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
    // Label tombol "STATUS LANGGANAN" di reply keyboard bawah. Ditaruh jadi konstanta
    // supaya label di sendPersistentMenu() dan pengecekan teksnya di handleWebhook()
    // dijamin selalu sama persis (Telegram mengirim balik teks tombol apa adanya).
    private const BTN_STATUS = '📊 STATUS LANGGANAN';

    public function handleWebhook(Request $request)
    {
        try {
            // DEBUG SEMENTARA: catat body mentah persis seperti dikirim Telegram, sebelum
            // disentuh decode/casting apa pun, plus ukuran integer PHP di server ini.
            // Ini buat memastikan betul-betul di titik mana angka chat.id berubah.
            Log::info('RAW webhook body', [
                'raw_body' => $request->getContent(),
                'PHP_INT_SIZE' => PHP_INT_SIZE,
                'PHP_INT_MAX' => PHP_INT_MAX,
            ]);

            // PENTING: JANGAN pakai $request->all() untuk update Telegram.
            // ID Telegram (chat.id, from.id) sekarang bisa >2.147.483.647 (di luar batas
            // integer 32-bit). $request->all() mem-parsing JSON lewat json_decode() versi
            // default, yang membiarkan PHP bebas memilih representasi angka besar — begitu
            // nilai itu nyasar lewat konversi/parameter bertipe int di suatu tempat, angka
            // besar seperti 8745282259 bisa overflow/wrap jadi 155347667 (persis kasus yang
            // ditemukan: 8745282259 % 2^32 = 155347667). Dengan flag JSON_BIGINT_AS_STRING,
            // semua angka yang melebihi batas integer PHP dipaksa jadi STRING sejak awal,
            // jadi tidak akan pernah tersentuh casting integer di mana pun sepanjang alur.
            $update = json_decode($request->getContent(), true, 512, JSON_BIGINT_AS_STRING) ?? [];
            Log::info('Telegram Update Received:', $update);

            // 1. Handling Callback Query (Klik Tombol Inline)
            if (isset($update['callback_query'])) {
                $callbackQuery = $update['callback_query'];
                $callbackQueryId = $callbackQuery['id'];
                $chatId = $callbackQuery['message']['chat']['id'];
                $chatType = $callbackQuery['message']['chat']['type'] ?? 'private';
                $callbackData = $callbackQuery['data'] ?? '';

                // Hilangkan indikator loading di tombol Telegram secara instan
                $this->answerCallbackQuery($callbackQueryId);

                // Sama seperti guard di handler pesan teks: abaikan kalau tombol ini entah
                // bagaimana berasal dari grup (chat.id negatif), supaya tidak ikut membuat/
                // mengubah baris user dengan telegram_id yang sebenarnya adalah ID grup.
                if ($chatType !== 'private') {
                    return response()->json(['status' => 'success'], 200);
                }

                // Sinkronkan username/first_name terbaru tiap kali user berinteraksi lewat tombol,
                // sama seperti yang sudah dilakukan VerifyTelegramInitData di sisi TWA. Data ini
                // ('from') selalu tersedia di setiap callback_query, jadi dipakai juga di sini.
                $callbackUsername = $callbackQuery['from']['username'] ?? null;
                $callbackFirstName = $callbackQuery['from']['first_name'] ?? 'User';
                $currentUser = $this->syncTelegramUser($chatId, $callbackUsername, $callbackFirstName);

                try {
                    // Jika user klik tombol Status Langganan
                    if ($callbackData === 'check_status') {
                        // PENTING: pakai $currentUser yang SUDAH di-resolve syncTelegramUser() di atas
                        // (telegram_id-nya sudah melalui trim/cast yang konsisten), BUKAN query baru
                        // pakai $chatId mentah — sebelumnya ini dua sumber berbeda dan bisa gagal cocok,
                        // yang membuat tombol ini terlihat "tidak merespons" (loading hilang tapi pesan
                        // balasan tidak pernah terkirim karena exception atau silent-null di tengah jalan).
                        $user = $currentUser;

                        Log::info('Cek status via tombol', [
                            'chat_id' => $chatId,
                            'user_id' => $user->id ?? null,
                            'telegram_id_di_db' => $user->telegram_id ?? 'NOT_FOUND',
                            'is_subscribed' => $user->is_subscribed ?? null,
                            'expired_at' => $user->expired_at ? $user->expired_at->toDateTimeString() : null,
                        ]);

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

                    // Tombol navigasi episode di bawah video ("watch:{movie_id}:{episode_id}"),
                    // supaya user bisa lanjut nonton tanpa harus balik ke TWA setiap episode.
                    // Status VIP dicek ulang di dalam TelegramMediaSender::sendEpisode setiap kali
                    // tombol ini ditekan, bukan cuma sekali di awal.
                    elseif (str_starts_with($callbackData, 'watch:')) {
                        [, $movieId, $episodeId] = array_pad(explode(':', $callbackData), 3, null);

                        $movie = \App\Models\Movie::find($movieId);
                        $episode = $episodeId ? \App\Models\Episode::find($episodeId) : null;

                        if ($movie) {
                            \App\Services\TelegramMediaSender::sendEpisode($chatId, $movie, $episode);
                        }
                    }
                } catch (\Throwable $e) {
                    // Sebelumnya kalau ada error di sini, tombol terlihat "tidak merespons" sama
                    // sekali (loading hilang karena answerCallbackQuery sudah dipanggil di awal,
                    // tapi pesan balasan tidak pernah terkirim). Sekarang minimal user tetap dapat
                    // notifikasi, dan errornya kecatat jelas di log untuk didiagnosis.
                    Log::error('Gagal memproses callback_query', [
                        'callback_data' => $callbackData,
                        'chat_id' => $chatId,
                        'error' => $e->getMessage(),
                    ]);
                    $this->sendMessage($chatId, "⚠️ Terjadi kendala saat memproses permintaanmu, coba lagi ya.");
                }
            }

            // 2. Handling Pesan Teks Normal / Command (/start, /status, /paket)
            elseif (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $chatType = $message['chat']['type'] ?? 'private';

                // PENTING: hanya proses pesan dari chat PRIBADI (1-on-1 dengan bot).
                // Kalau bot di-invite ke grup dan seseorang ketik /start atau command lain di
                // sana, Telegram tetap mengirimnya sebagai update 'message' (bukan 'channel_post'
                // — itu cuma untuk channel), dengan chat.id berupa ID GRUP (angka negatif),
                // bukan ID user. Tanpa guard ini, baris di bawah bisa membuat baris "user" palsu
                // di tabel users: telegram_id = ID grup (negatif), tapi username = username
                // pengirim asli — bikin data user jadi campur aduk / kelihatan duplikat.
                if ($chatType !== 'private') {
                    return response()->json(['status' => 'success'], 200);
                }

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

                // Simpan / Update User ke Database MySQL, sekaligus sinkronkan username/first_name
                // terbaru kalau user sudah pernah ganti username sebelumnya (lihat syncTelegramUser()).
                $user = $this->syncTelegramUser($chatId, $username, $firstName);

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

                    // Tombol inline yang menempel langsung di pesan /start.
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🎬 REELGATE', 'web_app' => ['url' => $this->webAppUrl('/app')]]
                            ],
                            [
                                ['text' => '📊 Status Langganan', 'callback_data' => 'check_status']
                            ],
                            [
                                ['text' => '🎥 Request Film', 'web_app' => ['url' => $this->webAppUrl('/request-film')]]
                            ],
                            [
                                ['text' => '📦 Lihat Paket', 'web_app' => ['url' => $this->webAppUrl('/app?tab=packages')]]
                            ]
                        ]
                    ];

                    $this->sendMessageWithKeyboard($chatId, $replyText, $keyboard);

                    // Selain tombol inline di atas, pasang juga menu persisten di bawah kolom
                    // ketik (reply keyboard) supaya menu utama selalu bisa diakses tanpa harus
                    // scroll balik ke pesan /start.
                    $this->sendPersistentMenu($chatId);
                }

                // Command /status ATAU tombol "STATUS LANGGANAN" di reply keyboard bawah
                // (tombol ini murni tombol teks biasa, bukan web_app, jadi begitu ditekan
                // Telegram mengirimkannya sebagai pesan teks normal seperti command lain).
                elseif ($text === '/status' || $text === self::BTN_STATUS) {
                    // Jejak audit: bandingkan chat_id & user_id di sini dengan log "Admin extend VIP"
                    // kalau ada laporan status tidak sinkron antara admin/TWA vs bot.
                    Log::info('Cek status via /status', [
                        'chat_id' => $chatId,
                        'user_id' => $user->id,
                        'telegram_id_di_db' => $user->telegram_id,
                        'username_di_db' => $user->username,
                        'is_subscribed' => $user->is_subscribed,
                        'expired_at' => $user->expired_at ? $user->expired_at->toDateTimeString() : null,
                    ]);

                    $statusText = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture()
                        ? "✅ Status Langganan: *AKTIF*\nExpired: " . $user->expired_at->format('d M Y H:i') . " WIB"
                        : "❌ Status Langganan: *TIDAK AKTIF*\nSilakan beli paket langganan terlebih dahulu.";

                    $this->sendMessage($chatId, $statusText);
                }

                // Command /paket atau /buy
                elseif ($text === '/paket' || $text === '/buy') {
                    $this->sendPackageList($chatId);
                }

                // Command /menu buat munculin lagi reply keyboard bawah kalau ke-dismiss user.
                elseif ($text === '/menu') {
                    $this->sendPersistentMenu($chatId);
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

    /**
     * Cari/buat User berdasarkan telegram_id (chat_id), lalu sinkronkan kolom
     * username & first_name kalau ada perubahan dari data Telegram terbaru.
     *
     * PENTING: pencocokan user SELALU pakai telegram_id (chat_id numerik dari Telegram,
     * tidak pernah berubah), bukan username. Jadi status VIP tidak akan pernah "hilang"
     * gara-gara user ganti username. Sinkronisasi username di sini hanya supaya kolom
     * `username` di tabel admin (dipakai buat pencarian di halaman Manajemen User) selalu
     * menampilkan username yang terbaru, sama seperti yang sudah berjalan di TWA lewat
     * VerifyTelegramInitData.
     */
    private function syncTelegramUser($chatId, ?string $username, ?string $firstName): User
    {
        // Paksa jadi string bersih di titik SATU-SATUNYA sebelum menyentuh DB, apa pun tipe
        // aslinya (int, string dari JSON_BIGINT_AS_STRING, dst). Ini defensif khusus buat
        // hosting ini yang diketahui PHP_INT_SIZE=4 (32-bit) — di lingkungan begini, ID
        // Telegram besar sangat rawan berubah kalau lewat casting int di mana pun sebelum
        // titik ini. trim() jaga-jaga ada whitespace tak kasat mata ikut kebawa.
        $chatId = trim((string) $chatId);

        Log::info('syncTelegramUser dipanggil', [
            'chatId_value' => $chatId,
            'chatId_type' => gettype($chatId),
            'chatId_length' => strlen($chatId),
        ]);

        $user = User::firstOrCreate(
            ['telegram_id' => $chatId],
            [
                'username' => $username,
                'first_name' => $firstName,
                'is_subscribed' => false,
            ]
        );

        if ($username !== $user->username || ($firstName && $firstName !== $user->first_name)) {
            $user->update([
                'username' => $username ?? $user->username,
                'first_name' => $firstName ?? $user->first_name,
            ]);
        }

        return $user;
    }

    /**
     * Bangun URL absolut ke suatu halaman TWA berdasarkan TELEGRAM_WEBAPP_URL di .env.
     * $path boleh menyertakan query string sendiri, mis. '/app?tab=packages'.
     */
    private function webAppUrl(string $path): string
    {
        return rtrim(config('services.telegram.webapp_url'), '/') . $path;
    }

    /**
     * Kirim reply keyboard (menu di bawah kolom ketik) berisi 4 menu utama:
     * REELGATE, STATUS LANGGANAN, REQUEST FILM, LIHAT PAKET.
     *
     * PENTING soal tipe tombol di reply keyboard:
     * - REELGATE, REQUEST FILM, LIHAT PAKET pakai 'web_app' → begitu ditekan, Telegram
     *   LANGSUNG membuka TWA di URL terkait, TIDAK mengirim pesan teks apa pun ke bot.
     * - STATUS LANGGANAN sengaja dibuat tombol teks BIASA (tanpa web_app/url), karena
     *   fiturnya cukup dijawab lewat pesan bot (lihat pengecekan self::BTN_STATUS di
     *   handleWebhook), bukan lewat halaman TWA.
     *
     * Keyboard ini persisten: sekali terkirim (reply_markup ResizeKeyboard), Telegram akan
     * terus menampilkannya di bawah kolom ketik user sampai bot mengirim ReplyKeyboardRemove
     * atau user menutupnya manual.
     */
    private function sendPersistentMenu($chatId): void
    {
        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '🎬 REELGATE', 'web_app' => ['url' => $this->webAppUrl('/app')]],
                    ['text' => self::BTN_STATUS],
                ],
                [
                    ['text' => '🎥 REQUEST FILM', 'web_app' => ['url' => $this->webAppUrl('/request-film')]],
                    ['text' => '📦 LIHAT PAKET', 'web_app' => ['url' => $this->webAppUrl('/app?tab=packages')]],
                ],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];

        $this->sendMessageWithKeyboard(
            $chatId,
            'Gunakan menu cepat di bawah ini untuk navigasi 👇',
            $keyboard
        );
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
