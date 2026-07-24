<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Services\NetworkTrendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * Raw result history for the authenticated user (desktop client), optionally
     * scoped to a single target.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['nullable', 'integer', 'exists:ping_targets,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = PingResult::query()
            ->with('target:id,name,host')
            ->where('user_id', $request->user()->id)
            ->latest('tested_at');

        if (! empty($validated['target_id'])) {
            $query->where('ping_target_id', $validated['target_id']);
        }

        $results = $query->limit($validated['limit'] ?? 50)->get();

        return response()->json([
            'count' => $results->count(),
            'results' => $results->map(fn (PingResult $r) => [
                'id' => $r->id,
                'target_id' => $r->ping_target_id,
                'target_name' => $r->target?->name,
                'target_host' => $r->target?->host,
                'status' => $r->status,
                'avg_latency_ms' => $r->avg_latency_ms,
                'min_latency_ms' => $r->min_latency_ms,
                'max_latency_ms' => $r->max_latency_ms,
                'jitter_ms' => $r->jitter_ms,
                'packet_loss_percent' => $r->packet_loss_percent,
                'network_status' => is_array($r->network_analysis) ? ($r->network_analysis['status'] ?? null) : null,
                'tested_at' => $r->tested_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Trend analysis: compares the authenticated user's most recent results
     * against their own historical baseline, per target and overall.
     * Optionally scope to a single target via ?target_id=.
     */
    public function trend(Request $request, NetworkTrendService $trendService): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['nullable', 'integer', 'exists:ping_targets,id'],
        ]);

        $user = $request->user();

        if (! empty($validated['target_id'])) {
            $target = PingTarget::findOrFail($validated['target_id']);

            return response()->json([
                'target' => $trendService->trendForTarget($user, $target),
            ]);
        }

        return response()->json($trendService->summaryForUser($user));
    }
}
