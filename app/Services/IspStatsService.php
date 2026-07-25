<?php

namespace App\Services;

use App\Models\PingResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds anonymized ISP × destination latency aggregates for the public stats page.
 *
 * Privacy rules:
 * - Never expose user_id, client_ip, city, coordinates, or session identifiers.
 * - Group only by ISP/ASN + coarse country + destination metadata.
 * - Hide buckets with fewer than $minSamples rows (k-anonymity).
 */
class IspStatsService
{
    public const DEFAULT_MIN_SAMPLES = 3;

    /**
     * @param  array{isp?: string, provider?: string, country?: string, min_samples?: int}  $filters
     * @return Collection<int, object>
     */
    public function aggregate(array $filters = []): Collection
    {
        $minSamples = max(1, (int) ($filters['min_samples'] ?? self::DEFAULT_MIN_SAMPLES));

        $query = PingResult::query()
            ->join('ping_targets', 'ping_targets.id', '=', 'ping_results.ping_target_id')
            ->where('ping_results.status', 'success')
            ->whereNotNull('ping_results.avg_latency_ms')
            ->whereNotNull('ping_results.client_isp')
            ->where('ping_results.client_isp', '!=', '')
            ->when(
                filled($filters['isp'] ?? null),
                fn ($q) => $q->where('ping_results.client_isp', $filters['isp'])
            )
            ->when(
                filled($filters['provider'] ?? null),
                fn ($q) => $q->where('ping_targets.provider', $filters['provider'])
            )
            ->when(
                filled($filters['country'] ?? null),
                fn ($q) => $q->where('ping_results.client_country_code', strtoupper((string) $filters['country']))
            );

        // Exclude known proxies when geo flagged them.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $query->where(function ($q) {
                $q->whereNull('ping_results.client_geo')
                    ->orWhereRaw("(ping_results.client_geo->>'isProxy') IS DISTINCT FROM 'true'");
            });
        } elseif ($driver === 'sqlite') {
            $query->where(function ($q) {
                $q->whereNull('ping_results.client_geo')
                    ->orWhereRaw("json_extract(ping_results.client_geo, '$.isProxy') IS NOT 1");
            });
        }

        return $query
            ->selectRaw('
                ping_results.client_isp as isp,
                ping_results.client_asn as asn,
                ping_results.client_country_code as country_code,
                ping_targets.provider as provider,
                ping_targets.name as target_name,
                ping_targets.host as host,
                ping_results.resolved_ip as resolved_ip,
                AVG(ping_results.avg_latency_ms) as avg_latency_ms,
                MIN(ping_results.avg_latency_ms) as min_latency_ms,
                MAX(ping_results.avg_latency_ms) as max_latency_ms,
                COUNT(*) as samples,
                AVG(CASE WHEN ping_results.connection_type = \'wifi\' THEN ping_results.avg_latency_ms END) as avg_wifi_ms,
                AVG(CASE WHEN ping_results.connection_type = \'ethernet\' THEN ping_results.avg_latency_ms END) as avg_ethernet_ms,
                SUM(CASE WHEN ping_results.connection_type = \'wifi\' THEN 1 ELSE 0 END) as samples_wifi,
                SUM(CASE WHEN ping_results.connection_type = \'ethernet\' THEN 1 ELSE 0 END) as samples_ethernet
            ')
            ->groupBy([
                'ping_results.client_isp',
                'ping_results.client_asn',
                'ping_results.client_country_code',
                'ping_targets.provider',
                'ping_targets.name',
                'ping_targets.host',
                'ping_results.resolved_ip',
            ])
            ->havingRaw('COUNT(*) >= ?', [$minSamples])
            ->orderBy('ping_results.client_isp')
            ->orderBy('avg_latency_ms')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                // Plain object: PingResult casts (decimal:2) would reformat aggregates.
                $out = (object) [
                    'isp' => $row->isp,
                    'asn' => $row->asn,
                    'country_code' => $row->country_code,
                    'provider' => $row->provider ?: __('ping.categories.other'),
                    'target_name' => $row->target_name,
                    'host' => $row->host,
                    'resolved_ip' => $row->resolved_ip ?: '—',
                    'avg_latency_ms' => (int) round((float) $row->avg_latency_ms, 0),
                    'min_latency_ms' => (int) round((float) $row->min_latency_ms, 0),
                    'max_latency_ms' => (int) round((float) $row->max_latency_ms, 0),
                    'samples' => (int) $row->samples,
                    'avg_wifi_ms' => $row->avg_wifi_ms !== null ? (int) round((float) $row->avg_wifi_ms, 0) : null,
                    'avg_ethernet_ms' => $row->avg_ethernet_ms !== null ? (int) round((float) $row->avg_ethernet_ms, 0) : null,
                    'samples_wifi' => (int) $row->samples_wifi,
                    'samples_ethernet' => (int) $row->samples_ethernet,
                ];
                $out->summary = $this->summarizeRow($out);

                return $out;
            });
    }

    /**
     * @return list<string>
     */
    public function availableIsps(): array
    {
        return PingResult::query()
            ->whereNotNull('client_isp')
            ->where('client_isp', '!=', '')
            ->distinct()
            ->orderBy('client_isp')
            ->pluck('client_isp')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function availableProviders(): array
    {
        return PingResult::query()
            ->join('ping_targets', 'ping_targets.id', '=', 'ping_results.ping_target_id')
            ->whereNotNull('ping_targets.provider')
            ->where('ping_targets.provider', '!=', '')
            ->distinct()
            ->orderBy('ping_targets.provider')
            ->pluck('ping_targets.provider')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function availableCountries(): array
    {
        return PingResult::query()
            ->whereNotNull('client_country_code')
            ->where('client_country_code', '!=', '')
            ->distinct()
            ->orderBy('client_country_code')
            ->pluck('client_country_code')
            ->all();
    }

    private function summarizeRow(object $row): string
    {
        $isp = (string) $row->isp;
        $provider = (string) $row->provider;
        $ip = (string) $row->resolved_ip;
        $avg = $row->avg_latency_ms;
        $country = $row->country_code ? ' ('.$row->country_code.')' : '';

        if ($ip !== '—') {
            return __('ping.stats_summary_ip', [
                'isp' => $isp.$country,
                'provider' => $provider,
                'ip' => $ip,
                'ms' => $avg,
            ]);
        }

        return __('ping.stats_summary_host', [
            'isp' => $isp.$country,
            'provider' => $provider,
            'host' => $row->host,
            'ms' => $avg,
        ]);
    }
}
