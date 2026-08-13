<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Request Film - REELGATE</title>

    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg: #0B0910; --surface: #16131F; --surface-2: #1E1930;
            --gold: #E8B156; --gold-soft: #F3D08A; --crimson: #C2355A;
            --text: #EDE9F5; --text-muted: #9C93AF; --hairline: rgba(232,177,86,0.16);
        }
        html, body { max-width: 100vw; overflow-x: hidden !important; }
        body{
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-display{ font-family: 'Fraunces', serif; }

        .btn-ticket{
            position: relative;
            background: linear-gradient(180deg, var(--gold-soft), var(--gold));
            color: #241705;
        }
        .btn-ticket::before, .btn-ticket::after{
            content:"";
            position:absolute; top:50%; transform: translateY(-50%);
            width: 14px; height: 14px; border-radius: 999px;
            background: var(--bg);
        }
        .btn-ticket::before{ left: -7px; }
        .btn-ticket::after{ right: -7px; }

        .skel{
            background: linear-gradient(100deg, var(--surface) 30%, var(--surface-2) 45%, var(--surface) 60%);
            background-size: 200% 100%;
            animation: shimmerSkel 1.4s ease-in-out infinite;
        }
        @keyframes shimmerSkel{ from{ background-position: 140% 0; } to{ background-position: -40% 0; } }

        .field{
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
            color: var(--text);
        }
        .field:focus{ outline: none; border-color: var(--gold); }
        .field::placeholder{ color: var(--text-muted); }

        select.field{
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239C93AF'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 36px;
        }

        @media (prefers-reduced-motion: reduce){ .skel{ animation: none !important; } }

        .status-badge{
            font-size: 10px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .status-pending{ background: rgba(232,177,86,0.15); color: var(--gold-soft); border: 1px solid rgba(232,177,86,0.3); }
        .status-approved{ background: rgba(74,222,128,0.15); color: #4ADE80; border: 1px solid rgba(74,222,128,0.3); }
        .status-rejected{ background: rgba(194,53,90,0.15); color: #F27C97; border: 1px solid rgba(194,53,90,0.3); }
    </style>
</head>
<body class="min-h-screen pb-10">
    <div class="max-w-md mx-auto">

        <!-- Top Bar Navigasi -->
        <div class="flex items-center gap-3 px-4 pt-4 pb-2">
            <button onclick="window.location.href='/app'" class="w-8 h-8 rounded-full bg-[var(--surface)] border border-[var(--hairline)] flex items-center justify-center text-sm shrink-0">
                ←
            </button>
            <h1 class="text-base font-bold text-white">Request Film</h1>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="px-4 pt-3">
            <div class="h-4 w-2/3 rounded skel mb-3"></div>
            <div class="h-11 rounded-xl skel mb-3"></div>
            <div class="h-11 rounded-xl skel mb-3"></div>
            <div class="h-11 rounded-xl skel"></div>
        </div>

        <!-- Outside Telegram State -->
        <div id="outside-tg" class="hidden px-4 pt-16 text-center">
            <div class="text-4xl mb-3">⚠️</div>
            <p class="font-semibold text-[var(--gold-soft)] mb-1">Buka lewat Telegram</p>
            <p class="text-xs text-[var(--text-muted)]">Halaman ini hanya bisa diakses lewat Telegram Mini App.</p>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden px-4 pt-16 text-center">
            <div class="text-4xl mb-3">📽️</div>
            <p class="text-[var(--crimson)] font-semibold mb-1">Gagal terhubung ke server</p>
            <p class="text-xs text-[var(--text-muted)]">Silakan coba lagi nanti.</p>
        </div>

        <!-- Content -->
        <div id="page-content" class="hidden px-4">

            <p class="text-xs text-[var(--text-muted)] mb-5 leading-relaxed">
                Tidak menemukan judul yang kamu cari di katalog kami? Kirim permintaanmu di sini, tim kami akan mengusahakan menambahkannya secepat mungkin.
            </p>

            <!-- Notice: khusus pelanggan -->
            <div id="locked-notice" class="hidden rounded-xl p-4 mb-5 text-center bg-gradient-to-r from-red-900/30 to-purple-900/30 border border-[var(--crimson)]">
                <p class="text-xs text-[var(--gold-soft)] font-semibold mb-1">🔒 Fitur Khusus VIP</p>
                <p class="text-[11px] text-[var(--text-muted)] mb-3">Request judul film hanya tersedia untuk pelanggan aktif. Berlangganan sekarang untuk mengakses fitur ini.</p>
                <button onclick="window.location.href='/app?tab=packages'" class="text-xs bg-[var(--gold)] text-black font-bold px-4 py-1.5 rounded-lg">Beli Paket VIP</button>
            </div>

            <!-- Form Request -->
            <form id="request-form" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Judul Film / Drama</label>
                    <input type="text" id="movie-title" required maxlength="255"
                        class="field" placeholder="Contoh: Cinta di Ujung Senja">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Asal Aplikasi</label>
                    <select id="movie-source" required class="field">
                        <option value="" disabled selected>Pilih aplikasi asal film</option>
                        <option value="Netflix">Netflix</option>
                        <option value="Viu">Viu</option>
                        <option value="WeTV">WeTV</option>
                        <option value="iQIYI">iQIYI</option>
                        <option value="Disney+ Hotstar">Disney+ Hotstar</option>
                        <option value="Prime Video">Prime Video</option>
                        <option value="Vidio">Vidio</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <button type="submit" id="submit-btn" class="btn-ticket w-full font-bold py-3 rounded-xl text-sm shadow-lg active:scale-95 transition disabled:opacity-60">
                    Kirim Request
                </button>

                <p id="form-message" class="hidden text-xs text-center mt-1"></p>
            </form>

            <!-- Riwayat Request -->
            <div class="mt-8">
                <h2 class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide mb-3">Riwayat Request</h2>
                <div id="history-list" class="space-y-2.5">
                    <!-- diisi oleh JS -->
                </div>
            </div>
        </div>
    </div>

    <script>
    const tg = (window.Telegram && window.Telegram.WebApp) ? window.Telegram.WebApp : {
        ready: () => {}, expand: () => {}, close: () => {},
        showAlert: (msg) => alert(msg),
        initDataUnsafe: {}
    };
    tg.ready();
    tg.expand();

    const tgUser = tg.initDataUnsafe?.user;
    let isSubscribed = false;

    function showState(id){
        ['loading-state', 'outside-tg', 'error-state', 'page-content'].forEach(s => {
            document.getElementById(s).classList.add('hidden');
        });
        document.getElementById(id).classList.remove('hidden');
    }

    function applySubscriptionState(){
        const lockedNotice = document.getElementById('locked-notice');
        const form = document.getElementById('request-form');

        if (isSubscribed) {
            lockedNotice.classList.add('hidden');
            form.classList.remove('hidden');
        } else {
            lockedNotice.classList.remove('hidden');
            form.classList.add('hidden');
        }
    }

    const statusMap = {
        PENDING: { label: 'Menunggu', cls: 'status-pending' },
        APPROVED: { label: 'Disetujui', cls: 'status-approved' },
        REJECTED: { label: 'Ditolak', cls: 'status-rejected' },
    };

    function formatDate(dateStr){
        try {
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch (e) {
            return dateStr;
        }
    }

    function renderHistory(items){
        const list = document.getElementById('history-list');

        if (!items.length) {
            list.innerHTML = `
                <div class="text-center py-6 border border-dashed border-[var(--hairline)] rounded-xl">
                    <div class="text-2xl mb-1.5">📭</div>
                    <p class="text-xs text-[var(--text-muted)]">Kamu belum pernah melakukan request film.</p>
                </div>
            `;
            return;
        }

        list.innerHTML = items.map(item => {
            const status = statusMap[item.status] || statusMap.PENDING;
            return `
                <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl p-3.5 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[var(--text)] truncate">${item.movie_title}</p>
                        <p class="text-[11px] text-[var(--text-muted)] mt-0.5">${item.source} • ${formatDate(item.created_at)}</p>
                    </div>
                    <span class="status-badge ${status.cls} shrink-0">${status.label}</span>
                </div>
            `;
        }).join('');
    }

    async function loadHistory(){
        const list = document.getElementById('history-list');
        list.innerHTML = `
            <div class="h-14 rounded-xl skel"></div>
            <div class="h-14 rounded-xl skel"></div>
        `;

        try {
            const res = await fetch(`/api/movie-requests`, {
                headers: { 'X-Telegram-Init-Data': tg.initData }
            });
            const result = await res.json();

            if (!res.ok) {
                throw new Error(result.message || `HTTP ${res.status}`);
            }

            renderHistory(result.data || []);
        } catch (err) {
            console.error('Gagal memuat riwayat request:', err);
            list.innerHTML = `<p class="text-xs text-[var(--crimson)] text-center py-4">Gagal memuat riwayat. Coba refresh halaman ini.</p>`;
        }
    }

    async function handleSubmit(e){
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        const messageEl = document.getElementById('form-message');
        const movieTitle = document.getElementById('movie-title').value.trim();
        const movieSource = document.getElementById('movie-source').value;

        messageEl.classList.add('hidden');

        if (!movieTitle || !movieSource) return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengirim...';

        try {
            const res = await fetch('/api/movie-requests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Telegram-Init-Data': tg.initData
                },
                body: JSON.stringify({
                    movie_title: movieTitle,
                    source: movieSource,
                })
            });

            const result = await res.json();

            if (!res.ok) {
                throw new Error(result.message || 'Gagal mengirim request.');
            }

            messageEl.textContent = '✅ Request berhasil dikirim, terima kasih!';
            messageEl.className = 'text-xs text-center mt-1 text-[var(--gold-soft)]';
            messageEl.classList.remove('hidden');
            document.getElementById('request-form').reset();
            tg.showAlert('Request film berhasil dikirim, terima kasih!');
            loadHistory();
        } catch (err) {
            messageEl.textContent = err.message || 'Terjadi kesalahan, coba lagi nanti.';
            messageEl.className = 'text-xs text-center mt-1 text-[var(--crimson)]';
            messageEl.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Kirim Request';
        }
    }

    async function init(){
        if (!tgUser) {
            showState('outside-tg');
            return;
        }

        try {
            const res = await fetch(`/api/user/status`, {
                headers: { 'X-Telegram-Init-Data': tg.initData }
            });
            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
            const result = await res.json();
            isSubscribed = !!result.is_subscribed;

            applySubscriptionState();
            showState('page-content');
            loadHistory();

            document.getElementById('request-form').addEventListener('submit', handleSubmit);
        } catch (err) {
            console.error('Gagal memeriksa status:', err);
            showState('error-state');
        }
    }

    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
