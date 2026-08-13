<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detail Film - REELGATE</title>

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

        .badge-vip{
            position: relative; overflow: hidden;
            background: linear-gradient(90deg, #8A5B18, var(--gold), #8A5B18);
            color: #1A1408;
        }

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

        .poster-thumb{
            position: relative;
            width: 108px;
            aspect-ratio: 3 / 4;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--hairline);
            background: var(--surface);
            flex-shrink: 0;
        }
        .poster-thumb img{ width: 100%; height: 100%; object-fit: cover; }

        @media (prefers-reduced-motion: reduce){ .skel{ animation: none !important; } }
    </style>
</head>
<body class="min-h-screen pb-10">
    <div class="max-w-md mx-auto">

        <!-- Top Bar Navigasi -->
        <div class="flex items-center gap-3 px-4 pt-4 pb-2">
            <button onclick="window.location.href='/app'" class="w-8 h-8 rounded-full bg-[var(--surface)] border border-[var(--hairline)] flex items-center justify-center text-sm shrink-0">
                ←
            </button>
            <h1 class="text-base font-bold text-white">Detail Film</h1>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="px-4 pt-3">
            <div class="flex gap-4">
                <div class="skel rounded-2xl" style="width:108px; aspect-ratio:3/4"></div>
                <div class="flex-1 space-y-2 pt-1">
                    <div class="h-3 w-16 rounded skel"></div>
                    <div class="h-5 w-4/5 rounded skel"></div>
                    <div class="h-3 w-1/2 rounded skel"></div>
                </div>
            </div>
            <div class="h-11 rounded-xl skel mt-6"></div>
            <div class="h-20 rounded skel mt-6"></div>
        </div>

        <!-- Not Found State -->
        <div id="not-found-state" class="hidden px-4 pt-16 text-center">
            <div class="text-4xl mb-3">🎬</div>
            <p class="font-semibold text-[var(--crimson)] mb-1">Film tidak ditemukan</p>
            <p class="text-xs text-[var(--text-muted)] mb-5">Judul ini mungkin sudah tidak tersedia.</p>
            <button onclick="window.location.href='/app'" class="text-xs bg-[var(--surface)] border border-[var(--hairline)] px-4 py-2 rounded-lg">Kembali ke Katalog</button>
        </div>

        <!-- Outside Telegram State -->
        <div id="outside-tg" class="hidden px-4 pt-16 text-center">
            <div class="text-4xl mb-3">⚠️</div>
            <p class="font-semibold text-[var(--gold-soft)] mb-1">Buka lewat Telegram</p>
            <p class="text-xs text-[var(--text-muted)]">Halaman ini hanya bisa diakses lewat Telegram Mini App.</p>
        </div>

        <!-- Content -->
        <div id="movie-content" class="hidden px-4">

            <!-- Poster kecil + info utama, sejajar -->
            <div class="flex gap-4 pt-2">
                <div class="poster-thumb">
                    <img id="movie-cover" src="" alt="">
                    <span id="movie-vip-badge" class="badge-vip hidden absolute top-1.5 right-1.5 text-[9px] font-bold px-2 py-0.5 rounded-full">VIP</span>
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                    <span id="movie-genre" class="inline-block text-[10px] font-semibold tracking-wide px-2.5 py-1 rounded-full bg-[var(--surface)] border border-[var(--hairline)] text-[var(--gold-soft)] mb-2"></span>
                    <h1 id="movie-title" class="font-display text-xl font-semibold leading-tight mb-1"></h1>
                    <p id="movie-episodes" class="text-xs text-[var(--text-muted)]"></p>
                </div>
            </div>

            <!-- Tombol Aksi: langsung di bawah info, bukan di dasar layar -->
            <div class="mt-5">
                <button id="watch-button" onclick="handleWatchClick()" class="btn-ticket w-full font-bold py-3 rounded-xl text-sm shadow-lg active:scale-95 transition disabled:opacity-60">
                    ▶️ Tonton Sekarang
                </button>
                <p id="watch-error" class="hidden text-xs text-[var(--crimson)] text-center mt-2"></p>
            </div>

            <!-- Notice: perlu berlangganan -->
            <div id="watch-locked-notice" class="hidden rounded-xl p-3.5 mt-3 text-center bg-gradient-to-r from-red-900/30 to-purple-900/30 border border-[var(--crimson)]">
                <p class="text-xs text-[var(--gold-soft)] font-semibold mb-0.5">🔒 Konten Khusus VIP</p>
                <p class="text-[11px] text-[var(--text-muted)]">Berlangganan untuk membuka akses menonton film ini.</p>
            </div>

            <!-- Sinopsis -->
            <div class="mt-6">
                <h2 class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide mb-2">Sinopsis</h2>
                <p id="movie-synopsis" class="text-sm text-[var(--text)] leading-relaxed"></p>
            </div>

            <!-- Daftar Episode (khusus film bertipe series) -->
            <div id="episode-list-wrapper" class="hidden mt-6">
                <h2 class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide mb-2">Daftar Episode</h2>
                <div id="episode-list" class="space-y-2"></div>
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

    const movieId = Number("{{ $id }}");
    const tgUser = tg.initDataUnsafe?.user;
    let isSubscribed = false;

    // Data diambil dari GET /api/movies/{id} — sudah sinkron dengan katalog di /app.
    let currentMovie = null;

    function showState(id){
        ['loading-state', 'not-found-state', 'outside-tg', 'movie-content'].forEach(s => {
            document.getElementById(s).classList.add('hidden');
        });
        document.getElementById(id).classList.remove('hidden');
    }

    function renderMovie(movie){
        document.getElementById('movie-cover').src = movie.cover || 'https://placehold.co/300x400?text=No+Cover';
        document.getElementById('movie-cover').alt = `Poster ${movie.title}`;
        document.getElementById('movie-genre').textContent = movie.genre || 'Drama';
        document.getElementById('movie-title').textContent = movie.title;
        document.getElementById('movie-episodes').textContent = movie.type === 'series'
            ? `${movie.episodes} Episode`
            : 'Single / Film Penuh';
        document.getElementById('movie-synopsis').textContent = movie.synopsis || 'Belum ada sinopsis untuk film ini.';

        renderEpisodeList(movie);
    }

    function renderEpisodeList(movie){
        const wrapper = document.getElementById('episode-list-wrapper');
        const list = document.getElementById('episode-list');

        if (movie.type !== 'series' || !movie.episode_list || movie.episode_list.length === 0) {
            wrapper.classList.add('hidden');
            list.innerHTML = '';
            return;
        }

        list.innerHTML = movie.episode_list.map(ep => `
            <button type="button" onclick="handleWatchClick(${ep.id})"
                class="w-full flex items-center justify-between bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-4 py-3 text-left hover:border-[var(--gold)] transition">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white">Episode ${ep.episode_number}</p>
                    ${ep.title ? `<p class="text-[11px] text-[var(--text-muted)] truncate">${ep.title}</p>` : ''}
                </div>
                <span class="text-[10px] text-[var(--text-muted)]">▶</span>
            </button>
        `).join('');

        wrapper.classList.remove('hidden');
    }

    function applySubscriptionState(){
        const watchBtn = document.getElementById('watch-button');
        const lockedNotice = document.getElementById('watch-locked-notice');
        const vipBadge = document.getElementById('movie-vip-badge');

        if (isSubscribed) {
            lockedNotice.classList.add('hidden');
            vipBadge.classList.add('hidden');
            watchBtn.textContent = '▶️ Tonton Sekarang';
        } else {
            lockedNotice.classList.remove('hidden');
            vipBadge.classList.remove('hidden');
            watchBtn.textContent = '💎 Berlangganan untuk Menonton';
        }
    }

    async function handleWatchClick(episodeId){
        if (!isSubscribed) {
            window.location.href = '/app?tab=packages';
            return;
        }

        const watchBtn = document.getElementById('watch-button');
        const errorEl = document.getElementById('watch-error');
        errorEl.classList.add('hidden');

        watchBtn.disabled = true;
        watchBtn.textContent = 'Mengirim ke chat Telegram-mu...';

        try {
            const res = await fetch(`/api/movies/${movieId}/watch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Telegram-Init-Data': tg.initData
                },
                body: JSON.stringify(episodeId ? { episode_id: episodeId } : {})
            });

            const result = await res.json();

            if (!res.ok) throw new Error(result.message || 'Gagal mengirim video.');

            // Setelah video pertama terkirim, episode selanjutnya bisa langsung diakses
            // lewat tombol "▶️ Episode Selanjutnya" di bawah video dalam chat Telegram —
            // jadi user tidak perlu balik ke TWA setiap ganti episode.
            tg.showAlert('Video sudah dikirim ke chat Telegram-mu! Buka chat bot untuk mulai nonton — episode selanjutnya bisa langsung dari tombol di bawah video.');
        } catch (err) {
            errorEl.textContent = err.message || 'Gagal mengirim video, coba lagi ya.';
            errorEl.classList.remove('hidden');
        } finally {
            watchBtn.disabled = false;
            watchBtn.textContent = '▶️ Tonton Sekarang';
        }
    }

    async function init(){
        if (!tgUser) {
            showState('outside-tg');
            return;
        }

        try {
            const res = await fetch(`/api/movies/${movieId}`);
            if (!res.ok) {
                showState('not-found-state');
                return;
            }
            currentMovie = await res.json();
        } catch (err) {
            showState('not-found-state');
            return;
        }

        renderMovie(currentMovie);

        try {
            const res = await fetch(`/api/user/status`, {
                headers: { 'X-Telegram-Init-Data': tg.initData }
            });
            const result = await res.json();
            isSubscribed = !!result.is_subscribed;
        } catch (err) {
            isSubscribed = false;
        }

        applySubscriptionState();
        showState('movie-content');
    }

    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
