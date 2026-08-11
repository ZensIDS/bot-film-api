<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ruang Drama - NiceDramaBot</title>

    <!-- ⚡ SDK Resmi Telegram Web App -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Typefaces: Fraunces + Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg: #0B0910;
            --surface: #16131F;
            --surface-2: #1E1930;
            --gold: #E8B156;
            --gold-soft: #F3D08A;
            --crimson: #C2355A;
            --text: #EDE9F5;
            --text-muted: #9C93AF;
            --hairline: rgba(232,177,86,0.16);
        }

        html, body {
            max-width: 100vw;
            overflow-x: hidden !important;
            position: relative;
        }

        body{
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-image:
                radial-gradient(ellipse 60% 40% at 50% -10%, rgba(194,53,90,0.20), transparent 60%),
                radial-gradient(ellipse 50% 35% at 90% 10%, rgba(232,177,86,0.10), transparent 60%);
        }

        .font-display{ font-family: 'Fraunces', serif; }

        /* --- Marquee Text Animation --- */
        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .marquee-content {
            display: inline-block;
            animation: marquee 18s linear infinite;
        }
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* --- Spotlight Header --- */
        .spotlight{
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            padding: 4px;
        }
        .spotlight::before{
            content: "";
            position: absolute;
            inset: -20% 0 auto 0;
            height: 180px;
            background: conic-gradient(from 200deg at 50% 0%, transparent, rgba(232,177,86,0.25), transparent 40%);
            filter: blur(8px);
            pointer-events: none;
            animation: sweep 6s ease-in-out infinite;
        }
        @keyframes sweep{
            0%, 100%{ transform: translateX(-4%) rotate(-1deg); opacity: .6; }
            50%{ transform: translateX(4%) rotate(1deg); opacity: 0.9; }
        }

        /* --- Poster Card --- */
        .poster{
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            transition: transform .35s ease, border-color .35s ease;
        }
        .poster:active{ transform: scale(0.97); }
        .poster__frame{ position: relative; aspect-ratio: 3 / 4; overflow: hidden; background-color: var(--surface-2); }
        .poster__frame img{
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .5s ease, filter .5s ease;
        }
        .poster:hover .poster__frame img{ transform: scale(1.06); }
        .poster__frame::after{
            content:"";
            position:absolute; inset:0;
            background: linear-gradient(180deg, transparent 40%, rgba(11,9,16,0.92) 100%);
        }
        .poster__badge{
            position:absolute; top:8px; left:8px; z-index:2;
            font-size: 10px; letter-spacing:.06em; font-weight:700;
            padding: 3px 8px; border-radius: 999px;
            background: rgba(11,9,16,0.7); border:1px solid var(--hairline);
            color: var(--gold-soft);
            backdrop-filter: blur(4px);
        }
        .poster__meta{
            position:absolute; left:0; right:0; bottom:0; z-index:2;
            padding: 10px 12px;
        }

        /* --- VIP Shimmer Badge --- */
        .badge-vip{
            position: relative;
            overflow: hidden;
            background: linear-gradient(90deg, #8A5B18, var(--gold), #8A5B18);
            color: #1A1408;
        }
        .badge-vip::after{
            content:"";
            position:absolute; top:0; bottom:0; left:-60%;
            width: 40%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.65), transparent);
            transform: skewX(-20deg);
            animation: shimmer 2.6s ease-in-out infinite;
        }
        @keyframes shimmer{
            0%{ left: -60%; }
            60%, 100%{ left: 140%; }
        }

        /* --- Ticket-style CTA Button --- */
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

        /* --- Skeleton Loading --- */
        .skel{
            background: linear-gradient(100deg, var(--surface) 30%, var(--surface-2) 45%, var(--surface) 60%);
            background-size: 200% 100%;
            animation: shimmerSkel 1.4s ease-in-out infinite;
        }
        @keyframes shimmerSkel{
            from{ background-position: 140% 0; }
            to{ background-position: -40% 0; }
        }

        /* --- Nav Footer Styling --- */
        .nav-tab.active {
            color: var(--gold);
        }
        .nav-tab.active svg {
            stroke: var(--gold);
        }

        @media (prefers-reduced-motion: reduce){
            .spotlight::before, .badge-vip::after, .skel, .marquee-content{ animation: none !important; }
        }
    </style>
