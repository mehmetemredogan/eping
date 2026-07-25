<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PingTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();

        $targetCounts = PingTarget::query()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('admin.categories.index', [
            'categories' => $categories,
            'targetCounts' => $targetCounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('admin.category_created'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'targetCount' => PingTarget::query()->where('category', $category->slug)->count(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validated($request, $category);
        $oldSlug = $category->slug;

        DB::transaction(function () use ($category, $validated, $oldSlug) {
            $category->update($validated);

            if ($oldSlug !== $category->slug) {
                PingTarget::query()
                    ->where('category', $oldSlug)
                    ->update(['category' => $category->slug]);
            }
        });

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('admin.category_updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $inUse = PingTarget::query()->where('category', $category->slug)->exists();

        if ($inUse) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', __('admin.category_in_use'));
        }

        if ($category->slug === 'other') {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', __('admin.category_other_protected'));
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('admin.category_deleted'));
    }

    /**
     * @return array{slug: string, name_tr: string, name_en: string, sort_order: int}
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique(Category::class, 'slug')->ignore($category?->id),
            ],
            'name_tr' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'slug.regex' => __('admin.category_slug_format'),
        ]);

        $validated['slug'] = Str::lower($validated['slug']);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($category?->slug === 'other' && $validated['slug'] !== 'other') {
            throw ValidationException::withMessages([
                'slug' => __('admin.category_other_protected'),
            ]);
        }

        return $validated;
    }
}
