<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkQualityTest extends Model
{
    protected $fillable = [
        'user_id',
        'score',
        'grade',
        'status',
        'summary',
        'avg_latency_ms',
        'avg_dns_ms',
        'avg_tcp_ms',
        'avg_tls_ms',
        'avg_ttfb_ms',
        'packet_loss_percent',
        'client_ip',
        'client_geo',
        'client_isp',
        'client_asn',
        'client_country_code',
        'connection_type',
        'results',
        'insights',
        'tested_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'results' => 'array',
            'insights' => 'array',
            'client_geo' => 'array',
            'tested_at' => 'datetime',
            'avg_latency_ms' => 'decimal:2',
            'avg_dns_ms' => 'decimal:2',
            'avg_tcp_ms' => 'decimal:2',
            'avg_tls_ms' => 'decimal:2',
            'avg_ttfb_ms' => 'decimal:2',
            'packet_loss_percent' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
