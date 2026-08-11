<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $update = $request->all();

        // Log incoming update for debugging
        Log::info('Telegram Update:', $update);

        // 1. Handling Pesan Teks Normal / Command
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $username = $message['from']['username'] ?? null;
            $firstName = $message['from']['first_name'] ?? 'User';

            // Simpan / Update User ke Database MySQL
            $user = User::firstOrCreate(
                ['telegram_id' => $chatId],
                [
                    'username' => $username,
                    'first_name' => $firstName,
                    'is_subscribed' => false,
                ]
            );

            // Command /start
            if (str_starts_with($text, '/start')) {
                $replyText = "Halo {$firstName}! 👋\n\nSelamat datang di Bot Streaming Film.\nBuka katalog film eksklusif dan tonton langsung melalui tombol di bawah ini!";

                $this->sendMessage($chatId, $replyText);
            }

            // Command /status
            elseif ($text === '/status') {
                $statusText = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture()
                    ? "✅ Status Langganan: *AKTIF*\nExpired: " . $user->expired_at->format('d M Y H:i')
                    : "❌ Status Langganan: *TIDAK AKTIF*\nSilakan beli paket langganan terlebih dahulu.";

                $this->sendMessage($chatId, $statusText);
            }

            // Command /paket (Untuk Menampilkan Daftar Paket)
            elseif ($text === '/paket' || $text === '/buy') {
                $this->sendPackageList($chatId);
            }
        }

        // 2. Handling Klik Tombol Inline (Callback Query)
        if (isset($update['callback_query'])) {
            $callbackQuery = $update['callback_query'];
            $chatId = $callbackQuery['message']['chat']['id'];
            $callbackData = $callbackQuery['data'];

            // Jika user memilih paket langganan (Format data: buy_package_{id})
            if (str_starts_with($callbackData, 'buy_package_')) {
                $packageId = str_replace('buy_package_', '', $callbackData);
                $this->processPaymentRequest($chatId, $packageId);
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Menampilkan daftar paket langganan dengan Inline Keyboard Buttons
     */
    private function sendPackageList($chatId)
    {
        $packages = Package::all();

        if ($packages->isEmpty()) {
            $this->sendMessage($chatId, "⚠️ Belum ada paket langganan yang tersedia saat ini.");
            return;
        }

        $keyboard = [];
        foreach ($packages as $package) {
            $priceFormatted = number_format($package->price, 0, ',', '.');
            $keyboard[] = [
                [
                    'text' => "📦 {$package->name} - Rp {$priceFormatted} ({$package->duration_days} Hari)",
                    'callback_data' => "buy_package_{$package->id}"
                ]
            ];
        }

        $token = env('TELEGRAM_BOT_TOKEN');
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => "🍿 *PILIH PAKET LANGGANAN*\n\nSilahkan pilih paket langganan yang kamu inginkan:",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    /**
     * Memanggil PaymentController untuk membuat transaksi Midtrans dan mengirim tombol bayar
     */
    private function processPaymentRequest($chatId, $packageId)
    {
        // Panggil PaymentController internal
        $paymentRequest = new Request([
            'telegram_id' => $chatId,
            'package_id' => $packageId,
        ]);

        $paymentController = new PaymentController();
        $response = $paymentController->createTransaction($paymentRequest);
        $responseData = json_decode($response->getContent(), true);

        if (isset($responseData['status']) && $responseData['status'] === 'success') {
            $paymentUrl = $responseData['data']['payment_url'];
            $amount = number_format($responseData['data']['amount'], 0, ',', '.');

            $text = "💳 *TAGIHAN PEMBAYARAN MIDTRANS*\n\n"
                . "Order ID: `{$responseData['data']['order_id']}`\n"
                . "Total Bayar: *Rp {$amount}*\n\n"
                . "Silakan klik tombol di bawah ini untuk membayar via *QRIS (GoPay/ShopeePay/OVO/DANA), Transfer Bank, dll.*";

            $token = env('TELEGRAM_BOT_TOKEN');
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🚀 Bayar Sekarang via Midtrans', 'url' => $paymentUrl]
                        ]
                    ]
                ])
            ]);
        } else {
            $this->sendMessage($chatId, "❌ Gagal membuat transaksi pembayaran. Silakan coba lagi.");
        }
    }

    private function sendMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN belum diatur di file .env');
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
