<?php

namespace App\Models;

use App\Authorization\AdminPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'name', 'company', 'email', 'country', 'product', 'source_slug', 'message', 'status', 'assigned_to',
        'coffee_product_id', 'source_locale', 'source_product_title', 'source_category_slug',
        'first_response_at', 'last_activity_at',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function coffeeProduct(): BelongsTo
    {
        return $this->belongsTo(CoffeeProduct::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! AdminPermissions::allows($user, AdminPermissions::LEADS_READ)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->whereNull('assigned_to')->orWhere('assigned_to', $user->getKey());
        });
    }
}
