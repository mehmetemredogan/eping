<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Services\DnsLookupService;
use App\Services\FreeIpApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResultController extends Controller
{
    /**
     * Store a desktop-client ping result for the authenticated user.
     */
    public function store(
        Request $request,
        PingTarget $target,
        DnsLookupService $dnsLookupService,
        FreeIpApiService $freeIpApi
    ): JsonResponse {
        abort_unless($target->is_active, 404);

        // Apply the packets_sent default before validation so the
        // packets_received <= packets_sent comparison rule sees the real
        // effective value instead of comparing against a missing field.
        if (! $request->filled('packets_sent')) {
            $request->merge(['packets_sent' => 4]);
        }

        $validated = $request->validate([
            'session_id' => ['nullable', 'uuid'],
            'status' => ['required', 'string', 'in:success,failed,timeout'],
            'min_latency_ms' => ['nullable', 'numeric', 'min:0', 'lte:max_latency_ms', 'required_if:status,success'],
            'max_latency_ms' => ['nullable', 'numeric', 'min:0', 'gte:min_latency_ms', 'required_if:status,success'],
            'avg_latency_ms' => ['nullable', 'numeric', 'min:0', 'required_if:status,success'],
            'jitter_ms' => ['nullable', 'numeric', 'min:0'],
            'packet_loss_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'packets_sent' => ['nullable', 'integer', 'min:1', 'max:20'],
            'packets_received' => ['nullable', 'integer', 'min:0', 'max:20', 'lte:packets_sent'],
            'samples' => ['nullable', 'array'],
            'samples.*' => ['numeric', 'min:0'],
            'metric' => ['nullable', 'string', 'max:32'],
            'client_version' => ['nullable', 'string', 'max:64'],
            'connection_type' => ['nullable', 'string', 'in:wifi,ethernet,unknown'],
            'network_analysis' => ['nullable', 'array'],
        ]);

        if ($validated['status'] === 'success' && (int) ($validated['packets_received'] ?? 0) < 1) {
            abort(422, 'packets_received must be at least 1 when status is success.');
        }

        $dns = [
            'resolved_ip' => null,
            'rdns' => null,
            'dns_records' => [],
            'edns_data' => null,
        ];

        try {
            $dns = $dnsLookupService->lookup($target->host);
        } catch (\Throwable) {
            // keep empty
        }

        $analysis = $validated['network_analysis'] ?? null;
        $raw = ['desktop-client HTTP waiting (TTFB)'];
        if (! empty($validated['metric'])) {
            $raw[] = 'metric: '.$validated['metric'];
        }
        if (! empty($validated['client_version'])) {
            $raw[] = 'client: '.$validated['client_version'];
        }
        if (! empty($validated['samples'])) {
            $raw[] = 'samples: '.implode(', ', array_map(
                fn ($s) => round((float) $s, 2).'ms',
                $validated['samples']
            ));
        }
        if (is_array($analysis)) {
            if (! empty($analysis['summary'])) {
                $raw[] = 'net: '.$analysis['summary'];
            }
            if (! empty($analysis['path_summary'])) {
                $raw[] = 'path: '.$analysis['path_summary'];
            }
            if (! empty($analysis['path']['hop_count'])) {
                $raw[] = sprintf(
                    'hops=%d local=%d public=%d timeout=%d tool=%s reached=%s',
                    (int) $analysis['path']['hop_count'],
                    (int) ($analysis['path']['local_hops'] ?? 0),
                    (int) ($analysis['path']['public_hops'] ?? 0),
                    (int) ($analysis['path']['timeout_hops'] ?? 0),
                    (string) ($analysis['path']['tool'] ?? '?'),
                    ! empty($analysis['path']['reached']) ? 'yes' : 'no',
                );
            }
            // Keep hop list + tool output, but cap raw traceroute text size.
            if (isset($analysis['path']['raw']) && is_string($analysis['path']['raw'])) {
                $maxRaw = 64 * 1024;
                if (strlen($analysis['path']['raw']) > $maxRaw) {
                    $analysis['path']['raw'] = substr($analysis['path']['raw'], 0, $maxRaw)."\n…[truncated]";
                }
            }
        }

        $connectionType = $validated['connection_type']
            ?? (is_array($analysis) ? ($analysis['connection_type'] ?? null) : null);
        if (! in_array($connectionType, ['wifi', 'ethernet', 'unknown'], true)) {
            $connectionType = null;
        }
        if (is_array($analysis) && $connectionType) {
            $analysis['connection_type'] = $connectionType;
        }
        if ($connectionType) {
            $raw[] = 'link: '.$connectionType;
        }

        $clientIp = $request->ip();
        $clientGeo = null;
        try {
            $clientGeo = $freeIpApi->lookup($clientIp);
        } catch (\Throwable) {
            $clientGeo = null;
        }

        $result = PingResult::create([
            'ping_target_id' => $target->id,
            'session_id' => $validated['session_id'] ?? (string) Str::uuid(),
            'status' => $validated['status'],
            'min_latency_ms' => $validated['min_latency_ms'] ?? null,
            'max_latency_ms' => $validated['max_latency_ms'] ?? null,
            'avg_latency_ms' => $validated['avg_latency_ms'] ?? null,
            'jitter_ms' => $validated['jitter_ms'] ?? null,
            'packet_loss_percent' => $validated['packet_loss_percent'] ?? null,
            'packets_sent' => $validated['packets_sent'] ?? 4,
            'packets_received' => $validated['packets_received'] ?? 0,
            'resolved_ip' => $dns['resolved_ip'] ?? null,
            'rdns' => $dns['rdns'] ?? null,
            'dns_records' => $dns['dns_records'] ?? [],
            'edns_data' => $dns['edns_data'] ?? null,
            'ping_raw_output' => implode(' | ', $raw),
            'client_ip' => $clientIp,
            'client_geo' => $clientGeo,
            'client_asn' => $clientGeo['asn'] ?? null,
            'client_isp' => $clientGeo['asnOrganization'] ?? null,
            'client_country_code' => isset($clientGeo['countryCode'])
                ? strtoupper((string) $clientGeo['countryCode'])
                : null,
            'connection_type' => $connectionType,
            'client_dns' => null,
            'network_analysis' => $analysis,
            'user_id' => $request->user()->id,
            'tested_at' => now(),
        ]);

        return response()->json([
            'id' => $result->id,
            'target_id' => $result->ping_target_id,
            'status' => $result->status,
            'avg_latency_ms' => $result->avg_latency_ms,
            'resolved_ip' => $result->resolved_ip,
            'network_status' => is_array($analysis) ? ($analysis['status'] ?? null) : null,
            'tested_at' => $result->tested_at?->toIso8601String(),
        ], 201);
    }
}
