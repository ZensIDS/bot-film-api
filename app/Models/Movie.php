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
        'cover_url',
        'type',
        'telegram_file_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
