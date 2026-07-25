<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'slug',
        'name_tr',
        'name_en',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PingTarget::class, 'category', 'slug');
    }

    public function label(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return ($locale === 'en' ? $this->name_en : $this->name_tr) ?: $this->slug;
    }

    /**
     * @return array<string, string> slug => localized label
     */
    public static function options(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get()
            ->mapWithKeys(fn (self $category) => [$category->slug => $category->label()])
            ->all();
    }
}
