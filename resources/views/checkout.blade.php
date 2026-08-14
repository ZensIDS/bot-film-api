<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Checkout Paket VIP - REELGATE</title>

    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- 💳 Midtrans Snap JS (ganti ke https://app.midtrans.com/snap/snap.js untuk Production) -->
    <script type="text/javascript"
        src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('services.midtrans.client_key') }}"></script>

    <style>
        :root{
            --bg: #FFFFFF; --surface: #EFF5FC; --surface-2: #DCEAFC;
            --gold: #3A94FF; --gold-soft: #2184E2; --crimson: #C2355A;
            --text: #2D3647; --text-muted: #5C7A99; --hairline: rgba(31,78,127,0.15);
        }
        body { background-color: var(--bg); color: var(--text); font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display{ font-family: 'Fraunces', serif; }
        .skel{
            background: linear-gradient(100deg, var(--surface) 30%, var(--surface-2) 45%, var(--surface) 60%);
            background-size: 200% 100%;
            animation: shimmerSkel 1.4s ease-in-out infinite;
        }
        @keyframes shimmerSkel{ from{ background-position: 140% 0; } to{ background-position: -40% 0; } }
    </style>
</head>
<body class="min-h-screen p-4 flex flex-col justify-between max-w-md mx-auto">

    <div>
        <!-- Top Bar Navigasi Kembali -->
        <div class="flex items-center gap-3 mb-6">
            <button onclick="window.location.href='/app'" class="w-8 h-8 rounded-full bg-[var(--surface)] border border-[var(--hairline)] flex items-center justify-center text-sm">
                ←
            </button>
            <h1 class="text-base font-bold text-[var(--text)]">Rincian Pembayaran</h1>
        </div>

        <!-- State: memuat data paket -->
        <div id="loading-state">
            <div class="h-24 rounded-2xl skel mb-4"></div>
            <div class="h-16 rounded-xl skel"></div>
        </div>

        <!-- State: paket tidak ditemukan / URL tidak valid -->
        <div id="invalid-state" class="hidden text-center py-10">
            <div class="text-4xl mb-3">📦</div>
            <p class="font-semibold text-[var(--crimson)] mb-1">Paket tidak ditemukan</p>
            <p class="text-xs text-[var(--text-muted)]">Silakan kembali dan pilih ulang paket langgananmu.</p>
        </div>

        <!-- State: bukan dibuka dari Telegram -->
        <div id="outside-tg" class="hidden text-center py-10">
            <div class="text-4xl mb-3">⚠️</div>
            <p class="font-semibold text-[var(--gold-soft)] mb-1">Buka lewat Telegram</p>
            <p class="text-xs text-[var(--text-muted)]">Halaman checkout ini hanya bisa diakses lewat Telegram Mini App.</p>
        </div>

        <!-- State: konten checkout -->
        <div id="checkout-content" class="hidden">
            <!-- Kartu Ringkasan Pesanan -->
            <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5 mb-4 space-y-4">
                <div class="border-b border-[var(--hairline)] pb-3">
                    <p class="text-xs text-[var(--text-muted)] mb-0.5">Paket yang dipilih</p>
                    <h2 id="plan-title" class="font-display text-lg font-bold text-[var(--gold-soft)]">-</h2>
                    <p id="plan-duration" class="text-xs text-[var(--text-muted)] mt-0.5">-</p>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-[var(--text-muted)]">Harga Paket</span>
                        <span id="plan-price" class="font-semibold text-[var(--text)]">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[var(--text-muted)]">Biaya Layanan</span>
                        <span class="font-semibold text-emerald-400">Gratis</span>
                    </div>
                </div>

                <div class="border-t border-[var(--hairline)] pt-3 flex justify-between items-center">
                    <span class="text-xs font-bold text-[var(--text)]">Total Bayar</span>
                    <span id="plan-total" class="text-xl font-extrabold text-[var(--gold)]">-</span>
                </div>
            </div>

            <!-- Informasi Akun Pembeli -->
            <div class="bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl p-4 text-xs space-y-1 mb-4">
                <p class="text-[var(--text-muted)]">Akun Telegram:</p>
                <p id="user-info" class="font-bold text-[var(--text)]">Memuat data pengguna...</p>
            </div>

            <p id="checkout-error" class="hidden text-xs text-[var(--crimson)] text-center mb-2"></p>
        </div>
    </div>

    <!-- Tombol Aksi Pembayaran Midtrans -->
    <div id="pay-action" class="hidden pt-6">
        <button id="pay-button" onclick="payWithMidtrans()"
            class="w-full bg-gradient-to-r from-[var(--gold-soft)] to-[var(--gold)] text-white font-extrabold py-3.5 rounded-xl text-sm shadow-lg active:scale-95 transition disabled:opacity-60">
            Lanjut ke Pembayaran Midtrans
        </button>
        <p class="text-center text-[10px] text-[var(--text-muted)] mt-3">Pembayaran diproses aman oleh Midtrans, langsung di dalam aplikasi ini.</p>
    </div>

    <script>
    const tg = (window.Telegram && window.Telegram.WebApp) ? window.Telegram.WebApp : {
        ready: () => {}, expand: () => {}, close: () => {},
        showAlert: (msg) => alert(msg),
        initDataUnsafe: {}
    };
    tg.ready();
    tg.expand();

    const urlParams = new URLSearchParams(window.location.search);
    const packageId = urlParams.get('package_id');
    const tgUser = tg.initDataUnsafe?.user;

    let currentPackage = null;

    function showState(id) {
        ['loading-state', 'invalid-state', 'outside-tg', 'checkout-content', 'pay-action'].forEach(s => {
            document.getElementById(s).classList.add('hidden');
        });
        document.getElementById(id).classList.remove('hidden');
    }

    async function init() {
        if (!tgUser) {
            showState('outside-tg');
            return;
        }

        if (!packageId) {
            showState('invalid-state');
            return;
        }

        document.getElementById('user-info').textContent =
            `${tgUser.first_name || ''} ${tgUser.last_name || ''} (ID: ${tgUser.id})`.trim();

        try {
            const res = await fetch(`/api/packages/${packageId}`);
            if (!res.ok) throw new Error('not found');
            currentPackage = await res.json();

            const priceFormatted = `Rp ${Number(currentPackage.price).toLocaleString('id-ID')}`;
            document.getElementById('plan-title').textContent = currentPackage.name;
            document.getElementById('plan-duration').textContent = `Akses VIP selama ${currentPackage.duration_days} Hari`;
            document.getElementById('plan-price').textContent = priceFormatted;
            document.getElementById('plan-total').textContent = priceFormatted;

            showState('checkout-content');
            document.getElementById('pay-action').classList.remove('hidden');
        } catch (err) {
            showState('invalid-state');
        }
    }

    async function payWithMidtrans() {
        if (!currentPackage) return;

        const payBtn = document.getElementById('pay-button');
        const errorEl = document.getElementById('checkout-error');
        errorEl.classList.add('hidden');

        payBtn.disabled = true;
        payBtn.textContent = 'Memproses transaksi...';

        try {
            const response = await fetch('/api/payment/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Telegram-Init-Data': tg.initData
                },
                body: JSON.stringify({
                    package_id: currentPackage.id
                })
            });

            const res = await response.json();

            if (res.status !== 'success' || !res.data?.snap_token) {
                throw new Error(res.message || 'Gagal membuat transaksi.');
            }

            payBtn.textContent = 'Menunggu pembayaran...';

            window.snap.pay(res.data.snap_token, {
                onSuccess: function () {
                    tg.showAlert('Pembayaran berhasil! Paket VIP kamu sedang diaktifkan.');
                    window.location.href = '/app';
                },
                onPending: function () {
                    tg.showAlert('Pembayaran kamu sedang diproses. Kami akan kirim konfirmasi lewat bot begitu selesai.');
                    window.location.href = '/app';
                },
                onError: function () {
                    errorEl.textContent = 'Pembayaran gagal. Silakan coba lagi.';
                    errorEl.classList.remove('hidden');
                },
                onClose: function () {
                    errorEl.textContent = 'Kamu menutup jendela pembayaran sebelum selesai.';
                    errorEl.classList.remove('hidden');
                }
            });
        } catch (err) {
            errorEl.textContent = err.message || 'Terjadi kesalahan sistem.';
            errorEl.classList.remove('hidden');
        } finally {
            payBtn.disabled = false;
            payBtn.textContent = 'Lanjut ke Pembayaran Midtrans';
        }
    }

    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
