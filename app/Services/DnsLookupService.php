<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fast DNS / rDNS / EDNS enrichment via DNS-over-HTTPS (avoids blocking dns_get_record on Windows).
 */
class DnsLookupService
{
    private const CACHE_TTL_SECONDS = 300;

    private const DOH_ENDPOINT = 'https://cloudflare-dns.com/dns-query';

    private const HTTP_TIMEOUT = 2.0;

    public function lookup(string $host): array
    {
        $host = $this->normalizeHost($host);
        $cacheKey = 'dns_lookup_v2:'.md5($host);

        return Cache::store('file')->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($host) {
            return $this->performLookup($host);
        });
    }

    private function performLookup(string $host): array
    {
        $emptyEdns = [
            'nameserver' => '1.1.1.1',
            'udp_payload_size' => null,
            'extended_rcode' => null,
            'edns_version' => null,
            'flags' => [],
            'options' => [],
            'support' => false,
        ];

        try {
            $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

            if ($isIp) {
                $rdns = $this->lookupPtr($host);

                return [
                    'host' => $host,
                    'resolved_ip' => $host,
                    'rdns' => $rdns,
                    'dns_records' => $rdns ? [[
                        'type' => 'PTR',
                        'host' => $host,
                        'value' => $rdns,
                        'ttl' => null,
                    ]] : [],
                    'edns_data' => array_merge($emptyEdns, ['support' => true, 'udp_payload_size' => 1232, 'edns_version' => 0]),
                ];
            }

            $a = $this->dohQuery($host, 'A');
            $aaaa = $this->dohQuery($host, 'AAAA');
            $cname = $this->dohQuery($host, 'CNAME');

            $records = array_merge(
                $this->mapAnswers($cname, 'CNAME'),
                $this->mapAnswers($a, 'A'),
                $this->mapAnswers($aaaa, 'AAAA'),
            );

            $resolvedIp = $a[0]['data'] ?? $aaaa[0]['data'] ?? $this->resolvePrimaryIp($host);

            // Fallback A record when DoH is blocked but gethostbyname worked.
            if ($records === [] && $resolvedIp && filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $records[] = [
                    'type' => 'A',
                    'host' => $host,
                    'value' => $resolvedIp,
                    'ttl' => null,
                ];
            }

            $rdns = $resolvedIp ? $this->lookupPtr($resolvedIp) : null;

            return [
                'host' => $host,
                'resolved_ip' => $resolvedIp,
                'rdns' => $rdns,
                'dns_records' => $records,
                'edns_data' => array_merge($emptyEdns, [
                    'support' => $a !== [] || $aaaa !== [] || $cname !== [],
                    'udp_payload_size' => ($a !== [] || $aaaa !== [] || $cname !== []) ? 1232 : null,
                    'edns_version' => ($a !== [] || $aaaa !== [] || $cname !== []) ? 0 : null,
                    'flags' => ($a !== [] || $aaaa !== [] || $cname !== []) ? ['DOH'] : [],
                    'options' => ($a !== [] || $aaaa !== [] || $cname !== [])
                        ? [['code' => 0, 'name' => 'DoH (Cloudflare)', 'length' => 0, 'data' => '']]
                        : [],
                ]),
            ];
        } catch (Throwable) {
            return [
                'host' => $host,
                'resolved_ip' => null,
                'rdns' => null,
                'dns_records' => [],
                'edns_data' => $emptyEdns,
            ];
        }
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);
        $host = preg_replace('#^https?://#i', '', $host) ?? $host;
        $host = explode('/', $host)[0];
        $host = $this->stripPort($host);

        return strtolower($host);
    }

    /**
     * Strip an optional trailing ":port" without corrupting bare IPv6 literals
     * (e.g. "2001:db8::1") or "[ipv6]:port" host:port pairs.
     */
    private function stripPort(string $host): string
    {
        if ($host === '') {
            return $host;
        }

        // "[::1]:8080" or "[::1]" -> "::1"
        if ($host[0] === '[') {
            $end = strpos($host, ']');

            return $end !== false ? substr($host, 1, $end - 1) : $host;
        }

        // Bare IPv6 literal (multiple colons) has no port to strip.
        if (substr_count($host, ':') >= 2) {
            return $host;
        }

        // "example.com:8080" or IPv4 "1.2.3.4:8080" -> strip single ":port".
        return explode(':', $host)[0];
    }

    private function resolvePrimaryIp(string $host): ?string
    {
        $ipv4 = @gethostbyname($host);

        if ($ipv4 && $ipv4 !== $host && filter_var($ipv4, FILTER_VALIDATE_IP)) {
            return $ipv4;
        }

        return null;
    }

    /**
     * @return list<array{name?: string, type?: int, TTL?: int, data?: string}>
     */
    private function dohQuery(string $name, string $type): array
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withOptions(['verify' => filter_var(env('DNS_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN)])
                ->accept('application/dns-json')
                ->get(self::DOH_ENDPOINT, [
                    'name' => $name,
                    'type' => $type,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $answers = $response->json('Answer');

            return is_array($answers) ? $answers : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  list<array{name?: string, TTL?: int, data?: string}>  $answers
     * @return list<array{type: string, host: string|null, value: string, ttl: int|null}>
     */
    private function mapAnswers(array $answers, string $type): array
    {
        $records = [];

        foreach ($answers as $answer) {
            $value = isset($answer['data']) ? rtrim((string) $answer['data'], '.') : '';
            if ($value === '') {
                continue;
            }

            $records[] = [
                'type' => $type,
                'host' => isset($answer['name']) ? rtrim((string) $answer['name'], '.') : null,
                'value' => $value,
                'ttl' => isset($answer['TTL']) ? (int) $answer['TTL'] : null,
            ];
        }

        return $records;
    }

    private function lookupPtr(string $ip): ?string
    {
        $ptrName = $this->ptrName($ip);
        if ($ptrName === null) {
            return null;
        }

        $answers = $this->dohQuery($ptrName, 'PTR');

        if ($answers === []) {
            return null;
        }

        $value = rtrim((string) ($answers[0]['data'] ?? ''), '.');

        return $value !== '' ? $value : null;
    }

    private function ptrName(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return implode('.', array_reverse(explode('.', $ip))).'.in-addr.arpa';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = @inet_pton($ip);
            if ($packed === false) {
                return null;
            }

            $hex = bin2hex($packed);
            $nibbles = array_reverse(str_split($hex));

            return implode('.', $nibbles).'.ip6.arpa';
        }

        return null;
    }
}
