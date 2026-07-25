<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PingTarget;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PingTargetController extends Controller
{
    public function index(Request $request): View
    {
        $targets = PingTarget::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('host', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.targets.index', [
            'targets' => $targets,
            'categories' => PingTarget::categories(),
        ]);
    }

    public function create(): View
    {
        return view('admin.targets.create', [
            'categories' => PingTarget::categories(),
            'providers' => $this->providerOptions(),
        ]);
    }

    /**
     * Next sort_order for a category: max(existing) + 1 (or 1 when empty).
     */
    public function nextSortOrder(Request $request): JsonResponse
    {
        $categorySlugs = array_keys(PingTarget::categories());

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in($categorySlugs)],
            'exclude_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $max = PingTarget::query()
            ->where('category', $validated['category'])
            ->when(
                filled($validated['exclude_id'] ?? null),
                fn ($q) => $q->where('id', '!=', (int) $validated['exclude_id'])
            )
            ->max('sort_order');

        return response()->json([
            'category' => $validated['category'],
            'next' => ((int) $max) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] ??= 0;
        $this->ensureProvider($validated['provider'] ?? null);

        PingTarget::create($validated);

        return redirect()->route('admin.targets.index')->with('success', __('admin.target_created'));
    }

    public function edit(PingTarget $target): View
    {
        return view('admin.targets.edit', [
            'target' => $target,
            'categories' => PingTarget::categories(),
            'providers' => $this->providerOptions(),
        ]);
    }

    public function update(Request $request, PingTarget $target): RedirectResponse
    {
        $validated = $this->validated($request);

        $validated['is_active'] = $request->boolean('is_active');
        $this->ensureProvider($validated['provider'] ?? null);

        $target->update($validated);

        return redirect()->route('admin.targets.index')->with('success', __('admin.target_updated'));
    }

    public function destroy(PingTarget $target): RedirectResponse
    {
        $target->delete();

        return redirect()->route('admin.targets.index')->with('success', __('admin.target_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $categorySlugs = array_keys(PingTarget::categories());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.\-:]+$/'],
            'category' => ['required', 'string', Rule::in($categorySlugs)],
            'provider' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('provider', $validated)) {
            $validated['provider'] = filled($validated['provider'])
                ? trim((string) $validated['provider'])
                : null;
        }

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function providerOptions(): array
    {
        return Provider::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function ensureProvider(?string $name): void
    {
        if (! filled($name)) {
            return;
        }

        Provider::query()->firstOrCreate(['name' => $name]);
    }
}
