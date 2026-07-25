<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PingTarget;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function index(): View
    {
        $this->syncFromTargets();

        $providers = Provider::query()
            ->orderBy('name')
            ->get();

        $targetCounts = PingTarget::query()
            ->selectRaw('provider, count(*) as total')
            ->whereNotNull('provider')
            ->groupBy('provider')
            ->pluck('total', 'provider');

        return view('admin.providers.index', [
            'providers' => $providers,
            'targetCounts' => $targetCounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.providers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Provider::create($validated);

        return redirect()
            ->route('admin.providers.index')
            ->with('success', __('admin.provider_created'));
    }

    public function edit(Provider $provider): View
    {
        return view('admin.providers.edit', [
            'provider' => $provider,
            'targetCount' => PingTarget::query()->where('provider', $provider->name)->count(),
        ]);
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $validated = $this->validated($request, $provider);
        $oldName = $provider->name;

        DB::transaction(function () use ($provider, $validated, $oldName) {
            $provider->update($validated);

            if ($oldName !== $provider->name) {
                PingTarget::query()
                    ->where('provider', $oldName)
                    ->update(['provider' => $provider->name]);
            }
        });

        return redirect()
            ->route('admin.providers.index')
            ->with('success', __('admin.provider_updated', ['name' => $provider->name]));
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        $inUse = PingTarget::query()->where('provider', $provider->name)->exists();

        if ($inUse) {
            return redirect()
                ->route('admin.providers.index')
                ->with('error', __('admin.provider_in_use'));
        }

        $provider->delete();

        return redirect()
            ->route('admin.providers.index')
            ->with('success', __('admin.provider_deleted'));
    }

    /**
     * @return array{name: string, description: ?string}
     */
    private function validated(Request $request, ?Provider $provider = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Provider::class, 'name')->ignore($provider?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['description'] = $validated['description'] ?? null;

        return $validated;
    }

    /**
     * Ensure every provider used by ping targets has a row.
     */
    private function syncFromTargets(): void
    {
        $known = Provider::query()->pluck('name');

        $missing = PingTarget::query()
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->distinct()
            ->pluck('provider')
            ->diff($known);

        foreach ($missing as $name) {
            Provider::query()->firstOrCreate(['name' => $name]);
        }
    }
}
