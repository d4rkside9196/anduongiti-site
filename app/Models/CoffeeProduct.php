<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffeeProduct extends Model
{
    use HasFactory;

    public const BEAN_VARIETY_LABELS = [
        'arabica' => 'Arabica',
        'robusta' => 'Robusta',
    ];

    protected $fillable = [
        'slug', 'locale', 'category_id', 'bean_variety', 'title', 'eyebrow', 'description',
        'specs', 'image', 'image_alt', 'sort_order', 'visible',
    ];

    protected $casts = [
        'specs' => 'array',
        'visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

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

    /**
     * Stable enquiry routing key derived from the localized category.
     */
    public function contactProductKey(): string
    {
        return 'category:'.$this->category->slug;
    }

    /**
     * Public URL for the product image. Seeded rows point at plain
     * uploads/coffee/... paths under public/ (served at /uploads/...),
     * admin uploads go through the public disk (storage/app/public
     * served at /storage). Falls back to the per-slug SVG placeholder —
     * or the generic one when the slug has no placeholder of its own —
     * so a card never renders without an image.
     */
    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            $placeholder = 'uploads/coffee/'.$this->slug.'.svg';

            return asset(file_exists(public_path($placeholder)) ? $placeholder : 'uploads/coffee/placeholder.svg');
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')
            || str_starts_with($this->image, '/')) {
            return $this->image;
        }

        // Seeded demo assets live under public/uploads/... — not on the
        // public disk — so anything starting with "uploads/" is served
        // verbatim, not via the /storage mount.
        if (str_starts_with($this->image, 'uploads/')) {
            return asset($this->image);
        }

        return asset('storage/'.$this->image);
    }
}
