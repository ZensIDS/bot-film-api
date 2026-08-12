<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - NiceDramaBot</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg: #0B0910; --surface: #16131F; --surface-2: #1E1930;
            --gold: #E8B156; --gold-soft: #F3D08A; --crimson: #C2355A;
            --text: #EDE9F5; --text-muted: #9C93AF; --hairline: rgba(232,177,86,0.16);
        }
        body{
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }
        .font-display{ font-family: 'Fraunces', serif; }

        .bg-glow{
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background:
                radial-gradient(600px circle at 20% 15%, rgba(232,177,86,0.10), transparent 60%),
                radial-gradient(500px circle at 85% 85%, rgba(194,53,90,0.12), transparent 60%);
        }

        .field{
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 12px;
            padding: 13px 16px 13px 42px;
            font-size: 14px;
            color: var(--text);
            transition: border-color .15s;
        }
        .field:focus{ outline: none; border-color: var(--gold); }
        .field::placeholder{ color: var(--text-muted); }

        .field-icon{
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 15px; pointer-events: none;
        }

        .btn-gold{
            background: linear-gradient(180deg, var(--gold-soft), var(--gold));
            color: #241705;
        }
        .btn-gold:hover{ filter: brightness(1.05); }
        .btn-gold:active{ transform: scale(0.98); }

        input[type="checkbox"]{ accent-color: var(--gold); }
    </style>
</head>
<body class="flex items-center justify-center px-4">
    <div class="bg-glow"></div>

    <div class="relative z-10 w-full max-w-sm">

        <div class="text-center mb-8">
            <h1 class="font-display text-2xl font-semibold text-white">
                Nice<span style="color:var(--gold)">Drama</span>Bot
            </h1>
            <p class="text-xs text-[var(--text-muted)] mt-1 tracking-wide uppercase">Admin Panel</p>
        </div>

        <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-7 shadow-2xl">
            <p class="text-sm text-[var(--text-muted)] mb-6">Masuk untuk mengelola film, user, dan transaksi.</p>

            @if ($errors->any())
                <div class="mb-5 rounded-xl px-4 py-3 text-xs bg-red-900/20 border border-[var(--crimson)] text-[#F27C97]">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <div class="relative">
                    <span class="field-icon">✉️</span>
                    <input type="email" name="email" class="field" placeholder="Email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="relative">
                    <span class="field-icon">🔒</span>
                    <input type="password" name="password" class="field" placeholder="Password" required>
                </div>

                <div class="flex items-center justify-between text-xs text-[var(--text-muted)] pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded">
                        Ingat saya
                    </label>
                </div>

                <button type="submit" class="btn-gold w-full font-bold py-3 rounded-xl text-sm shadow-lg transition mt-2">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-[var(--text-muted)] mt-6">
            &copy; {{ date('Y') }} NiceDramaBot &middot; Internal Admin Only
        </p>
    </div>
</body>
</html>
