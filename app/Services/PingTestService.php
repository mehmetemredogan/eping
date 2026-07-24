<?php

namespace App\Services;

use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Support\Str;

class PingTestService
{
    public function __construct(
        private PingService $pingService,
        private DnsLookupService $dnsLookupService,
    ) {}

    public function run(PingTarget $target, ?string $sessionId = null, ?string $clientIp = null): PingResult
    {
        $sessionId ??= (string) Str::uuid();

        $dns = $this->dnsLookupService->lookup($target->host);
        $ping = $this->pingService->ping($target->host);

        $resolvedIp = $ping['resolved_ip'] ?? $dns['resolved_ip'];

        return PingResult::create([
            'ping_target_id' => $target->id,
            'session_id' => $sessionId,
            'status' => $ping['status'],
            'min_latency_ms' => $ping['min_latency_ms'],
            'max_latency_ms' => $ping['max_latency_ms'],
            'avg_latency_ms' => $ping['avg_latency_ms'],
            'jitter_ms' => $ping['jitter_ms'],
            'packet_loss_percent' => $ping['packet_loss_percent'],
            'packets_sent' => $ping['packets_sent'],
            'packets_received' => $ping['packets_received'],
            'resolved_ip' => $resolvedIp,
            'rdns' => $dns['rdns'],
            'dns_records' => $dns['dns_records'],
            'edns_data' => $dns['edns_data'],
            'ping_raw_output' => $ping['raw_output'],
            'client_ip' => $clientIp,
            'tested_at' => now(),
        ]);
    }

    public function runBatch(iterable $targets, ?string $clientIp = null): array
    {
        $sessionId = (string) Str::uuid();
        $results = [];

        foreach ($targets as $target) {
            $results[] = $this->run($target, $sessionId, $clientIp);
        }

        return [
            'session_id' => $sessionId,
            'results' => $results,
        ];
    }
}
