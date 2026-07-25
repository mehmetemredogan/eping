<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PingResult extends Model
{
    protected $fillable = [
        'ping_target_id',
        'session_id',
        'status',
        'min_latency_ms',
        'max_latency_ms',
        'avg_latency_ms',
        'jitter_ms',
        'packet_loss_percent',
        'packets_sent',
        'packets_received',
        'resolved_ip',
        'rdns',
        'dns_records',
        'edns_data',
        'ping_raw_output',
        'client_ip',
        'client_geo',
        'client_asn',
        'client_isp',
        'client_country_code',
        'connection_type',
        'client_dns',
        'network_analysis',
        'user_id',
        'tested_at',
    ];

    protected function casts(): array
    {
        return [
            'dns_records' => 'array',
            'edns_data' => 'array',
            'client_geo' => 'array',
            'client_dns' => 'array',
            'network_analysis' => 'array',
            'tested_at' => 'datetime',
            'min_latency_ms' => 'decimal:2',
            'max_latency_ms' => 'decimal:2',
            'avg_latency_ms' => 'decimal:2',
            'jitter_ms' => 'decimal:2',
            'packet_loss_percent' => 'decimal:2',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(PingTarget::class, 'ping_target_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
