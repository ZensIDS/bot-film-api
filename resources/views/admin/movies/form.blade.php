@extends('admin.layout')

@section('title', $isEdit ? 'Edit Film' : 'Tambah Film')
@section('page_title', $isEdit ? 'Kelola Film: ' . $movie->title : 'Tambah Film Baru')

@section('content')

<div class="flex items-center gap-2 mb-5">
    <a href="{{ route('admin.movies.index') }}" class="text-xs font-semibold text-[var(--text-muted)] hover:text-[var(--gold)]">
        &larr; Kembali ke daftar film
    </a>
</div>

<div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-6">

    <form action="{{ $isEdit ? route('admin.movies.update', $movie) : route('admin.movies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div>
            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Judul Film</label>
            <input type="text" name="title" value="{{ old('title', $movie->title) }}" required
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-4 py-2.5 text-sm text-[var(--text)] focus:outline-none focus:border-[var(--gold)]">
            @error('title') <p class="text-xs text-[var(--crimson)] mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Genre</label>
                <input type="text" name="genre" list="genre_list" value="{{ old('genre', $movie->genre) }}"
                    placeholder="Pilih genre yang ada atau ketik baru"
                    class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-4 py-2.5 text-sm text-[var(--text)] focus:outline-none focus:border-[var(--gold)]">
                <datalist id="genre_list">
                    @foreach ($genres as $g)
                        <option value="{{ $g }}">
                    @endforeach
                </datalist>
                <p class="text-[11px] text-[var(--text-muted)] mt-1">Ketik untuk cari genre yang sudah ada, atau langsung ketik nama genre baru.</p>
                @error('genre') <p class="text-xs text-[var(--crimson)] mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Cover</label>

                <div class="flex items-center gap-3">
                    @if ($movie->cover_url)
                        <img id="cover_preview" src="{{ $movie->cover_url }}" class="w-14 h-20 object-cover rounded-lg border border-[var(--hairline)]" alt="">
                    @else
                        <img id="cover_preview" src="" class="w-14 h-20 object-cover rounded-lg border border-[var(--hairline)] hidden" alt="">
                    @endif

                    <div class="flex-1">
                        <input type="file" name="cover" accept="image/png,image/jpeg,image/webp"
                            onchange="previewCover(this)"
                            class="w-full text-xs text-[var(--text-muted)] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[var(--surface-2)] file:text-[var(--text)]">

                        @if ($isEdit && $movie->cover_path)
                            <label class="flex items-center gap-1.5 mt-1.5 text-[11px] text-[var(--text-muted)]">
                                <input type="checkbox" name="remove_cover" value="1" class="accent-[var(--crimson)]">
                                Hapus cover saat ini
                            </label>
                        @endif
                    </div>
                </div>
                <p class="text-[11px] text-[var(--text-muted)] mt-1">JPG/PNG/WEBP, maks 2MB.</p>
                @error('cover') <p class="text-xs text-[var(--crimson)] mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Deskripsi</label>
            <textarea name="description" rows="3"
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-4 py-2.5 text-sm text-[var(--text)] focus:outline-none focus:border-[var(--gold)]">{{ old('description', $movie->description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Tipe Film</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="type" value="single" class="peer sr-only" id="type_single"
                        {{ old('type', $movie->type) === 'single' ? 'checked' : '' }}
                        onchange="toggleMovieType()">
                    <div class="rounded-xl border border-[var(--hairline)] px-4 py-3 text-sm peer-checked:border-[var(--gold)] peer-checked:bg-[var(--gold)]/10 peer-checked:text-[var(--gold-soft)] text-[var(--text-muted)]">
                        <p class="font-semibold">Single</p>
                        <p class="text-[11px] mt-0.5">Film sekali tayang, satu file video.</p>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="type" value="series" class="peer sr-only" id="type_series"
                        {{ old('type', $movie->type) === 'series' ? 'checked' : '' }}
                        onchange="toggleMovieType()">
                    <div class="rounded-xl border border-[var(--hairline)] px-4 py-3 text-sm peer-checked:border-[var(--gold)] peer-checked:bg-[var(--gold)]/10 peer-checked:text-[var(--gold-soft)] text-[var(--text-muted)]">
                        <p class="font-semibold">Series</p>
                        <p class="text-[11px] mt-0.5">Ber-episode, file video per episode.</p>
                    </div>
                </label>
            </div>
            @error('type') <p class="text-xs text-[var(--crimson)] mt-1">{{ $message }}</p> @enderror
        </div>

        <div id="single_file_field">
            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">
                Telegram File ID
            </label>
            <input type="text" name="telegram_file_id" value="{{ old('telegram_file_id', $movie->telegram_file_id) }}"
                placeholder="Tempel file_id dari log webhook bot"
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-4 py-2.5 text-sm text-[var(--text)] font-mono focus:outline-none focus:border-[var(--gold)]">
            <p class="text-[11px] text-[var(--text-muted)] mt-1">
                Dapatkan dari admin upload video ke channel privat, lalu ambil <code>file_id</code>-nya dari bot.
            </p>
            @error('telegram_file_id') <p class="text-xs text-[var(--crimson)] mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2.5">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                {{ old('is_active', $movie->is_active) ? 'checked' : '' }}
                class="w-4 h-4 rounded accent-[var(--gold)]">
            <label for="is_active" class="text-sm">Tampilkan film ini di katalog TWA (Aktif)</label>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <button type="submit" class="btn-gold text-sm font-bold px-6 py-2.5 rounded-xl">
                {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Film' }}
            </button>
        </div>
    </form>
</div>

@if ($isEdit && $movie->type === 'series')
<div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-6 mt-6">
    <h3 class="font-display text-base font-semibold text-[var(--text)] mb-1">Daftar Episode</h3>
    <p class="text-xs text-[var(--text-muted)] mb-4">Tambahkan episode satu per satu. Tombol "Tonton" di TWA akan mengirim file_id episode yang dipilih user.</p>

    <div class="space-y-2 mb-6">
        @forelse ($movie->episodes as $episode)
            <div class="flex items-center gap-3 bg-[var(--surface-2)] rounded-xl px-4 py-3">
                <span class="text-xs font-bold text-[var(--gold-soft)] w-16 shrink-0">Eps {{ $episode->episode_number }}</span>
                <span class="text-sm flex-1 min-w-0 truncate">{{ $episode->title ?: '(tanpa judul)' }}</span>
                <span class="text-[11px] font-mono text-[var(--text-muted)] truncate max-w-[220px]">{{ $episode->telegram_file_id }}</span>

                <button type="button" onclick="document.getElementById('edit-episode-{{ $episode->id }}').classList.toggle('hidden')"
                    class="text-xs font-semibold text-[var(--gold-soft)] hover:text-[var(--gold)] shrink-0">
                    Edit
                </button>

                <form action="{{ route('admin.movies.episodes.destroy', [$movie, $episode]) }}" method="POST" onsubmit="return confirm('Hapus episode {{ $episode->episode_number }}?');" class="shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-[var(--crimson)] hover:text-[var(--crimson)]">Hapus</button>
                </form>
            </div>

            <div id="edit-episode-{{ $episode->id }}" class="hidden bg-[var(--surface-2)]/60 rounded-xl px-4 py-3">
                <form action="{{ route('admin.movies.episodes.update', [$movie, $episode]) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-[11px] text-[var(--text-muted)] mb-1">No. Episode</label>
                        <input type="number" min="1" name="episode_number" value="{{ $episode->episode_number }}" required
                            class="w-full bg-[var(--bg)] border border-[var(--hairline)] rounded-lg px-3 py-2 text-sm text-[var(--text)]">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[11px] text-[var(--text-muted)] mb-1">Judul (opsional)</label>
                        <input type="text" name="title" value="{{ $episode->title }}"
                            class="w-full bg-[var(--bg)] border border-[var(--hairline)] rounded-lg px-3 py-2 text-sm text-[var(--text)]">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[11px] text-[var(--text-muted)] mb-1">Telegram File ID</label>
                        <input type="text" name="telegram_file_id" value="{{ $episode->telegram_file_id }}" required
                            class="w-full bg-[var(--bg)] border border-[var(--hairline)] rounded-lg px-3 py-2 text-sm text-[var(--text)] font-mono">
                    </div>
                    <button type="submit" class="btn-gold text-xs font-bold px-4 py-2 rounded-lg h-fit">Simpan</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-[var(--text-muted)]">Belum ada episode.</p>
        @endforelse
    </div>

    <form action="{{ route('admin.movies.episodes.store', $movie) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end border-t border-[var(--hairline)] pt-5">
        @csrf
        <div>
            <label class="block text-[11px] text-[var(--text-muted)] mb-1">No. Episode</label>
            <input type="number" min="1" name="episode_number" value="{{ old('episode_number', $movie->episodes->max('episode_number') + 1) }}" required
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-lg px-3 py-2 text-sm text-[var(--text)]">
        </div>
        <div>
            <label class="block text-[11px] text-[var(--text-muted)] mb-1">Judul (opsional)</label>
            <input type="text" name="title" placeholder="mis. Awal Pertemuan"
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-lg px-3 py-2 text-sm text-[var(--text)]">
        </div>
        <div>
            <label class="block text-[11px] text-[var(--text-muted)] mb-1">Telegram File ID</label>
            <input type="text" name="telegram_file_id" required placeholder="Tempel file_id"
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-lg px-3 py-2 text-sm text-[var(--text)] font-mono">
        </div>
        <button type="submit" class="btn-gold text-xs font-bold px-4 py-2.5 rounded-lg h-fit">+ Tambah Episode</button>
    </form>
    @error('episode_number') <p class="text-xs text-[var(--crimson)] mt-2">{{ $message }}</p> @enderror
</div>
@elseif (!$isEdit)
<div class="mt-6 text-xs text-[var(--text-muted)] px-1">
    💡 Kalau memilih tipe <b>Series</b>, simpan dulu film ini — setelah tersimpan kamu akan diarahkan ke halaman edit untuk menambahkan episode satu per satu.
</div>
@endif

@endsection

@section('extra_js')
<script>
    function toggleMovieType() {
        const isSeries = document.getElementById('type_series').checked;
        document.getElementById('single_file_field').style.display = isSeries ? 'none' : 'block';
    }
    document.addEventListener('DOMContentLoaded', toggleMovieType);

    function previewCover(input) {
        const img = document.getElementById('cover_preview');
        if (input.files && input.files[0]) {
            img.src = URL.createObjectURL(input.files[0]);
            img.classList.remove('hidden');
        }
    }
</script>
@endsection
