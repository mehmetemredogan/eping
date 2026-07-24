<?php

namespace App\Http\Controllers;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\Provider;
use App\Services\DnsLookupService;
use App\Services\FreeIpApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PingController extends Controller
{
    public function index(Request $request, FreeIpApiService $freeIpApi): View
    {
        $query = PingTarget::query()
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('location')
            ->orderBy('name');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('host', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        $targets = $query->with('latestResult')->get();

        $groupedByProvider = $targets
            ->groupBy(fn (PingTarget $t) => $t->provider ?: __('ping.categories.other'))
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(function (Collection $group) {
                return $group->sortBy([
                    fn (PingTarget $t) => Str::lower($t->location ?: 'zzz'),
                    fn (PingTarget $t) => Str::lower($t->name),
                ])->values();
            });

        $payload = $targets->map(fn (PingTarget $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'host' => $t->host,
            'category' => $t->category,
            'provider' => $t->provider,
            'location' => $t->location,
            'country_code' => $t->country_code ? strtoupper($t->country_code) : null,
        ])->values();

        $clientIp = $request->ip();
        $clientGeo = $freeIpApi->lookup($clientIp);

        $providerDescriptions = Provider::query()
            ->whereIn('name', $groupedByProvider->keys())
            ->get()
            ->mapWithKeys(fn (Provider $p) => [$p->name => $p->description_html])
            ->filter();

        return view('ping.index', [
            'groupedByProvider' => $groupedByProvider,
            'providerDescriptions' => $providerDescriptions,
            'targetPayload' => $payload,
            'categories' => PingTarget::categories(),
            'selectedCategory' => (string) $request->string('category'),
            'search' => (string) $request->string('search'),
            'totalCount' => $targets->count(),
            'clientIp' => $clientIp,
            'clientGeo' => $clientGeo,
            'clientLocation' => $freeIpApi->formatLocation($clientGeo),
            'i18n' => [
                'details' => __('ping.details'),
                'hide' => __('ping.hide'),
                'measuring' => __('ping.measuring'),
                'yes' => __('ping.yes'),
                'no' => __('ping.no'),
                'no_records' => __('ping.no_records'),
                'status_success' => __('ping.status_success'),
                'status_failed' => __('ping.status_failed'),
                'status_timeout' => __('ping.status_timeout'),
                'status_pending' => __('ping.status_pending'),
                'dns_detecting' => __('ping.dns_detecting'),
                'dns_unavailable' => __('ping.dns_unavailable'),
                'edns_missing' => __('ping.edns_missing'),
                'edns_hint' => __('ping.edns_hint'),
            ],
        ]);
    }

    public function report(
        PingTarget $target,
        DnsLookupService $dnsLookupService,
        FreeIpApiService $freeIpApi,
        Request $request
    ): JsonResponse {
        abort_unless($target->is_active, 404);

        set_time_limit(15);

        $validated = $request->validate([
            'session_id' => ['nullable', 'uuid'],
            'status' => ['required', 'string', 'in:success,failed,timeout'],
            'min_latency_ms' => ['nullable', 'numeric', 'min:0'],
            'max_latency_ms' => ['nullable', 'numeric', 'min:0'],
            'avg_latency_ms' => ['nullable', 'numeric', 'min:0'],
            'jitter_ms' => ['nullable', 'numeric', 'min:0'],
            'packet_loss_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'packets_sent' => ['nullable', 'integer', 'min:1', 'max:20'],
            'packets_received' => ['nullable', 'integer', 'min:0', 'max:20'],
            'samples' => ['nullable', 'array'],
            'samples.*' => ['numeric'],
            'warmup_ms' => ['nullable', 'numeric', 'min:0'],
            'warmup_excluded' => ['nullable', 'boolean'],
            'client_geo' => ['nullable', 'array'],
            'client_dns' => ['nullable', 'array'],
            'client_dns.dns' => ['nullable', 'array'],
            'client_dns.dns.ip' => ['nullable', 'string', 'max:45'],
            'client_dns.dns.geo' => ['nullable', 'string', 'max:255'],
            'client_dns.edns' => ['nullable', 'array'],
            'client_dns.edns.ip' => ['nullable', 'string', 'max:64'],
            'client_dns.edns.geo' => ['nullable', 'string', 'max:255'],
            'client_dns.edns.ecs' => ['nullable', 'string', 'max:64'],
            'client_dns.source' => ['nullable', 'string', 'max:64'],
            'client_dns.detected_at' => ['nullable', 'string', 'max:64'],
        ]);

        $dns = [
            'resolved_ip' => null,
            'rdns' => null,
            'dns_records' => [],
            'edns_data' => [
                'nameserver' => null,
                'udp_payload_size' => null,
                'extended_rcode' => null,
                'edns_version' => null,
                'flags' => [],
                'options' => [],
                'support' => false,
            ],
        ];

        try {
            $dns = $dnsLookupService->lookup($target->host);
        } catch (\Throwable) {
            // Persist ping metrics even if DNS enrichment fails.
        }

        $clientIp = $request->ip();
        $clientGeo = $validated['client_geo'] ?? null;

        if (! is_array($clientGeo) || empty($clientGeo['ipAddress'])) {
            try {
                $clientGeo = $freeIpApi->lookup($clientIp);
            } catch (\Throwable) {
                $clientGeo = null;
            }
        }

        $clientDns = $validated['client_dns'] ?? null;
        if (is_array($clientDns)) {
            $clientDns = [
                'dns' => isset($clientDns['dns']) && is_array($clientDns['dns'])
                    ? [
                        'ip' => $clientDns['dns']['ip'] ?? null,
                        'geo' => $clientDns['dns']['geo'] ?? null,
                    ]
                    : null,
                'edns' => isset($clientDns['edns']) && is_array($clientDns['edns'])
                    ? [
                        'ip' => $clientDns['edns']['ip'] ?? null,
                        'geo' => $clientDns['edns']['geo'] ?? null,
                        'ecs' => $clientDns['edns']['ecs'] ?? ($clientDns['edns']['ip'] ?? null),
                    ]
                    : null,
                'source' => $clientDns['source'] ?? 'edns.ip-api.com',
                'detected_at' => $clientDns['detected_at'] ?? now()->toIso8601String(),
            ];

            if (empty($clientDns['dns']['ip']) && empty($clientDns['edns']['ip'])) {
                $clientDns = null;
            }
        } else {
            $clientDns = null;
        }

        $rawParts = ['client-side HTTP RTT (warmup excluded)'];
        if (isset($validated['warmup_ms'])) {
            $rawParts[] = 'warmup: '.round((float) $validated['warmup_ms'], 2).'ms';
        }
        if (! empty($validated['samples'])) {
            $rawParts[] = 'samples: '.implode(', ', array_map(
                fn ($s) => round((float) $s, 2).'ms',
                $validated['samples']
            ));
        }
        if (! empty($clientDns['dns']['ip'])) {
            $rawParts[] = 'client-dns: '.$clientDns['dns']['ip'];
        }
        if (! empty($clientDns['edns']['ip'])) {
            $rawParts[] = 'client-edns: '.$clientDns['edns']['ip'];
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
            'ping_raw_output' => implode(' | ', $rawParts),
            'client_ip' => $clientGeo['ipAddress'] ?? $clientIp,
            'client_geo' => $clientGeo,
            'client_dns' => $clientDns,
            'user_id' => $request->user()?->id,
            'tested_at' => now(),
        ]);

        return response()->json($this->formatResult($result));
    }

    private function formatResult(PingResult $result): array
    {
        $result->loadMissing('target');

        return [
            'id' => $result->id,
            'target_id' => $result->ping_target_id,
            'target_name' => $result->target?->name,
            'target_host' => $result->target?->host,
            'session_id' => $result->session_id,
            'status' => $result->status,
            'min_latency_ms' => $result->min_latency_ms,
            'max_latency_ms' => $result->max_latency_ms,
            'avg_latency_ms' => $result->avg_latency_ms,
            'jitter_ms' => $result->jitter_ms,
            'packet_loss_percent' => $result->packet_loss_percent,
            'packets_sent' => $result->packets_sent,
            'packets_received' => $result->packets_received,
            'resolved_ip' => $result->resolved_ip,
            'rdns' => $result->rdns,
            'dns_records' => $result->dns_records,
            'edns_data' => $result->edns_data,
            'client_ip' => $result->client_ip,
            'client_geo' => $result->client_geo,
            'client_dns' => $result->client_dns,
            'tested_at' => $result->tested_at?->toIso8601String(),
        ];
    }
}
