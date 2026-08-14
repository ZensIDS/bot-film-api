<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    /**
     * php artisan telegram:set-webhook
     * php artisan telegram:set-webhook --url=https://domain-lain.com/api/telegram/webhook
     */
    protected $signature = 'telegram:set-webhook {--url= : Override URL webhook, default pakai APP_URL + /api/telegram/webhook}';

    protected $description = 'Set/refresh webhook Telegram Bot supaya menyertakan semua tipe update yang dibutuhkan (termasuk channel_post untuk channel privat)';

    public function handle()
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN belum diset di .env.');
            return self::FAILURE;
        }

        $url = $this->option('url') ?: rtrim(config('app.url'), '/') . '/api/telegram/webhook';

        $this->info("Mendaftarkan webhook ke: {$url}");

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
            // Wajib sertakan channel_post & edited_channel_post, kalau tidak Telegram
            // TIDAK akan mengirim update apapun saat bot dipakai di dalam channel
            // (termasuk saat ada yang mengetik /start di channel tersebut).
            'allowed_updates' => json_encode([
                'message',
                'edited_message',
                'channel_post',
                'edited_channel_post',
                'callback_query',
            ]),
        ]);

        if ($response->failed()) {
            $this->error('Gagal set webhook: ' . $response->body());
            return self::FAILURE;
        }

        $this->info('Webhook berhasil di-set: ' . $response->body());

        // Set juga "menu button" (tombol di sebelah ikon emoji, sisi kiri kolom ketik) supaya
        // langsung mengarah ke home TWA (REELGATE). Ini berlaku global untuk semua chat privat
        // dengan bot (tanpa parameter chat_id), beda dari reply keyboard bawah yang dikirim
        // per-chat lewat pesan bot.
        $webAppUrl = config('services.telegram.webapp_url');

        if (!$webAppUrl) {
            $this->warn('TELEGRAM_WEBAPP_URL belum diset, menu button dilewati.');
            return self::SUCCESS;
        }

        $menuButtonResponse = Http::post("https://api.telegram.org/bot{$token}/setChatMenuButton", [
            'menu_button' => json_encode([
                'type' => 'web_app',
                'text' => 'REELGATE',
                'web_app' => ['url' => rtrim($webAppUrl, '/') . '/app'],
            ]),
        ]);

        if ($menuButtonResponse->failed()) {
            $this->error('Gagal set menu button: ' . $menuButtonResponse->body());
            return self::FAILURE;
        }

        $this->info('Menu button berhasil di-set: ' . $menuButtonResponse->body());

        return self::SUCCESS;
    }
}
