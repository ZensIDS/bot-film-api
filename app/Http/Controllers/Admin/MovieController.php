<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $movies = Movie::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->q . '%');
            })
            ->withCount('episodes')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.movies.index', compact('movies'));
    }

    public function create()
    {
        $movie = new Movie(['type' => 'single', 'is_active' => true]);

        return view('admin.movies.form', [
            'movie' => $movie,
            'isEdit' => false,
            'genres' => $this->existingGenres(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMovie($request);

        $data['slug'] = $this->uniqueSlug($data['title']);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('covers', 'public');
        }

        $movie = Movie::create($data);

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Film "' . $movie->title . '" berhasil ditambahkan.' .
                ($movie->type === 'series' ? ' Sekarang tambahkan episode-nya di bawah.' : ''));
    }

    public function edit(Movie $movie)
    {
        $movie->load('episodes');

        return view('admin.movies.form', [
            'movie' => $movie,
            'isEdit' => true,
            'genres' => $this->existingGenres(),
        ]);
    }

    public function update(Request $request, Movie $movie)
    {
        $data = $this->validateMovie($request, $movie->id);

        if ($data['title'] !== $movie->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $movie->id);
        }

        if ($request->hasFile('cover')) {
            if ($movie->cover_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($movie->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('covers', 'public');
        } elseif ($request->boolean('remove_cover')) {
            if ($movie->cover_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($movie->cover_path);
            }
            $data['cover_path'] = null;
        }

        $movie->update($data);

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Film "' . $movie->title . '" berhasil diperbarui.');
    }

    public function destroy(Movie $movie)
    {
        $title = $movie->title;

        if ($movie->cover_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($movie->cover_path);
        }

        $movie->delete(); // episodes ikut terhapus (cascadeOnDelete di migration)

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Film "' . $title . '" beserta episodenya berhasil dihapus.');
    }

    // ==============================
    // Episode (khusus movie type=series)
    // ==============================

    public function storeEpisode(Request $request, Movie $movie)
    {
        abort_unless($movie->type === 'series', 422, 'Episode hanya untuk film bertipe series.');

        $data = $request->validate([
            'episode_number' => [
                'required',
                'integer',
                'min:1',
                'unique:episodes,episode_number,NULL,id,movie_id,' . $movie->id,
            ],
            'title' => 'nullable|string|max:255',
            'telegram_file_id' => 'required|string|max:255',
        ], [
            'episode_number.unique' => 'Nomor episode ini sudah ada untuk film tersebut.',
        ]);

        $data['movie_id'] = $movie->id;

        Episode::create($data);

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Episode ' . $data['episode_number'] . ' berhasil ditambahkan.');
    }

    public function updateEpisode(Request $request, Movie $movie, Episode $episode)
    {
        abort_unless($episode->movie_id == $movie->id, 404); // loose (==) sengaja: hindari false-404 kalau tipe data numeric beda representasi

        $data = $request->validate([
            'episode_number' => [
                'required',
                'integer',
                'min:1',
                'unique:episodes,episode_number,' . $episode->id . ',id,movie_id,' . $movie->id,
            ],
            'title' => 'nullable|string|max:255',
            'telegram_file_id' => 'required|string|max:255',
        ], [
            'episode_number.unique' => 'Nomor episode ini sudah ada untuk film tersebut.',
        ]);

        $episode->update($data);

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Episode ' . $data['episode_number'] . ' berhasil diperbarui.');
    }

    public function destroyEpisode(Movie $movie, Episode $episode)
    {
        abort_unless($episode->movie_id == $movie->id, 404); // loose (==) sengaja: hindari false-404 kalau tipe data numeric beda representasi

        $number = $episode->episode_number;
        $episode->delete();

        return redirect()
            ->route('admin.movies.edit', $movie)
            ->with('success', 'Episode ' . $number . ' berhasil dihapus.');
    }

    // ==============================
    // Helpers
    // ==============================

    private function validateMovie(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // max 2MB
            'remove_cover' => 'nullable|boolean',
            'genre' => 'nullable|string|max:255',
            'type' => 'required|in:single,series',
            // Wajib diisi kalau type=single, boleh kosong kalau type=series (episode yang isi).
            'telegram_file_id' => 'nullable|required_if:type,single|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        // 'cover' & 'remove_cover' ditangani terpisah di store()/update() (upload/hapus file),
        // jangan ikut di-mass-assign lewat $data.
        unset($data['cover'], $data['remove_cover']);

        $data['is_active'] = $request->boolean('is_active');

        if ($data['type'] === 'series') {
            $data['telegram_file_id'] = null;
        }

        return $data;
    }

    /**
     * Daftar genre unik yang sudah pernah dipakai, untuk pilihan di form (datalist)
     * — admin bisa pilih yang sudah ada atau ketik genre baru.
     */
    private function existingGenres()
    {
        return Movie::query()
            ->whereNotNull('genre')
            ->where('genre', '!=', '')
            ->distinct()
            ->orderBy('genre')
            ->pluck('genre');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (
            Movie::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
