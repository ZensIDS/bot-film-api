<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin') - NiceDramaBot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg: #0B0910; --surface: #16131F; --surface-2: #1E1930;
            --gold: #E8B156; --gold-soft: #F3D08A; --crimson: #C2355A;
            --text: #EDE9F5; --text-muted: #9C93AF; --hairline: rgba(232,177,86,0.16);
        }
        body{ background-color: var(--bg); color: var(--text); font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display{ font-family: 'Fraunces', serif; }

        .sidebar-link{ display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; color: var(--text-muted); font-size: 13px; font-weight: 600; transition: all .15s; }
        .sidebar-link:hover{ background: var(--surface-2); color: var(--text); }
        .sidebar-link.active{ background: linear-gradient(90deg, rgba(232,177,86,0.16), transparent); color: var(--gold-soft); border-left: 3px solid var(--gold); padding-left: 11px; }

        .btn-gold{ background: linear-gradient(180deg, var(--gold-soft), var(--gold)); color: #241705; }
        .btn-gold:hover{ filter: brightness(1.05); }

        ::-webkit-scrollbar{ width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb{ background: var(--surface-2); border-radius: 999px; }
    </style>

    @yield('extra_css')
</head>
<body class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="hidden md:flex w-60 shrink-0 flex-col bg-[var(--surface)] border-r border-[var(--hairline)] min-h-screen sticky top-0">
        <div class="px-5 py-5 border-b border-[var(--hairline)]">
            <h1 class="font-display text-lg font-semibold text-white">
                Nice<span style="color:var(--gold)">Drama</span>Bot
            </h1>
            <p class="text-[10px] text-[var(--text-muted)] uppercase tracking-wide mt-0.5">Admin Panel</p>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span>🏠</span> Dashboard
            </a>
            <a href="{{ route('admin.movies.index') }}" class="sidebar-link {{ request()->routeIs('admin.movies.*') ? 'active' : '' }}">
                <span>🎬</span> Manajemen Film
            </a>
            <a href="{{ route('admin.packages.index') }}" class="sidebar-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                <span>💎</span> Paket & Harga
            </a>
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <span>👤</span> User & Langganan
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                <span>💳</span> Riwayat Transaksi
            </a>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.movie-requests.*') ? 'active' : '' }}">
                <span>📥</span> Request Film
            </a>
        </nav>

        <div class="px-3 py-4 border-t border-[var(--hairline)]">
            <div class="flex items-center gap-2.5 px-2 mb-3">
                <div class="w-8 h-8 rounded-full bg-[var(--surface-2)] flex items-center justify-center text-xs font-bold text-[var(--gold-soft)]">
                    {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ Auth::guard('admin')->user()->name }}</p>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-xs font-semibold text-[var(--text-muted)] hover:text-[var(--crimson)] transition text-left px-2 py-1.5">
                    ⎋ Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 min-w-0">
        <header class="md:hidden flex items-center justify-between px-4 py-3 bg-[var(--surface)] border-b border-[var(--hairline)]">
            <h1 class="font-display text-base font-semibold text-white">Nice<span style="color:var(--gold)">Drama</span>Bot</h1>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs text-[var(--text-muted)]">Keluar</button>
            </form>
        </header>

        <main class="p-6 max-w-6xl mx-auto">
            <h2 class="font-display text-xl font-semibold text-white mb-5">@yield('page_title', 'Dashboard')</h2>

            @if (session('success'))
                <div class="mb-5 rounded-xl px-4 py-3 text-sm bg-green-900/20 border border-green-700 text-green-300">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 rounded-xl px-4 py-3 text-sm bg-red-900/20 border border-[var(--crimson)] text-[#F27C97]">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

@yield('extra_js')
</body>
</html>
