<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Membuat Transaksi PENDING & Mengambil Redirect URL Snap Midtrans
     */
    public function createTransaction(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        // Prioritaskan user yang sudah diverifikasi lewat middleware VerifyTelegramInitData
        // (route ini pakai middleware telegram.auth), fallback ke telegram_id mentah hanya
        // untuk kompatibilitas kalau suatu saat dipanggil tanpa middleware tsb.
        $user = $request->attributes->get('verified_telegram_user');

        if (!$user) {
            $request->validate(['telegram_id' => 'required']);
            $user = User::where('telegram_id', (string) $request->telegram_id)->firstOrFail();
        }

        $package = Package::where('is_active', true)->findOrFail($request->package_id);

        $orderId = 'INV-' . strtoupper(Str::random(6)) . '-' . time();

        $transaction = Transaction::create([
            'invoice_code' => $orderId,
            'user_id' => $user->id,
            'package_id' => $package->id,
            'amount' => $package->price,
            'status' => 'PENDING',
        ]);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $package->price,
            ],
            'customer_details' => [
                'first_name' => $user->first_name ?? 'User',
                'email' => $user->username ? "{$user->username}@telegram.user" : "user_{$user->telegram_id}@telegram.user",
            ],
            'item_details' => [
                [
                    'id' => 'PKG-' . $package->id,
                    'price' => (int) $package->price,
                    'quantity' => 1,
                    'name' => $package->name,
                ]
            ],
        ];

        try {
            $snapTransaction = Snap::createTransaction($params);
            // redirect_url disimpan sebagai fallback (mis. untuk dicek manual di admin),
            // tapi pembayaran utamanya dilakukan lewat snap_token yang di-embed di halaman checkout.
            $transaction->update(['qris_url' => $snapTransaction->redirect_url]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'order_id' => $orderId,
                    'amount' => $package->price,
                    'snap_token' => $snapTransaction->token,
                    'payment_url' => $snapTransaction->redirect_url,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat transaksi Midtrans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook / Notification Callback dari Midtrans
     */
    public function handleCallback(Request $request)
    {
        Log::info('Midtrans Notification Received:', $request->all());

        $serverKey = config('services.midtrans.server_key');
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');

        // Validasi Signature Key
        $mySignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);
        if ($signatureKey !== $mySignature) {
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        $transaction = Transaction::where('invoice_code', $orderId)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction Not Found'], 404);
        }

        if ($transaction->status === 'SUCCESS') {
            return response()->json(['message' => 'Transaction Already Processed']);
        }

        // Jika transaksi berhasil (settlement / capture)
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $transaction->update(['status' => 'SUCCESS']);

            $user = $transaction->user;
            $package = $transaction->package;

            $currentExpired = ($user->expired_at && $user->expired_at->isFuture())
                ? $user->expired_at
                : Carbon::now();

            $user->update([
                'is_subscribed' => true,
                'expired_at' => $currentExpired->addDays($package->duration_days),
            ]);

            $this->notifyUserPaymentSuccess($user->telegram_id, $transaction, $package);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $transaction->update(['status' => 'FAILED']);
        }

        return response()->json(['status' => 'OK']);
    }

    private function notifyUserPaymentSuccess($telegramId, $transaction, $package)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $text = "🎉 *PEMBAYARAN BERHASIL!*\n\n"
            . "Order ID: `{$transaction->invoice_code}`\n"
            . "Paket: {$package->name}\n"
            . "Status Langganan: *AKTIF*\n"
            . "Berlaku Hingga: " . $transaction->user->expired_at->format('d M Y H:i') . " WIB\n\n"
            . "Terima kasih! Silahkan nikmati tayangan eksklusif kami.";

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $telegramId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
