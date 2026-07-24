<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PingTarget;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    /**
     * Active ping targets for the desktop UI, grouped by provider.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PingTarget::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
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

        $targets = $query->get();

        // Normalize empty provider names up front so grouping and the
        // Provider lookup both key off the same "Other" bucket.
        $providerName = fn (PingTarget $t) => $t->provider ?: 'Other';

        $providerDescriptions = Provider::query()
            ->whereIn('name', $targets->map($providerName)->unique())
            ->get()
            ->mapWithKeys(fn (Provider $p) => [
                $p->name => [
                    'markdown' => $p->description,
                    'html' => $p->description_html,
                ],
            ]);

        $payload = $targets->map(fn (PingTarget $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'host' => $t->host,
            'category' => $t->category,
            'category_label' => $t->category_label,
            'provider' => $providerName($t),
            'location' => $t->location,
            'country_code' => $t->country_code ? strtoupper($t->country_code) : null,
            'description' => $t->description,
        ])->values();

        $grouped = $payload
            ->groupBy('provider')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(function ($items, $provider) use ($providerDescriptions) {
                $meta = $providerDescriptions->get($provider);

                return [
                    'provider' => $provider,
                    'description_markdown' => $meta['markdown'] ?? null,
                    'description_html' => $meta['html'] ?? null,
                    'targets' => $items->values(),
                ];
            })
            ->values();

        return response()->json([
            'count' => $payload->count(),
            'categories' => PingTarget::categories(),
            'groups' => $grouped,
            'targets' => $payload,
        ]);
    }
}
