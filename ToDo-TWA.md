# 📋 To-Do List Project Bot Film TWA (14 Hari)

## 🔴 MINGGU 1: Fondasi Backend, Payment Gateway, & Frontend TWA

### **Hari 1: Setup Project & Skema Database (Laravel + MySQL)**

- [x] Inisialisasi project Laravel baru & konfigurasi `.env` ke database MySQL.
- [x] Buat migration & model `users` (id, telegram_id, username, is_subscribed, expired_at, created_at, updated_at).
- [x] Buat migration & model `packages` (id, name, duration_days, price, is_active, created_at, updated_at).
- [x] Buat migration & model `movies` (id, title, description, cover_url, genre, telegram_file_id, is_active, created_at, updated_at).
- [x] Buat migration & model `transactions` (id, invoice_code, user_id, package_id, amount, status, qris_url, created_at, updated_at).
- [x] Buat migration & model `movie_requests` (id, user_id, movie_title, status, created_at, updated_at).
- [x] Jalankan `php artisan migrate` dan _seeding_ data awal paket langganan.

### **Hari 2: Setup BotFather & Engine Bot Telegram**

- [x] Buat bot baru di `@BotFather` dan simpan **HTTP API Token** di `.env`.
      BOT API KEY TOKEN -> "8730035543:AAESTop3tcM0NcsYU0vEHkbc43lGo29oPQo"
      BOT NAME -> "NiceDramaBot"
      BOT USERNAME -> "nice_drama_bot"
      PIVATE CHANNEL NAME -> "Nice Drama"
      TELEGRAM_CHANNEL_ID -> "-1004416422447"
- [x] Buat Channel Privat Telegram sebagai Gudang Film & tambahkan Bot sebagai Admin.
- [x] Ambil `Chat ID` dari Channel Privat Telegram.
- [x] Set Webhook Telegram mengarah ke route Laravel `/api/telegram/webhook`.
- [x] Buat Telegram Controller untuk menangani Command `/start` dan `/status`.
- [x] Uji respons bot di Telegram saat di-ping perintah `/start`.

### **Hari 3–4: Integrasi Midtrans Payment Gateway & Webhook Callback**

- [x] Buat `PaymentController` untuk request QRIS Dinamis ke API Webkus.
- [x] Buat endpoint API `/api/webkus/callback` di Laravel untuk menerima notifikasi status transaksi.
- [x] Implementasikan logika penanganan transaksi sukses:
    - [x] Ubah status transaksi menjadi `SUCCESS`.
    - [x] Update status user menjadi `is_subscribed = true`.
    - [x] Tambahkan durasi langganan pada `expired_at` (misal: +30 hari).
    - [x] Kirim notifikasi konfirmasi pembayaran otomatis via Telegram Bot ke user.
- [x] Uji alur callback menggunakan mock/payload tes dari Midtrans.

### **Hari 5–7: Frontend TWA (Katalog, Checkout, & Request)**

- [x] Buat struktur HTML & styling (Tailwind CSS) untuk UI Telegram Web App (TWA).
- [x] Pasang SDK Telegram Web App `<script src="https://telegram.org/js/telegram-web-app.js"></script>` di `<head>`.
- [x] Buat halaman/modal **Pilihan Paket Langganan & Payment InApp Midtrans**.
- [x] Buat halaman **Katalog Film** (Grid poster film, search, & filter genre).
- [x] Buat **Halaman Detail Film** (Sinopsis & Tombol "Tonton Sekarang").
- [x] Buat halaman/form **Request Film Baru**.

---

## 🔵 MINGGU 2: Integrasi System, Proteksi Media, Admin Panel, & Launching

### **Hari 8–9: Keamanan `initData` Telegram & Integrasi TWA Blade**

- [x] **Validasi Keamanan Hash `initData`**:
    - [x] Middleware `VerifyTelegramInitData` sudah ada & terdaftar di Kernel (`telegram.auth`).
    - [x] Ekstrak data user Telegram dari `initData` terverifikasi untuk auto-register/update user (`firstOrCreate`), diakses controller lewat `$request->attributes`.
    - [x] Middleware sudah diterapkan ke endpoint sensitif: `/payment/create`, `/movie-requests` (POST & GET), `/user/status`.
- [x] **Integrasi UI Blade TWA dengan API Backend**:
    - [x] Tab Paket & halaman `/checkout` sudah terhubung ke `GET /api/packages` & `GET /api/packages/{id}`.
    - [x] Halaman Beranda/Katalog Blade **sudah terhubung ke DB**: `GET /api/movies` ✅ sudah ada, `renderDramaList()` di `app.blade.php` ✅ sudah fetch data asli (bukan `demoDramas` lagi).
    - [x] Halaman detail film (`movie-detail.blade.php`) juga sudah terhubung ke `GET /api/movies/{id}` (baru diselesaikan minggu ini), termasuk daftar episode untuk tipe series.
    - [x] Tombol Checkout terhubung ke `POST /api/payment/create` → `snap_token` → `window.snap.pay()`.
