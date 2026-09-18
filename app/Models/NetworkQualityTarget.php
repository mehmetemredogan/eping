<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkQualityTarget extends Model
{
    protected $fillable = [
        'name',
        'url',
        'domain',
        'category',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (NetworkQualityTarget $target) {
            if ((blank($target->domain) || $target->isDirty('url')) && filled($target->url)) {
                $host = parse_url($target->url, PHP_URL_HOST);
                $target->domain = $host ?: $target->url;
            }
        });
    }
}
