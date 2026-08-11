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
        try {
            $update = $request->all();
            Log::info('Telegram Update Received:', $update);

            // 1. Handling Callback Query (Klik Tombol Inline)
            if (isset($update['callback_query'])) {
                $callbackQuery = $update['callback_query'];
                $callbackQueryId = $callbackQuery['id'];
                $chatId = $callbackQuery['message']['chat']['id'];
                $callbackData = $callbackQuery['data'] ?? '';

                // Hilangkan indikator loading di tombol Telegram secara instan
                $this->answerCallbackQuery($callbackQueryId);

                // Jika user klik tombol Status Langganan
                if ($callbackData === 'check_status') {
                    $user = User::where('telegram_id', $chatId)->first();
                    $statusText = ($user && $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture())
                        ? "✅ Status Langganan: *AKTIF*\nExpired: " . $user->expired_at->format('d M Y H:i') . " WIB"
                        : "❌ Status Langganan: *TIDAK AKTIF*\nSilakan pilih paket langganan terlebih dahulu.";

                    $this->sendMessage($chatId, $statusText);
                }

                // Jika user klik tombol Pilihan Paket
                elseif ($callbackData === 'view_packages') {
                    $this->sendPackageList($chatId);
                }

                // Jika user memilih paket langganan tertentu (buy_package_{id})
                elseif (str_starts_with($callbackData, 'buy_package_')) {
                    $packageId = str_replace('buy_package_', '', $callbackData);
                    $this->processPaymentRequest($chatId, $packageId);
                }
            }

            // 2. Handling Pesan Teks Normal / Command (/start, /status, /paket)
            elseif (isset($update['message'])) {
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
                    $replyText = "Halo {$firstName}! 👋\n\n"
                        . "Selamat datang di **NiceDramaBot**! 🍿\n"
                        . "Nikmati akses streaming berbagai drama & film eksklusif pilihan.\n\n"
                        . "Silakan pilih menu di bawah ini untuk memulai:";

                    $webAppUrl = "https://7cf4-2404-8000-1041-162-3d91-db2d-a401-fda8.ngrok-free.app/app";

                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🎬 Ruang Drama', 'web_app' => ['url' => $webAppUrl]]
                            ],
                            [
                                ['text' => '📊 Status Langganan', 'callback_data' => 'check_status'],
                                ['text' => '💎 Pilihan Paket', 'callback_data' => 'view_packages']
                            ]
                        ]
                    ];

                    $this->sendMessageWithKeyboard($chatId, $replyText, $keyboard);
                }

                // Command /status
                elseif ($text === '/status') {
                    $statusText = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture()
                        ? "✅ Status Langganan: *AKTIF*\nExpired: " . $user->expired_at->format('d M Y H:i') . " WIB"
                        : "❌ Status Langganan: *TIDAK AKTIF*\nSilakan beli paket langganan terlebih dahulu.";

                    $this->sendMessage($chatId, $statusText);
                }

                // Command /paket atau /buy
                elseif ($text === '/paket' || $text === '/buy') {
                    $this->sendPackageList($chatId);
                }
            }
        } catch (\Exception $e) {
            Log::error('Telegram Controller Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        }

        return response()->json(['status' => 'success'], 200);
    }

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

        $this->sendMessageWithKeyboard($chatId, "🍿 *PILIH PAKET LANGGANAN*\n\nSilahkan pilih paket langganan yang kamu inginkan:", ['inline_keyboard' => $keyboard]);
    }

    private function processPaymentRequest($chatId, $packageId)
    {
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

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🚀 Bayar Sekarang via Midtrans', 'url' => $paymentUrl]
                    ]
                ]
            ];

            $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
        } else {
            $this->sendMessage($chatId, "❌ Gagal membuat transaksi pembayaran. Silakan coba lagi.");
        }
    }

    private function sendMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function sendMessageWithKeyboard($chatId, $text, array $keyboard)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    private function answerCallbackQuery($callbackQueryId)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) return;

        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
        ]);
    }
}
