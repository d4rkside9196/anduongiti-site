<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'slug', 'locale', 'title', 'excerpt', 'body', 'tags', 'cover',
        'published_at', 'status',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function scopeForLocale(Builder $q, string $locale): Builder
    {
        return $q->where('locale', $locale);
    }
}
