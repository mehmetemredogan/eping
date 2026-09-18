<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetworkQualityTest;
use App\Services\FreeIpApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkQualityController extends Controller
{
    /**
     * Store a Network Quality Test submitted by the client (TUI, CLI, daemon).
     */
    public function store(Request $request, FreeIpApiService $freeIpApi): JsonResponse
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'grade' => ['required', 'string', 'max:8'],
            'status' => ['required', 'string', 'in:excellent,good,fair,poor,degraded,unreachable,unknown,critical'],
            'summary' => ['nullable', 'string', 'max:255'],
            'avg_latency_ms' => ['nullable', 'numeric', 'min:0'],
            'avg_dns_ms' => ['nullable', 'numeric', 'min:0'],
            'avg_tcp_ms' => ['nullable', 'numeric', 'min:0'],
            'avg_tls_ms' => ['nullable', 'numeric', 'min:0'],
            'avg_ttfb_ms' => ['nullable', 'numeric', 'min:0'],
            'packet_loss_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'connection_type' => ['nullable', 'string', 'in:wifi,ethernet,unknown'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.name' => ['nullable', 'string'],
            'results.*.domain' => ['nullable', 'string'],
            'results.*.category' => ['nullable', 'string'],
            'results.*.ok' => ['required', 'boolean'],
            'results.*.status_code' => ['nullable', 'integer'],
            'results.*.dns_ms' => ['nullable', 'numeric'],
            'results.*.tcp_ms' => ['nullable', 'numeric'],
            'results.*.tls_ms' => ['nullable', 'numeric'],
            'results.*.ttfb_ms' => ['nullable', 'numeric'],
            'results.*.total_ms' => ['nullable', 'numeric'],
            'results.*.error' => ['nullable', 'string'],
            'insights' => ['nullable', 'array'],
        ]);

        $clientIp = $request->ip();
        $clientGeo = null;
        try {
            $clientGeo = $freeIpApi->lookup($clientIp);
        } catch (\Throwable) {
            $clientGeo = null;
        }

        $test = NetworkQualityTest::create([
            'user_id' => $request->user()?->id,
            'score' => $validated['score'],
            'grade' => $validated['grade'],
            'status' => $validated['status'],
            'summary' => $validated['summary'] ?? null,
            'avg_latency_ms' => $validated['avg_latency_ms'] ?? null,
            'avg_dns_ms' => $validated['avg_dns_ms'] ?? null,
            'avg_tcp_ms' => $validated['avg_tcp_ms'] ?? null,
            'avg_tls_ms' => $validated['avg_tls_ms'] ?? null,
            'avg_ttfb_ms' => $validated['avg_ttfb_ms'] ?? null,
            'packet_loss_percent' => $validated['packet_loss_percent'] ?? 0,
            'client_ip' => $clientIp,
            'client_geo' => $clientGeo,
            'client_asn' => $clientGeo['asn'] ?? null,
            'client_isp' => $clientGeo['asnOrganization'] ?? null,
            'client_country_code' => isset($clientGeo['countryCode']) ? strtoupper((string) $clientGeo['countryCode']) : null,
            'connection_type' => $validated['connection_type'] ?? 'unknown',
            'results' => $validated['results'],
            'insights' => $validated['insights'] ?? [],
            'tested_at' => now(),
        ]);

        return response()->json([
            'id' => $test->id,
            'score' => $test->score,
            'grade' => $test->grade,
            'status' => $test->status,
            'summary' => $test->summary,
            'tested_at' => $test->tested_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Get the most recent network quality test.
     */
    public function latest(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $query = NetworkQualityTest::query()->latest('tested_at');
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $latest = $query->first();
        if (! $latest) {
            return response()->json(['message' => 'No test found'], 404);
        }

        return response()->json([
            'test' => $latest,
        ]);
    }

    /**
     * List recent network quality tests for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tests = NetworkQualityTest::query()
            ->where('user_id', $request->user()->id)
            ->latest('tested_at')
            ->limit($validated['limit'] ?? 25)
            ->get();

        return response()->json([
            'count' => $tests->count(),
            'tests' => $tests,
        ]);
    }

    /**
     * Active network quality targets for dynamic client probing.
     */
    public function targets(): JsonResponse
    {
        $targets = \App\Models\NetworkQualityTarget::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'url', 'domain', 'category']);

        return response()->json([
            'count' => $targets->count(),
            'targets' => $targets,
        ]);
    }
}