</head>
<body class="min-h-screen pb-24 overflow-x-hidden">
    <div id="app" class="max-w-md mx-auto relative overflow-hidden">

        <!-- Loading State -->
        <div id="loading" class="px-4 pt-6">
            <div class="h-6 w-40 rounded skel mb-2"></div>
            <div class="h-3 w-24 rounded skel mb-8"></div>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl skel" style="aspect-ratio:3/4"></div>
                <div class="rounded-2xl skel" style="aspect-ratio:3/4"></div>
                <div class="rounded-2xl skel" style="aspect-ratio:3/4"></div>
                <div class="rounded-2xl skel" style="aspect-ratio:3/4"></div>
            </div>
        </div>

        <!-- APP CONTAINER -->
        <div id="main-app" class="hidden">

            <!-- 1. TAB BERANDA / HOME -->
            <div id="tab-home" class="tab-content pt-3">
                <div class="px-4">

                    <!-- MARQUEE RUNNING TEXT -->
                    <div class="mb-4 bg-[var(--surface)] border border-[var(--hairline)] rounded-xl py-2 px-3 flex items-center gap-2">
                        <span class="text-xs">📢</span>
                        <div class="marquee-container flex-1 text-xs text-[var(--gold-soft)] font-medium">
                            <div class="marquee-content">
                                Selamat datang di NiceDramaBot! Nikmati pembaruan episode drama favorit Anda setiap harinya. Gunakan paket VIP untuk bebas iklan dan streaming tanpa batas.
                            </div>
                        </div>
                    </div>

                    <!-- Spotlight Header -->
                    <div class="spotlight flex items-start justify-between mb-4">
                        <div class="relative z-10">
                            <p id="greeting" class="text-xs text-[var(--text-muted)] mb-1">Selamat datang kembali</p>
                            <h1 class="font-display text-3xl font-semibold tracking-tight">Ruang Drama</h1>
                        </div>
                        <span id="vip-badge-home" class="badge-vip text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap mt-1 relative z-10">VIP AKTIF</span>
                    </div>

                    <!-- Banner Promo: Request Film -->
                    <button onclick="window.location.href='/request-film'" class="w-full flex items-center gap-3 mb-4 rounded-xl p-3.5 text-left bg-gradient-to-r from-[var(--surface-2)] to-[var(--surface)] border border-[var(--gold)]/40 active:scale-[0.98] transition">
                        <span class="w-9 h-9 rounded-full bg-[var(--gold)]/15 flex items-center justify-center text-lg shrink-0">🎬</span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-xs font-bold text-[var(--gold-soft)]">Tidak nemu judulnya?</span>
                            <span class="block text-[11px] text-[var(--text-muted)]">Request film favoritmu di sini</span>
                        </span>
                        <span class="text-[var(--gold)] text-sm shrink-0">→</span>
                    </button>

                    <!-- Search Box & Filter Genre -->
                    <div class="space-y-3 mb-6">
                        <div class="relative">
                            <input type="text" id="search-input" oninput="handleSearchFilter()" placeholder="Cari judul drama..."
                                class="w-full bg-[var(--surface)] border border-[var(--hairline)] rounded-xl py-2.5 pl-10 pr-4 text-sm text-[var(--text)] placeholder-[var(--text-muted)] focus:outline-none focus:border-[var(--gold)] transition">
                            <svg class="w-4 h-4 absolute left-3.5 top-3.5 stroke-[var(--text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <!-- Filter Chips -->
                        <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar text-xs">
                            <button onclick="setGenreFilter('Semua', this)" class="genre-chip px-3.5 py-1.5 rounded-full bg-[var(--gold)] text-black font-semibold whitespace-nowrap transition">Semua</button>
                            <button onclick="setGenreFilter('Romansa', this)" class="genre-chip px-3.5 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--hairline)] text-[var(--text-muted)] whitespace-nowrap transition">Romansa</button>
                            <button onclick="setGenreFilter('Misteri', this)" class="genre-chip px-3.5 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--hairline)] text-[var(--text-muted)] whitespace-nowrap transition">Misteri</button>
                            <button onclick="setGenreFilter('Drama Kerajaan', this)" class="genre-chip px-3.5 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--hairline)] text-[var(--text-muted)] whitespace-nowrap transition">Kerajaan</button>
                            <button onclick="setGenreFilter('Keluarga', this)" class="genre-chip px-3.5 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--hairline)] text-[var(--text-muted)] whitespace-nowrap transition">Keluarga</button>
                        </div>
                    </div>

                    <!-- Lock Paywall Banner -->
                    <div id="home-paywall-notice" class="hidden rounded-xl p-4 mb-6 text-center bg-gradient-to-r from-red-900/30 to-purple-900/30 border border-[var(--crimson)]">
                        <p class="text-xs text-[var(--gold-soft)] font-semibold mb-1">🔒 Mode Pratinjau Terbatas</p>
                        <p class="text-[11px] text-[var(--text-muted)] mb-3">Berlangganan sekarang untuk membuka kunci streaming full episode HD.</p>
                        <button onclick="switchTab('packages')" class="text-xs bg-[var(--gold)] text-black font-bold px-4 py-1.5 rounded-lg">Beli Paket VIP</button>
                    </div>

                    <div class="grid grid-cols-2 gap-4" id="drama-list"><!-- diisi oleh JS --></div>
                </div>
            </div>

            <!-- 2. TAB PAKET LANGGANAN -->
            <div id="tab-packages" class="tab-content hidden px-4 pt-6">
                <div class="text-center mb-6">
                    <h2 class="font-display text-2xl font-semibold mb-1">Pilih Paket Streaming</h2>
                    <p class="text-xs text-[var(--text-muted)]">Akses tanpa batas ke seluruh koleksi drama eksklusif</p>
                </div>

                <div class="space-y-4" id="packages-list">
                    <div class="rounded-2xl skel" style="height: 190px"></div>
                    <div class="rounded-2xl skel" style="height: 190px"></div>
                </div>
            </div>

            <!-- 3. TAB PROFIL AKUN -->
            <div id="tab-profile" class="tab-content hidden px-4 pt-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 mx-auto rounded-full bg-[var(--surface-2)] border-2 border-[var(--gold)] flex items-center justify-center text-3xl font-bold text-[var(--gold)] mb-3" id="profile-avatar">
                        👤
                    </div>
                    <h2 class="font-display text-xl font-semibold" id="profile-name">Pengguna Telegram</h2>
                    <p class="text-xs text-[var(--text-muted)]" id="profile-id">ID: -</p>
                </div>

                <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5 mb-6">
                    <h3 class="font-display text-sm font-semibold text-[var(--gold-soft)] mb-4 border-b border-[var(--hairline)] pb-2">Status Langganan</h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between">
                            <span class="text-[var(--text-muted)]">Status Paket:</span>
                            <span id="profile-status" class="font-bold text-red-400">Tidak Aktif</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--text-muted)]">Berlaku Sampai:</span>
                            <span id="profile-expired" class="font-semibold text-[var(--text)]">-</span>
                        </div>
                    </div>
                </div>

                <button onclick="switchTab('packages')" class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] text-[var(--gold-soft)] font-semibold py-3 rounded-xl text-xs flex items-center justify-center gap-2">
                    <span>💎</span> Kelola / Perpanjang Langganan
                </button>
            </div>

        </div>

        <!-- Tampilan Error & Outside TG -->
        <div id="error-state" class="hidden px-4 pt-10 text-center">
            <div class="text-4xl mb-3">📽️</div>
            <p class="text-[var(--crimson)] font-semibold mb-1">Gagal terhubung ke server</p>
            <p class="text-xs text-[var(--text-muted)]">Silakan coba lagi nanti.</p>
        </div>

        <div id="outside-tg" class="hidden px-4 pt-10 text-center">
            <div class="text-4xl mb-3">⚠️</div>
            <p class="text-[var(--gold-soft)] font-semibold mb-1">Buka lewat Telegram</p>
            <p class="text-xs text-[var(--text-muted)]">Aplikasi ini harus diakses melalui Telegram Mini App.</p>
        </div>

        <!-- FOOTER NAVIGATION BAR -->
        <nav id="footer-nav" class="hidden fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-[var(--surface)]/90 backdrop-blur-md border-t border-[var(--hairline)] px-6 py-2 flex justify-between items-center z-50">
            <button onclick="switchTab('home')" id="nav-home" class="nav-tab active flex flex-col items-center gap-1 text-[10px] font-medium text-[var(--text-muted)] transition">
                <svg class="w-5 h-5 stroke-[var(--text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 001 1m-6 0h6"/>
                </svg>
                Beranda
            </button>
            <button onclick="switchTab('packages')" id="nav-packages" class="nav-tab flex flex-col items-center gap-1 text-[10px] font-medium text-[var(--text-muted)] transition">
                <svg class="w-5 h-5 stroke-[var(--text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z"/>
                </svg>
                Paket
            </button>
            <button onclick="window.location.href='/request-film'" id="nav-request" class="nav-tab flex flex-col items-center gap-1 text-[10px] font-medium text-[var(--text-muted)] transition">
                <svg class="w-5 h-5 stroke-[var(--text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Request
            </button>
            <button onclick="switchTab('profile')" id="nav-profile" class="nav-tab flex flex-col items-center gap-1 text-[10px] font-medium text-[var(--text-muted)] transition">
                <svg class="w-5 h-5 stroke-[var(--text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profil
            </button>
        </nav>

    </div>

    <script>
    const tg = (window.Telegram && window.Telegram.WebApp) ? window.Telegram.WebApp : {
        ready: () => {},
        expand: () => {},
        close: () => {},
        openInvoice: () => {},
        openLink: () => {},
        showAlert: (msg) => alert(msg),
        initDataUnsafe: {}
    };
    tg.ready();
    tg.expand();

    // TODO (Hari 8-9): ganti data statis ini dengan hasil fetch ke GET /api/movies
    const demoDramas = [
        { id: 1, title: "Cinta di Ujung Senja", episodes: 16, genre: "Romansa", cover: "https://picsum.photos/seed/drama1/300/400", synopsis: "Setelah bertahun-tahun berpisah karena kesalahpahaman keluarga, Alya dan Raka dipertemukan kembali di sebuah kota kecil tepi pantai. Di antara senja yang sama, mereka harus memilih antara melanjutkan hidup masing-masing atau memberi cinta lama kesempatan kedua." },
        { id: 2, title: "Rahasia Kota Lama", episodes: 20, genre: "Misteri", cover: "https://picsum.photos/seed/drama2/300/400", synopsis: "Seorang jurnalis muda kembali ke kota kelahirannya untuk menyelidiki kematian misterius kakeknya. Setiap petunjuk yang ia temukan justru membongkar rahasia kelam yang selama ini disembunyikan seluruh kota." },
        { id: 3, title: "Pewaris Takhta", episodes: 24, genre: "Drama Kerajaan", cover: "https://picsum.photos/seed/drama3/300/400", synopsis: "Perebutan takhta antar saudara memaksa Putri Wulan menempuh jalan yang tak pernah ia bayangkan: menyamar sebagai rakyat biasa untuk mengungkap konspirasi di dalam istananya sendiri." },
        { id: 4, title: "Jalan Pulang", episodes: 12, genre: "Keluarga", cover: "https://picsum.photos/seed/drama4/300/400", synopsis: "Setelah 15 tahun merantau, Dimas pulang ke desanya membawa satu rahasia besar. Kisah hangat tentang keluarga, pengampunan, dan arti sesungguhnya dari kata 'rumah'." }
    ];

    let currentGenre = 'Semua';

    function goToMovie(id){
        window.location.href = `/movie/${id}`;
    }

    function renderDramaList(dramas){
        const list = document.getElementById('drama-list');
        if(dramas.length === 0){
            list.innerHTML = `<p class="col-span-2 text-center text-xs text-[var(--text-muted)] py-8">Drama tidak ditemukan.</p>`;
            return;
        }
        list.innerHTML = dramas.map(d => `
            <div class="poster" onclick="goToMovie(${d.id})" role="button" tabindex="0">
                <div class="poster__frame">
                    <img src="${d.cover}" alt="Poster ${d.title}" loading="lazy">
                    <span class="poster__badge">${d.genre}</span>
                    <div class="poster__meta">
                        <h3 class="font-display text-sm font-semibold leading-tight truncate">${d.title}</h3>
                        <p class="text-[11px] text-[var(--text-muted)] mt-0.5">${d.episodes} Episode</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function handleSearchFilter(){
        const query = document.getElementById('search-input').value.toLowerCase();
        const filtered = demoDramas.filter(d => {
            const matchSearch = d.title.toLowerCase().includes(query);
            const matchGenre = currentGenre === 'Semua' || d.genre === currentGenre;
            return matchSearch && matchGenre;
        });
        renderDramaList(filtered);
    }

    function setGenreFilter(genre, btnElement){
        currentGenre = genre;
        document.querySelectorAll('.genre-chip').forEach(btn => {
            btn.className = "genre-chip px-3.5 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--hairline)] text-[var(--text-muted)] whitespace-nowrap transition";
        });
        btnElement.className = "genre-chip px-3.5 py-1.5 rounded-full bg-[var(--gold)] text-black font-semibold whitespace-nowrap transition";
        handleSearchFilter();
    }

    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.nav-tab').forEach(el => {
            el.classList.remove('active');
            el.querySelector('svg').style.stroke = "var(--text-muted)";
        });

        document.getElementById(`tab-${tabName}`).classList.remove('hidden');
        const activeNav = document.getElementById(`nav-${tabName}`);
        activeNav.classList.add('active');
        activeNav.querySelector('svg').style.stroke = "var(--gold)";
    }

    /* --- PAKET LANGGANAN: ambil dari API, arahkan ke halaman /checkout terpisah --- */
    function renderPackages(packages) {
        const list = document.getElementById('packages-list');

        if (!packages.length) {
            list.innerHTML = `<p class="text-center text-xs text-[var(--text-muted)] py-8">Belum ada paket tersedia saat ini.</p>`;
            return;
        }

        list.innerHTML = packages.map((pkg, index) => {
            const isFeatured = index === packages.length - 1 && packages.length > 1; // tandai paket termahal sebagai "Paling Laris"
            const priceFormatted = `Rp ${Number(pkg.price).toLocaleString('id-ID')}`;

            return `
                <div class="${isFeatured ? 'bg-[var(--surface-2)] border-2 border-[var(--gold)]' : 'bg-[var(--surface)] border border-[var(--hairline)]'} rounded-2xl p-5 relative overflow-hidden">
                    ${isFeatured ? '<span class="absolute top-0 right-0 bg-[var(--gold)] text-black font-bold text-[9px] px-3 py-1 rounded-bl-xl uppercase tracking-wider">Paling Laris</span>' : ''}
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-display text-lg font-semibold ${isFeatured ? 'text-[var(--gold)]' : 'text-[var(--gold-soft)]'}">${pkg.name}</h3>
                            <p class="text-xs text-[var(--text-muted)]">Akses VIP selama ${pkg.duration_days} Hari</p>
                        </div>
                        <span class="text-xl font-bold ${isFeatured ? 'text-[var(--gold)]' : 'text-white'}">${priceFormatted}</span>
                    </div>
                    <button onclick="goToCheckout(${pkg.id})" class="btn-ticket w-full font-bold py-2.5 rounded-xl text-xs mt-4">Beli ${pkg.name}</button>
                </div>
            `;
        }).join('');
    }

    async function fetchPackages() {
        try {
            const res = await fetch('/api/packages');
            const packages = await res.json();
            renderPackages(packages);
        } catch (err) {
            console.error('Gagal memuat paket:', err);
            document.getElementById('packages-list').innerHTML =
                `<p class="text-center text-xs text-[var(--crimson)] py-8">Gagal memuat paket. Coba lagi nanti.</p>`;
        }
    }

    function goToCheckout(packageId) {
        // Navigasi di dalam webview TWA yang sama, tetap di dalam Telegram.
        window.location.href = `/checkout?package_id=${packageId}`;
    }

    document.addEventListener("DOMContentLoaded", async () => {
        const loadingEl = document.getElementById('loading');
        const mainAppEl = document.getElementById('main-app');
        const footerNavEl = document.getElementById('footer-nav');
        const errorEl = document.getElementById('error-state');
        const outsideEl = document.getElementById('outside-tg');

        const tgUser = tg.initDataUnsafe?.user;

        if (!tgUser) {
            loadingEl.classList.add('hidden');
            outsideEl.classList.remove('hidden');
            return;
        }

        if (tgUser.first_name) {
            document.getElementById('greeting').textContent = `Halo, ${tgUser.first_name} 👋`;
            document.getElementById('profile-name').textContent = `${tgUser.first_name} ${tgUser.last_name || ''}`;
            document.getElementById('profile-avatar').textContent = tgUser.first_name.charAt(0).toUpperCase();
        }
        document.getElementById('profile-id').textContent = `ID: ${tgUser.id}`;

        try {
            const response = await fetch(`/api/user/status?telegram_id=${tgUser.id}`);
            if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

            const result = await response.json();
            loadingEl.classList.add('hidden');
            mainAppEl.classList.remove('hidden');
            footerNavEl.classList.remove('hidden');

            renderDramaList(demoDramas);
            fetchPackages();

            const requestedTab = new URLSearchParams(window.location.search).get('tab');
            if (requestedTab === 'packages') {
                switchTab('packages');
            }

            if (result.is_subscribed) {
                document.getElementById('vip-badge-home').classList.remove('hidden');
                document.getElementById('home-paywall-notice').classList.add('hidden');
                document.getElementById('profile-status').textContent = "VIP AKTIF 💎";
                document.getElementById('profile-status').className = "font-bold text-[var(--gold)]";
                document.getElementById('profile-expired').textContent = result.user?.expired_at || "Aktif";
            } else {
                document.getElementById('vip-badge-home').classList.add('hidden');
                document.getElementById('home-paywall-notice').classList.remove('hidden');
                document.getElementById('profile-status').textContent = "Belum Langganan / Expired";
                document.getElementById('profile-status').className = "font-bold text-red-400";
                document.getElementById('profile-expired').textContent = "-";
            }
        } catch (error) {
            console.error("Gagal memeriksa status:", error);
            loadingEl.classList.add('hidden');
            errorEl.classList.remove('hidden');
        }
    });
    </script>
</body>
</html>
