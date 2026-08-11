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
- [ ] Buat **Halaman Detail Film** (Sinopsis & Tombol "Tonton Sekarang").
- [ ] Buat halaman/form **Request Film Baru**.

---

## 🔵 MINGGU 2: Integrasi System, Proteksi Media, Testing, & Launching

### **Hari 8–9: Integrasi TWA ⇄ API Laravel**

- [ ] Hubungkan UI Vercel ke API Laravel menggunakan `fetch` / `axios`.
- [ ] Implementasikan verifikasi hash `initData` Telegram di Middleware Laravel agar API aman dari luar.
- [ ] Hubungkan komponen Katalog TWA dengan API GET `/api/movies`.
- [ ] Hubungkan tombol Checkout di TWA dengan API POST `/api/transactions/create`.
- [ ] Buat logika validasi di TWA:
    - [ ] Jika status user **Aktif** ──► panggil API untuk memicu bot mengirimkan video film.
    - [ ] Jika status user **Tidak Aktif** ──► munculkan modal pembayaran QRIS Webkus.

### **Hari 10–11: Sistem Gudang Film (`file_id`) & Proteksi Media**

- [ ] Admin mengunggah berkas film ke Channel Privat Telegram untuk mendapatkan `file_id`.
- [ ] Input data film beserta `telegram_file_id` ke database MySQL.
- [ ] Implementasikan API pengiriman video film via Telegram API `sendVideo` di Laravel.
- [ ] **Proteksi Media**: Pastikan parameter `protect_content => true` aktif saat pengiriman video agar pengguna tidak dapat men-download, merekam layar, atau membagikan (_forward_) video.

### **Hari 12–13: Testing Ketat (End-to-End Test)**

- [ ] Uji alur pembuka TWA dari dalam aplikasi Telegram versi Android & iOS.
- [ ] Uji alur transaksi nyata: Klik Paket ──► Scan QRIS Webkus ──► Callback sukses ──► Status berubah otomatis.
- [ ] Uji fitur tombol "Tonton Sekarang": Pastikan bot langsung mengirimkan video ke _chat privat_ user setelah diklik dari TWA.
- [ ] Uji proteksi konten: Pastikan media video terikat aturan `protect_content`.
- [ ] Uji alur input Request Film di TWA dan verifikasi data masuk ke database.

### **Hari 14: Final Setup & GO LIVE! 🚀**

- [ ] Mendaftarkan URL Vercel sebagai **Menu Button TWA** di `@BotFather` (perintah `/setmenubutton`).
- [ ] Pastikan server Laravel, SSL/HTTPS, database MySQL, dan Webhook Webkus berjalan stabil.
- [ ] Lakukan _final sanity check_ seluruh fitur.
- [ ] **BOT FILM TWA RESMI LAUNCHING & SIAP DIPROMOSIKAN!**
