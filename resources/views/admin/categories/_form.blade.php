@php
    $isEdit = isset($category);
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-2xl space-y-4 border border-neutral-950 bg-white p-5">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div>
        <label for="slug" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('admin.field_slug') }} *</label>
        <input
            id="slug"
            type="text"
            name="slug"
            value="{{ old('slug', $category->slug ?? '') }}"
            required
            pattern="[a-z0-9_]+"
            @disabled(($category->slug ?? null) === 'other')
            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0 disabled:bg-neutral-100"
        >
        @if(($category->slug ?? null) === 'other')
            <input type="hidden" name="slug" value="other">
        @endif
        <p class="mt-1 text-[11px] text-neutral-400">{{ __('admin.field_slug_hint') }}</p>
        @error('slug') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name_tr" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('admin.field_name_tr') }} *</label>
            <input id="name_tr" type="text" name="name_tr" value="{{ old('name_tr', $category->name_tr ?? '') }}" required
                class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            @error('name_tr') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>
        <div>
            <label for="name_en" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('admin.field_name_en') }} *</label>
            <input id="name_en" type="text" name="name_en" value="{{ old('name_en', $category->name_en ?? '') }}" required
                class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            @error('name_en') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="sort_order" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('admin.field_sort_order') }}</label>
        <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" max="9999"
            class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0 sm:w-40">
        @error('sort_order') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
    </div>

    @if($isEdit && isset($targetCount))
        <p class="mono text-xs text-neutral-500">{{ __('admin.category_target_count', ['count' => $targetCount]) }}</p>
    @endif

    <div class="flex gap-3 pt-2">
        <button type="submit" class="border border-neutral-950 bg-neutral-950 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">{{ __('admin.save') }}</button>
        <a href="{{ route('admin.categories.index') }}" class="border border-neutral-300 bg-white px-5 py-2 text-sm hover:bg-neutral-50">{{ __('admin.cancel') }}</a>
    </div>
</form>
