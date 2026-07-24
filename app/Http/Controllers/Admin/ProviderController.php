<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PingTarget;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function edit(Provider $provider): View
    {
        return view('admin.providers.edit', [
            'provider' => $provider,
        ]);
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $provider->update([
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.providers.index')
            ->with('success', __('admin.provider_description_updated', ['name' => $provider->name]));
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
