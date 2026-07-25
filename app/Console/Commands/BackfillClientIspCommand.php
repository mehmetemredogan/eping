<?php

namespace App\Console\Commands;

use App\Models\PingResult;
use App\Services\FreeIpApiService;
use Illuminate\Console\Command;

class BackfillClientIspCommand extends Command
{
    protected $signature = 'stats:backfill-isp {--limit=200 : Max rows to process}';

    protected $description = 'Backfill client_isp / client_asn / client_country_code from client_ip for older ping results';

    public function handle(FreeIpApiService $freeIpApi): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $results = PingResult::query()
            ->whereNotNull('client_ip')
            ->where(function ($q) {
                $q->whereNull('client_isp')->orWhere('client_isp', '');
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($results as $result) {
            $geo = $freeIpApi->lookup($result->client_ip);
            if (! $geo || empty($geo['asnOrganization'])) {
                continue;
            }

            $result->update([
                'client_geo' => $geo,
                'client_asn' => $geo['asn'] ?? null,
                'client_isp' => $geo['asnOrganization'] ?? null,
                'client_country_code' => isset($geo['countryCode'])
                    ? strtoupper((string) $geo['countryCode'])
                    : null,
            ]);
            $updated++;
        }

        $this->info("Updated {$updated} / {$results->count()} rows.");

        return self::SUCCESS;
    }
}
