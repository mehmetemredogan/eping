<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * @see https://freeipapi.com/docs/api-reference/api-introduction
 * @see https://freeipapi.com/docs/api-reference/get-ip-info
 */
class FreeIpApiService
{
    private const CACHE_TTL_SECONDS = 1800;

    public function lookup(?string $ip = null): ?array
    {
        $ip = $ip ? trim($ip) : null;

        if ($ip !== null && ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Skip lookup for local/private addresses — Free IP API needs a public IP.
        if ($ip !== null && $this->isNonPublicIp($ip)) {
            return [
                'ipAddress' => $ip,
                'cityName' => null,
                'regionName' => null,
                'countryName' => null,
                'countryCode' => null,
                'continent' => null,
                'latitude' => null,
                'longitude' => null,
                'asn' => null,
                'asnOrganization' => null,
                'isProxy' => null,
                'zipCode' => null,
                'timeZones' => [],
                'local' => true,
            ];
        }

        $cacheKey = 'freeipapi:'.md5($ip ?: 'self');

        return Cache::store('file')->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($ip) {
            return $this->fetch($ip);
        });
    }

    private function fetch(?string $ip): ?array
    {
        $base = rtrim((string) env('FREEIPAPI_BASE_URL', 'https://free.freeipapi.com'), '/');
        $url = $ip ? "{$base}/api/json/{$ip}" : "{$base}/api/json";

        try {
            $response = Http::timeout(3)
                ->withOptions(['verify' => filter_var(env('DNS_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN)])
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data) || empty($data['ipAddress'])) {
                return null;
            }

            return [
                'ipAddress' => $data['ipAddress'] ?? null,
                'ipVersion' => $data['ipVersion'] ?? null,
                'cityName' => $data['cityName'] ?? null,
                'regionName' => $data['regionName'] ?? null,
                'regionCode' => $data['regionCode'] ?? null,
                'countryName' => $data['countryName'] ?? null,
                'countryCode' => $data['countryCode'] ?? null,
                'continent' => $data['continent'] ?? null,
                'continentCode' => $data['continentCode'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'zipCode' => $data['zipCode'] ?? null,
                'asn' => isset($data['asn']) ? (string) $data['asn'] : null,
                'asnOrganization' => $data['asnOrganization'] ?? null,
                'isProxy' => $data['isProxy'] ?? null,
                'timeZones' => $data['timeZones'] ?? [],
                'languages' => $data['languages'] ?? [],
                'currencies' => $data['currencies'] ?? [],
                'local' => false,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function isNonPublicIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