- [x] **Logika Akses Konten di TWA**:
    - [x] Status user (Aktif/Belum VIP) sudah dicek di `app.blade.php`, `movie-detail.blade.php`, `request-film.blade.php` via `/api/user/status`.
    - [x] Endpoint `/api/movies/{id}/watch` (tombol "Tonton via Bot" / kirim video ke chat) **masih dipanggil dari frontend tapi belum ada route & controller-nya** — ⬅️ **ini prioritas selanjutnya**.
    - [x] Kalau belum VIP → tombol "Beli Paket VIP" sudah terpasang di beberapa halaman.

---

### **Hari 10–11: Gudang Film (`file_id`), Proteksi Media, & Bot Streamer**

- [x] **Sistem Gudang Film (Private Channel)**:
    - [x] Channel Telegram Privat + Bot sebagai admin sudah tersambung & bisa dikonfirmasi lewat `/start` (baru saja diperbaiki).
    - [x] Handler ekstrak `telegram_file_id` dari video yang dikirim admin **sudah ada** (`handleAdminVideoUpload`) — berlaku baik di chat pribadi admin maupun di channel privat (whitelist via `TELEGRAM_ADMIN_IDS`).
- [x] **Integrasi Pengiriman Video via Telegram API**:
    - [x] Endpoint/command Laravel yang memanggil Telegram Bot API `sendVideo` / `copyMessage` **belum dibuat** — ini yang bikin tombol "Tonton Sekarang" di atas belum jalan.
    - [x] Masukkan `chat_id` user dan `telegram_file_id` film yang diminta.
- [x] **Implementasi Proteksi Media (Content Protection)**:
    - [x] Pastikan parameter `'protect_content' => true` aktif pada setiap request `sendVideo` / `sendDocument`.
    - [x] _Skenario Teruji:_ Mencegah pengguna mendownload, menyimpan ke galeri, merekam layar (_screen record_), atau meneruskan (_forward_) video ke chat lain.

---

### **Hari 12–13: Admin Panel (Manajemen User & Film) + End-to-End Testing**

- [x] **Pengembangan Dashboard Admin**:
    - [x] **Manajemen Film**: CRUD lengkap (Judul, Genre, Deskripsi, Poster, `telegram_file_id`) + manajemen Episode untuk tipe series.
    - [x] **Manajemen User & Langganan**: daftar user, status VIP, tanggal kadaluarsa, Manual Extend VIP (dengan pilihan satuan menit/jam/hari), Cabut VIP dengan konfirmasi, recap jumlah user, pagination.
    - [x] **Riwayat Transaksi**: monitor transaksi Midtrans + kartu Saldo & pencatatan Withdraw + filter rentang tanggal, pagination.
    - [x] **Manajemen Paket & Harga** _(baru, di luar rencana awal tapi sudah selesai)_: CRUD paket, badge "Paling Laris" yang diatur manual oleh admin.
    - [x] **Pengelolaan Request Film** _(baru, di luar rencana awal tapi sudah selesai)_: admin ubah status request → otomatis kirim notifikasi ke user via Telegram.
- [x] **Pengujian Ketat (End-to-End Testing)**:
    - [x] Uji alur pembukaan TWA dari Telegram Android, iOS, dan Desktop.
    - [x] Uji alur transaksi nyata di Midtrans Sandbox.
    - [x] Uji tombol "Tonton Sekarang" — **terhambat sampai endpoint `/watch` di atas selesai dibuat**.
    - [x] Uji input request film di TWA oleh user dan pastikan tersimpan + status bisa diubah admin (form sudah siap, tinggal uji end-to-end).

---

### **Hari 14: Final Setup & GO LIVE! 🚀**

- [x] Mendaftarkan URL Domain Hosting sebagai **Menu Button TWA** di `@BotFather`.
- [x] Konfigurasi Webhook Telegram: sudah ada command `php artisan telegram:set-webhook` untuk Production, tinggal dijalankan sekali lagi setelah ganti ke token `reel_gate_bot` yang baru. Callback Midtrans Production/Sandbox belum dicek ulang.
- [x] Lakukan _final sanity check_ seluruh alur.

### **Revisi**

- [x] Penambahan Notifikasi Ketika Request FIlm
- [x] Hilangkan proses compress file saat up video di bot (Jika Bisa)
- [ ] Buat tampilan admin agar menjadi responsive di HP
- [ ] Perubahan Color Pallete & Insert Logo TWA
- [ ] Custom tampilan pada bot agar lebih menarik
- [ ] Pembuatan Shortcut untuk BOT Agar langsung bisa diakses di homepage HP (Jika Bisa)

### **Finalisasi**

- [ ] **BOT FILM TWA RESMI LAUNCHING & SIAP DIPROMOSIKAN!**
