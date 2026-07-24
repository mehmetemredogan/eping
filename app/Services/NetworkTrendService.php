<?php

namespace App\Services;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;

/**
 * Compares a user's most recent ping results against their own historical
 * baseline to answer "is my network getting better or worse?".
 *
 * The "recent" window is the last N results (most recent first); the
 * "baseline" window is the M results immediately preceding it. Both windows
 * only consider results with a resolved avg_latency_ms (i.e. actual replies).
 */
class NetworkTrendService
{
    private const RECENT_WINDOW = 5;

    private const BASELINE_WINDOW = 30;

    /**
     * Trend across every target the user has ever tested, plus an overall
     * (all-targets-combined) trend.
     *
     * @return array{overall: array, targets: list<array>}
     */
    public function summaryForUser(User $user): array
    {
        $overall = $this->trendFromQuery(
            PingResult::query()->where('user_id', $user->id)
        );

        $targetIds = PingResult::query()
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('ping_target_id');

        $targets = PingTarget::query()
            ->whereIn('id', $targetIds)
            ->get()
            ->map(fn (PingTarget $target) => $this->trendForTarget($user, $target))
            ->values()
            ->all();

        return [
            'overall' => $overall,
            'targets' => $targets,
        ];
    }

    /**
     * Trend for a single target, comparing the user's latest result there
     * against their own history for that same target.
     */
    public function trendForTarget(User $user, PingTarget $target): array
    {
        $trend = $this->trendFromQuery(
            PingResult::query()
                ->where('user_id', $user->id)
                ->where('ping_target_id', $target->id)
        );

        $trend['target_id'] = $target->id;
        $trend['name'] = $target->name;
        $trend['host'] = $target->host;

        return $trend;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PingResult>  $baseQuery
     */
    private function trendFromQuery($baseQuery): array
    {
        $recent = (clone $baseQuery)
            ->whereNotNull('avg_latency_ms')
            ->latest('tested_at')
            ->limit(self::RECENT_WINDOW)
            ->get(['avg_latency_ms', 'packet_loss_percent', 'jitter_ms', 'tested_at', 'status', 'network_analysis']);

        $last = $recent->first();

        if ($recent->isEmpty()) {
            return [
                'trend' => 'insufficient_data',
                'trend_label' => 'yetersiz veri',
                'last' => null,
                'recent_avg_ms' => null,
                'baseline_avg_ms' => null,
                'delta_ms' => null,
                'delta_percent' => null,
                'recent_count' => 0,
                'baseline_count' => 0,
            ];
        }

        $baseline = (clone $baseQuery)
            ->whereNotNull('avg_latency_ms')
            ->latest('tested_at')
            ->skip(self::RECENT_WINDOW)
            ->limit(self::BASELINE_WINDOW)
            ->get(['avg_latency_ms', 'packet_loss_percent']);

        $recentAvg = round((float) $recent->avg('avg_latency_ms'), 2);
        $recentLoss = round((float) $recent->avg('packet_loss_percent'), 2);

        $lastPayload = [
            'status' => $last->status,
            'avg_latency_ms' => $last->avg_latency_ms !== null ? (float) $last->avg_latency_ms : null,
            'jitter_ms' => $last->jitter_ms !== null ? (float) $last->jitter_ms : null,
            'packet_loss_percent' => $last->packet_loss_percent !== null ? (float) $last->packet_loss_percent : null,
            'tested_at' => $last->tested_at?->toIso8601String(),
            'network_status' => is_array($last->network_analysis) ? ($last->network_analysis['status'] ?? null) : null,
        ];

        // Not enough history yet to call it a trend — still report the recent average.
        if ($baseline->count() < 3) {
            return [
                'trend' => 'insufficient_data',
                'trend_label' => 'yetersiz veri (yeni hedef)',
                'last' => $lastPayload,
                'recent_avg_ms' => $recentAvg,
                'baseline_avg_ms' => null,
                'delta_ms' => null,
                'delta_percent' => null,
                'recent_count' => $recent->count(),
                'baseline_count' => $baseline->count(),
            ];
        }

        $baselineAvg = round((float) $baseline->avg('avg_latency_ms'), 2);
        $baselineLoss = round((float) $baseline->avg('packet_loss_percent'), 2);

        $deltaMs = round($recentAvg - $baselineAvg, 2);
        $deltaPercent = $baselineAvg > 0 ? round(($deltaMs / $baselineAvg) * 100, 1) : 0.0;
        $lossDelta = round($recentLoss - $baselineLoss, 2);

        [$trend, $label] = $this->classify($deltaPercent, $lossDelta);

        return [
            'trend' => $trend,
            'trend_label' => $label,
            'last' => $lastPayload,
            'recent_avg_ms' => $recentAvg,
            'baseline_avg_ms' => $baselineAvg,
            'delta_ms' => $deltaMs,
            'delta_percent' => $deltaPercent,
            'recent_loss_percent' => $recentLoss,
            'baseline_loss_percent' => $baselineLoss,
            'loss_delta_percent' => $lossDelta,
            'recent_count' => $recent->count(),
            'baseline_count' => $baseline->count(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function classify(float $deltaPercent, float $lossDelta): array
    {
        // Packet loss regression trumps a small latency improvement.
        if ($lossDelta >= 5.0) {
            return ['degrading', 'kötüleşiyor (paket kaybı arttı)'];
        }

        if ($deltaPercent <= -20.0) {
            return ['improving', 'belirgin şekilde iyileşiyor'];
        }

        if ($deltaPercent <= -8.0) {
            return ['improving', 'iyileşiyor'];
        }

        if ($deltaPercent >= 20.0) {
            return ['degrading', 'belirgin şekilde kötüleşiyor'];
        }

        if ($deltaPercent >= 8.0) {
            return ['degrading', 'kötüleşiyor'];
        }

        return ['stable', 'stabil'];
    }
}
