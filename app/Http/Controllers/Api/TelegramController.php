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
                    $this->sendCheckoutLink($chatId, $packageId);
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

                    $webAppUrl = rtrim(config('services.telegram.webapp_url'), '/') . '/app';

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

    /**
     * Kirim tombol Web App yang membuka halaman /checkout di dalam TWA.
     * Transaksi Midtrans dibuat & dibayar sepenuhnya di dalam TWA, bukan dari bot.
     */
    private function sendCheckoutLink($chatId, $packageId)
    {
        $package = Package::where('is_active', true)->find($packageId);

        if (!$package) {
            $this->sendMessage($chatId, "⚠️ Paket tidak ditemukan atau sudah tidak tersedia.");
            return;
        }

        $priceFormatted = number_format($package->price, 0, ',', '.');
        $checkoutUrl = rtrim(config('services.telegram.webapp_url'), '/') . '/checkout?package_id=' . $package->id;

        $text = "🛒 *{$package->name}*\n"
            . "Rp {$priceFormatted} • {$package->duration_days} Hari\n\n"
            . "Tekan tombol di bawah untuk menyelesaikan pembayaran langsung di dalam aplikasi.";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🚀 Lanjut ke Pembayaran', 'web_app' => ['url' => $checkoutUrl]]
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
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
