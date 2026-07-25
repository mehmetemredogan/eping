<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PingTarget extends Model
{
    protected $fillable = [
        'name',
        'host',
        'category',
        'provider',
        'location',
        'country_code',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(PingResult::class);
    }

    public function latestResult()
    {
        return $this->hasOne(PingResult::class)->latestOfMany('tested_at');
    }

    /**
     * Localized category options keyed by slug (from the categories table).
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return Category::options();
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? $this->category;
    }
}
