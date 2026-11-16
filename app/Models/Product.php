<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_key', 'locale', 'title', 'eyebrow', 'lede', 'specs', 'origins',
    ];

    protected $casts = [
        'specs' => 'array',     // [[k, v], ...]
        'origins' => 'array',   // string[]
    ];
}
