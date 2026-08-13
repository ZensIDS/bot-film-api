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

                // Khusus admin: kalau kirim video (atau file video sebagai document) ke bot,
                // langsung balas telegram_file_id-nya. Dipakai untuk isi form Manajemen Film
                // di Admin Panel tanpa perlu ambil file_id manual lewat Telegram API.
                if ($this->isWhitelistedAdmin($chatId) && (isset($message['video']) || $this->isVideoDocument($message))) {
                    $this->handleAdminVideoUpload($chatId, $message);
                    return response()->json(['status' => 'success'], 200);
                }

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
                    // NOTE: parse_mode yang dipakai di sendMessage() adalah 'Markdown' (versi lama),
                    // bukan 'MarkdownV2'. Di mode lama, teks tebal HARUS pakai satu bintang (*teks*),
                    // bukan dua (**teks**). Pemakaian ** sebelumnya membuat Telegram menolak seluruh
                    // request sendMessage (error 400: can't parse entities), sehingga /start terlihat
                    // seperti tidak membalas sama sekali.
                    $replyText = "Halo {$firstName}! 👋\n\n"
                        . "Selamat datang di *REELGATE*! 🍿\n"
                        . "Nikmati akses streaming berbagai drama & film eksklusif pilihan.\n\n"
                        . "Silakan pilih menu di bawah ini untuk memulai:";

                    $webAppUrl = rtrim(config('services.telegram.webapp_url'), '/') . '/app';

                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🎬 REELGATE', 'web_app' => ['url' => $webAppUrl]]
                            ],
                            [
                                ['text' => '📊 Status Langganan', 'callback_data' => 'check_status']
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

            // 3. Handling Channel Post (bot ditambahkan/jadi admin di sebuah channel privat)
            // PENTING: update tipe ini TIDAK dikirim Telegram kalau webhook di-set dengan
            // parameter allowed_updates yang tidak menyertakan "channel_post". Kalau setelah
            // perbaikan ini bot masih belum membalas di channel, set ulang webhook-nya tanpa
            // allowed_updates (biar semua tipe update dikirim) atau sertakan "channel_post"
            // secara eksplisit di dalamnya.
            elseif (isset($update['channel_post'])) {
                $post = $update['channel_post'];
                $chatId = $post['chat']['id'];
                $chatTitle = $post['chat']['title'] ?? 'channel ini';
                $text = $post['text'] ?? '';

                // Sama seperti di chat pribadi: kalau admin kirim/post video ke channel privat,
                // bot balas telegram_file_id-nya supaya bisa langsung dipakai di form Manajemen Film.
                if ($this->isWhitelistedAdmin($chatId) && (isset($post['video']) || $this->isVideoDocument($post))) {
                    $this->handleAdminVideoUpload($chatId, $post);
                    return response()->json(['status' => 'success'], 200);
                }

                if (str_starts_with($text, '/start')) {
                    if ($this->isWhitelistedAdmin($chatId)) {
                        $replyText = "🤖 *REELGATE* aktif di *{$chatTitle}*.\n\n"
                            . "Chat ID channel ini (`{$chatId}`) sudah terdaftar sebagai admin ✅\n"
                            . "Kirim/post video ke channel ini kapan saja untuk mengambil Telegram File ID-nya.";
                    } else {
                        $replyText = "🤖 *REELGATE* berhasil terhubung ke *{$chatTitle}*.\n\n"
                            . "Chat ID channel ini:\n`{$chatId}`\n\n"
                            . "Kalau ingin bot bisa membaca video yang di-post di sini untuk diambil Telegram File ID-nya, "
                            . "tambahkan Chat ID di atas ke daftar `TELEGRAM_ADMIN_IDS` pada file `.env` (pisahkan dengan koma jika lebih dari satu), lalu jalankan `php artisan config:clear` di server dan kirim `/start` lagi di sini.";
                    }

                    $this->sendMessage($chatId, $replyText);
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

    /**
     * Cek apakah chat_id ini terdaftar sebagai admin (whitelist via TELEGRAM_ADMIN_IDS di .env,
     * dipisah koma, contoh: TELEGRAM_ADMIN_IDS=111111111,222222222).
     * Wajib pakai whitelist supaya sembarang user tidak bisa "membajak" fitur ambil file_id ini.
     */
    private function isWhitelistedAdmin($chatId): bool
    {
        $adminIds = array_filter(array_map('trim', explode(',', (string) config('services.telegram.admin_ids', ''))));

        return in_array((string) $chatId, $adminIds, true);
    }

    /**
     * Beberapa client Telegram (terutama Desktop) mengirim video sebagai 'document'
     * kalau dikirim lewat opsi "Send as File". Deteksi itu lewat mime_type.
     */
    private function isVideoDocument(array $message): bool
    {
        return isset($message['document']['mime_type'])
            && str_starts_with($message['document']['mime_type'], 'video/');
    }

    /**
     * Balas ke admin dengan telegram_file_id dari video yang baru dikirim,
     * siap di-copy ke form Manajemen Film di Admin Panel.
     */
    private function handleAdminVideoUpload($chatId, array $message)
    {
        $video = $message['video'] ?? $message['document'] ?? null;

        if (!$video || !isset($video['file_id'])) {
            $this->sendMessage($chatId, "⚠️ Gagal membaca file video. Coba kirim ulang.");
            return;
        }

        $fileId = $video['file_id'];
        $fileName = $video['file_name'] ?? null;
        $durationText = isset($video['duration']) ? gmdate('H:i:s', $video['duration']) : '-';
        $sizeText = isset($video['file_size']) ? number_format($video['file_size'] / 1048576, 1) . ' MB' : '-';

        $text = "🎬 *Video Diterima!*\n\n"
            . ($fileName ? "Nama File: `{$fileName}`\n" : "")
            . "Durasi: {$durationText}\n"
            . "Ukuran: {$sizeText}\n\n"
            . "Telegram File ID:\n`{$fileId}`\n\n"
            . "Salin ID di atas, lalu tempel ke form film/episode di Admin Panel.";

        $this->sendMessage($chatId, $text);
    }

    private function sendMessage($chatId, $text)
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN belum diset di .env, sendMessage dibatalkan.');
            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);

        if ($response->failed()) {
            Log::error('Telegram sendMessage gagal: ' . $response->body());
        }
    }

    private function sendMessageWithKeyboard($chatId, $text, array $keyboard)
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN belum diset di .env, sendMessageWithKeyboard dibatalkan.');
            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard)
        ]);

        if ($response->failed()) {
            Log::error('Telegram sendMessageWithKeyboard gagal: ' . $response->body());
        }
    }

    private function answerCallbackQuery($callbackQueryId)
    {
        $token = config('services.telegram.bot_token');
        if (!$token) return;

        $response = Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
        ]);

        if ($response->failed()) {
            Log::error('Telegram answerCallbackQuery gagal: ' . $response->body());
        }
    }
}
