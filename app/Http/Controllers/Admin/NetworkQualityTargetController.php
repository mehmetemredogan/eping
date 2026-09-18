<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkQualityTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NetworkQualityTargetController extends Controller
{
    public function index(Request $request): View
    {
        $targets = NetworkQualityTarget::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = [
            'search' => 'Arama Motorları',
            'cdn' => 'CDN & DNS',
            'cloud' => 'Bulut Servisleri',
            'streaming' => 'Medya & Video',
            'ecommerce' => 'E-Ticaret',
            'gov' => 'Kamu & e-Devlet',
            'hosting' => 'Sunucu & Barındırma',
            'dev' => 'Geliştirici & Kod',
            'web' => 'Genel Web',
        ];

        return view('admin.quality-targets.index', [
            'targets' => $targets,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        $categories = [
            'search' => 'Arama Motorları',
            'cdn' => 'CDN & DNS',
            'cloud' => 'Bulut Servisleri',
            'streaming' => 'Medya & Video',
            'ecommerce' => 'E-Ticaret',
            'gov' => 'Kamu & e-Devlet',
            'hosting' => 'Sunucu & Barındırma',
            'dev' => 'Geliştirici & Kod',
            'web' => 'Genel Web',
        ];

        return view('admin.quality-targets.create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        NetworkQualityTarget::create($validated);

        return redirect()->route('admin.quality-targets.index')
            ->with('success', 'Ağ kalitesi hedefi başarıyla eklendi.');
    }

    public function edit(NetworkQualityTarget $qualityTarget): View
    {
        $categories = [
            'search' => 'Arama Motorları',
            'cdn' => 'CDN & DNS',
            'cloud' => 'Bulut Servisleri',
            'streaming' => 'Medya & Video',
            'ecommerce' => 'E-Ticaret',
            'gov' => 'Kamu & e-Devlet',
            'hosting' => 'Sunucu & Barındırma',
            'dev' => 'Geliştirici & Kod',
            'web' => 'Genel Web',
        ];

        return view('admin.quality-targets.edit', [
            'target' => $qualityTarget,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, NetworkQualityTarget $qualityTarget): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $qualityTarget->update($validated);

        return redirect()->route('admin.quality-targets.index')
            ->with('success', 'Ağ kalitesi hedefi güncellendi.');
    }

    public function destroy(NetworkQualityTarget $qualityTarget): RedirectResponse
    {
        $qualityTarget->delete();

        return redirect()->route('admin.quality-targets.index')
            ->with('success', 'Ağ kalitesi hedefi silindi.');
    }
}
