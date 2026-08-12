@extends('admin.layout')

@section('title', 'Manajemen Film')
@section('page_title', 'Manajemen Film')

@section('content')

<div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
    <form method="GET" class="flex items-center gap-2">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari judul film..."
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-4 py-2 text-sm text-white placeholder:text-[var(--text-muted)] focus:outline-none focus:border-[var(--gold)] w-64"
        >
        <button type="submit" class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
            Cari
        </button>
    </form>

    <a href="{{ route('admin.movies.create') }}" class="btn-gold text-xs font-bold px-4 py-2.5 rounded-xl">
        + Tambah Film
    </a>
</div>

<div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[var(--text-muted)] text-xs uppercase tracking-wide border-b border-[var(--hairline)]">
                <th class="px-5 py-3">Judul</th>
                <th class="px-5 py-3">Tipe</th>
                <th class="px-5 py-3">Genre</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3">Konten</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movies as $movie)
                <tr class="border-b border-[var(--hairline)] last:border-0 hover:bg-[var(--surface-2)]/40">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-12 rounded-md bg-[var(--surface-2)] overflow-hidden shrink-0">
                                @if ($movie->cover_url)
                                    <img src="{{ $movie->cover_url }}" class="w-full h-full object-cover" alt="">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-white truncate">{{ $movie->title }}</p>
                                <p class="text-[11px] text-[var(--text-muted)] truncate">{{ $movie->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $movie->type === 'series' ? 'bg-[var(--gold)]/15 text-[var(--gold-soft)]' : 'bg-[var(--surface-2)] text-[var(--text-muted)]' }}">
                            {{ $movie->type === 'series' ? 'Series' : 'Single' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $movie->genre ?: '—' }}</td>
                    <td class="px-5 py-3">
                        @if ($movie->is_active)
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-green-900/30 text-green-300">Aktif</span>
                        @else
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[var(--surface-2)] text-[var(--text-muted)]">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">
                        @if ($movie->type === 'series')
                            {{ $movie->episodes_count }} episode
                        @else
                            {{ $movie->telegram_file_id ? '1 file' : '—' }}
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.movies.edit', $movie) }}" class="text-xs font-semibold text-[var(--gold-soft)] hover:text-[var(--gold)]">
                                Kelola
                            </a>
                            <form action="{{ route('admin.movies.destroy', $movie) }}" method="POST" onsubmit="return confirm('Hapus film ini beserta seluruh episodenya?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-[var(--crimson)] hover:text-[#F27C97]">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[var(--text-muted)] text-sm">
                        Belum ada film. Klik "+ Tambah Film" untuk mulai menambahkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $movies->links() }}
</div>

@endsection
