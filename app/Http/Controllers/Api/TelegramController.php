<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $username = $message['from']['username'] ?? null;
            $firstName = $message['from']['first_name'] ?? 'User';

            // 1. Simpan / Update User ke Database MySQL
            $user = User::firstOrCreate(
                ['telegram_id' => $chatId],
                [
                    'username' => $username,
                    'first_name' => $firstName,
                    'is_subscribed' => false,
                ]
            );

            // 2. Handling Command /start
            if (str_starts_with($text, '/start')) {
                $replyText = "Halo {$firstName}! 👋\n\nSelamat datang di Bot Streaming Film.\nBuka katalog film eksklusif dan tonton langsung melalui tombol di bawah ini!";

                $this->sendMessage($chatId, $replyText);
            }

            // 3. Handling Command /status
            elseif ($text === '/status') {
                $statusText = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture()
                    ? "✅ Status Langganan: **AKTIF**\nExpired: " . $user->expired_at->format('d M Y H:i')
                    : "❌ Status Langganan: **TIDAK AKTIF**\nSilakan beli paket langganan terlebih dahulu.";

                $this->sendMessage($chatId, $statusText);
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    private function sendMessage($chatId, $text)
    {
        // Mengambil token langsung dari .env untuk memastikan nilainya ada
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
