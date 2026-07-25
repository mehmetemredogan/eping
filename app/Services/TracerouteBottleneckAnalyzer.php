<?php

namespace App\Services;

/**
 * Marks traceroute hops where RTT jumps sharply versus the previous measured hop.
 */
class TracerouteBottleneckAnalyzer
{
    public function __construct(
        private readonly float $minAbsoluteMs = 25.0,
        private readonly float $minRelativeMs = 15.0,
        private readonly float $relativeFactor = 0.8,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $hops
     * @return list<array<string, mixed>>
     */
    public function annotate(array $hops): array
    {
        $previousAvg = null;
        $annotated = [];

        foreach ($hops as $hop) {
            $row = $hop;
            $avg = $this->hopAverageMs($hop);
            $row['bottleneck'] = false;
            $row['delta_ms'] = null;

            if ($avg !== null && $previousAvg !== null) {
                $delta = $avg - $previousAvg;
                $row['delta_ms'] = round($delta, 1);
                $row['bottleneck'] = $this->isBottleneck($delta, $previousAvg);
            }

            if ($avg !== null) {
                $previousAvg = $avg;
            }

            $annotated[] = $row;
        }

        return $annotated;
    }

    /**
     * @param  array<string, mixed>|null  $path
     * @return array<string, mixed>|null
     */
    public function annotatePath(?array $path): ?array
    {
        if ($path === null) {
            return null;
        }

        $hops = $path['hops'] ?? [];
        if (! is_array($hops)) {
            return $path;
        }

        $path['hops'] = $this->annotate(array_values($hops));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $hop
     */
    private function hopAverageMs(array $hop): ?float
    {
        if (! empty($hop['timeout'])) {
            return null;
        }

        if (isset($hop['avg_ms']) && is_numeric($hop['avg_ms'])) {
            return (float) $hop['avg_ms'];
        }

        $rtts = $hop['rtts_ms'] ?? null;
        if (! is_array($rtts) || $rtts === []) {
            return null;
        }

        $vals = array_values(array_filter(
            array_map(fn ($v) => is_numeric($v) ? (float) $v : null, $rtts),
            fn ($v) => $v !== null
        ));

        if ($vals === []) {
            return null;
        }

        return array_sum($vals) / count($vals);
    }

    private function isBottleneck(float $delta, float $previousAvg): bool
    {
        if ($delta < $this->minRelativeMs) {
            return false;
        }

        if ($delta >= $this->minAbsoluteMs) {
            return true;
        }

        return $previousAvg > 0 && $delta >= ($previousAvg * $this->relativeFactor);
    }
}
