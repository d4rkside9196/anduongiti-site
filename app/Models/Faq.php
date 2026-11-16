<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'locale', 'category', 'question', 'answer', 'sort_order', 'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('visible', true);
    }

    public function scopeForLocale(Builder $q, string $locale): Builder
    {
        return $q->where('locale', $locale);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
