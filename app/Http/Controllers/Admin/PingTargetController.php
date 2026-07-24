<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PingTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.\-:]+$/'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(PingTarget::categories()))],
            'provider' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] ??= 0;

        PingTarget::create($validated);

        return redirect()->route('admin.targets.index')->with('success', __('admin.target_created'));
    }

    public function edit(PingTarget $target): View
    {
        return view('admin.targets.edit', [
            'target' => $target,
            'categories' => PingTarget::categories(),
        ]);
    }

    public function update(Request $request, PingTarget $target): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.\-:]+$/'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(PingTarget::categories()))],
            'provider' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $target->update($validated);

        return redirect()->route('admin.targets.index')->with('success', __('admin.target_updated'));
    }

    public function destroy(PingTarget $target): RedirectResponse
    {
        $target->delete();

        return redirect()->route('admin.targets.index')->with('success', __('admin.target_deleted'));
    }
}
