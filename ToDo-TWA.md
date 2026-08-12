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
- [ ] Implementasikan logika penanganan transaksi sukses:
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
    - [x] Buat Middleware Laravel (`VerifyTelegramInitData`) untuk memverifikasi HMAC-SHA256 signature dari `Telegram.WebApp.initData` (dikirim via header `X-Telegram-Init-Data`).
    - [x] Ekstrak data user Telegram (`id`, `first_name`, `username`) dari `initData` terverifikasi untuk auto-register/update user (`firstOrCreate`), diakses controller lewat `$request->attributes`.
    - [x] Terapkan middleware ke endpoint sensitif: `/payment/create`, `/movie-requests` (POST & GET), `/user/status`.
- [ ] **Integrasi UI Blade TWA dengan API Backend**:
    - [x] Tab Paket & halaman `/checkout` sudah terhubung ke `GET /api/packages` & `GET /api/packages/{id}`.
    - [ ] Halaman Beranda/Katalog Blade masih pakai data dummy (`demoDramas`) — belum terhubung ke tabel `movies` di DB. **Perlu dibuat**: model `Movie`, migration, `GET /api/movies`, lalu update `renderDramaList()` di `app.blade.php`.
    - [x] Tombol Checkout terhubung ke `POST /api/payment/create` → mengembalikan `snap_token`, dirender langsung di halaman `/checkout` via `window.snap.pay()` (satu alur di dalam TWA, tanpa redirect keluar app/browser).
- [ ] **Logika Akses Konten di TWA**:
    - [x] Status user (Aktif/Belum VIP) sudah dicek di `app.blade.php`, `movie-detail.blade.php`, `request-film.blade.php` via `/api/user/status` (sekarang lebih aman karena pakai initData terverifikasi).
    - [ ] Endpoint `/api/movies/{id}/watch` (tombol "Tonton via Bot" / kirim video ke chat) **masih dipanggil dari frontend tapi belum ada route & controller-nya** — nyambung ke poin katalog film di atas.
    - [x] Kalau belum VIP → tombol "Beli Paket VIP" sudah terpasang di beberapa halaman (`request-film.blade.php`, dan alur checkout umum).

---

### **Hari 10–11: Gudang Film (`file_id`), Proteksi Media, & Bot Streamer**

- [ ] **Sistem Gudang Film (Private Channel)**:
    - [ ] Buat Channel Telegram Privat khusus gudang film dan tambahkan Bot sebagai Admin.
    - [ ] Admin mengunggah berkas video film ke Channel Privat untuk mendapatkan `telegram_file_id` (via log webhook bot atau command khusus admin).
- [ ] **Integrasi Pengiriman Video via Telegram API**:
    - [ ] Buat endpoint/command handler Laravel yang memanggil Telegram Bot API `sendVideo` / `copyMessage`.
    - [ ] Masukkan `chat_id` user dan `telegram_file_id` film yang diminta.
- [ ] **Implementasi Proteksi Media (Content Protection)**:
    - [ ] Pastikan parameter `'protect_content' => true` aktif pada setiap request `sendVideo` / `sendDocument`.
    - [ ] _Skenario Teruji:_ Mencegah pengguna mendownload, menyimpan ke galeri, merekam layar (_screen record_), atau meneruskan (_forward_) video ke chat lain.

---

### **Hari 12–13: Admin Panel (Manajemen User & Film) + End-to-End Testing**

- [ ] **Pengembangan Dashboard Admin**:
    - [ ] Buat fitur **Manajemen Film**: CRUD data film (Judul, Genre, Deskripsi, Poster, `telegram_file_id`).
    - [ ] Buat fitur **Manajemen User & Langganan**: Melihat daftar user Telegram, status VIP, tanggal kadaluarsa, serta tombol _Manual Extend VIP_.
    - [ ] Buat fitur **Riwayat Transaksi**: Monitor transaksi Midtrans (`invoice_code`, nominal, status `SUCCESS`/`PENDING`/`FAILED`).
- [ ] **Pengujian Ketat (End-to-End Testing)**:
    - [ ] Uji alur pembukaan TWA dari Telegram Android, iOS, dan Desktop.
    - [ ] Uji alur transaksi nyata di Midtrans Sandbox: Pilih Paket ──► Snap Popup ──► Bayar via QRIS/VA ──► Callback Webhook ──► Status VIP User Aktif otomatis + Pesan Notifikasi Telegram terkirim.
    - [ ] Uji tombol "Tonton Sekarang": Dipastikan bot langsung membalas chat privat user dengan video film yang terproteksi.
    - [ ] Uji input request film di TWA oleh user dan pastikan tersimpan di DB Admin.

---

### **Hari 14: Final Setup & GO LIVE! 🚀**

- [ ] Mendaftarkan URL Domain Hosting (`https://nice.decaasoftwares.com`) sebagai **Menu Button TWA** di `@BotFather` (perintah `/setmenubutton`).
- [ ] Konfigurasi Webhook Telegram Production dan Callback Midtrans Production/Sandbox di domain hosting.
- [ ] Lakukan _final sanity check_ seluruh alur (TWA, Midtrans Snap, Webhook, Bot Auto-reply, dan Content Protection).
- [ ] **BOT FILM TWA RESMI LAUNCHING & SIAP DIPROMOSIKAN!**
