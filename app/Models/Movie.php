<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'genre',
        'cover_path',
        'type',
        'telegram_file_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'cover_url',
    ];

    /**
     * URL publik untuk cover, di-generate dari cover_path yang disimpan di disk "public".
     * Sengaja pakai path RELATIF (bukan Storage::url() yang absolut berbasis APP_URL),
     * supaya otomatis ikut domain manapun yang dipakai akses halaman
     * (localhost, ngrok, domain production) tanpa perlu APP_URL selalu sinkron.
     */
    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? asset('storage/' . $this->cover_path) : null;
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isSeries(): bool
    {
        return $this->type === 'series';
    }
}
