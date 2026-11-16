<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name', 'region', 'spec_en', 'spec_vi', 'sort_order', 'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('visible', true);
    }
}
