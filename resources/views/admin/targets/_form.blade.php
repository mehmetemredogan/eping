@php
    $isEdit = isset($target);
    $initialCategory = old('category', $target->category ?? (array_key_first($categories) ?: ''));
    $initialSort = old('sort_order', $target->sort_order ?? '');
    $sortOrderConfig = [
        'url' => route('admin.targets.next-sort-order'),
        'excludeId' => $isEdit ? $target->id : null,
        'autoOnLoad' => ! $isEdit && old('sort_order') === null,
    ];
@endphp

{{-- Single-quoted x-data: @js() emits double quotes and would break x-data="..." --}}
<form
    method="POST"
    action="{{ $isEdit ? route('admin.targets.update', $target) : route('admin.targets.store') }}"
    class="max-w-2xl space-y-4"
    x-data='targetSortOrder({{ \Illuminate\Support\Js::from($sortOrderConfig) }})'
>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_name') }} *</label>
        <input type="text" name="name" value="{{ old('name', $target->name ?? '') }}" required
            class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_host') }} *</label>
        <input type="text" name="host" value="{{ old('host', $target->host ?? '') }}" required placeholder="{{ __('admin.field_host_placeholder') }}"
            class="w-full border border-neutral-300 bg-white px-3 py-2 mono text-sm focus:border-neutral-950 focus:outline-none">
        @error('host') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_category') }} *</label>
            <select name="category" required data-width="100%" x-ref="category" @change="onCategoryChange()">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected($initialCategory == $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_provider') }}</label>
            @php
                $providerValue = old('provider', $target->provider ?? '');
                $providerList = $providers ?? [];
                if (filled($providerValue) && ! in_array($providerValue, $providerList, true)) {
                    $providerList[] = $providerValue;
                    sort($providerList, SORT_NATURAL | SORT_FLAG_CASE);
                }
            @endphp
            <select name="provider" data-width="100%" data-placeholder="{{ __('admin.field_provider_none') }}">
                <option value="">{{ __('admin.field_provider_none') }}</option>
                @foreach($providerList as $providerName)
                    <option value="{{ $providerName }}" @selected($providerValue === $providerValue)>{{ $providerName }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-[11px] text-neutral-400">{{ __('admin.field_provider_hint') }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_location') }}</label>
            <input type="text" name="location" value="{{ old('location', $target->location ?? '') }}" placeholder="{{ __('admin.field_location_placeholder') }}"
                class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_country_code') }}</label>
            <input type="text" name="country_code" value="{{ old('country_code', $target->country_code ?? '') }}" maxlength="2" placeholder="DE"
                class="w-full border border-neutral-300 bg-white px-3 py-2 mono text-sm uppercase focus:border-neutral-950 focus:outline-none">
        </div>
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_description') }}</label>
        <textarea name="description" rows="3" class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">{{ old('description', $target->description ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('admin.field_sort_order') }}</label>
            <input
                type="number"
                name="sort_order"
                x-ref="sortOrder"
                value="{{ $initialSort === '' ? '' : $initialSort }}"
                min="0"
                @input="manual = true"
                class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none"
            >
            <p class="mt-1 text-[11px] text-neutral-400">{{ __('admin.field_sort_order_hint') }}</p>
            <p x-show="loading" x-cloak class="mono mt-1 text-[11px] text-neutral-400">…</p>
        </div>
        <div class="flex items-center pt-6">
            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $target->is_active ?? true))
                    class="border-neutral-300 text-neutral-950 focus:ring-neutral-950">
                {{ __('admin.field_active') }}
            </label>
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="bg-neutral-950 px-5 py-2 text-sm font-medium text-white hover:bg-neutral-800">{{ __('admin.save') }}</button>
        <a href="{{ route('admin.targets.index') }}" class="border border-neutral-300 bg-white px-5 py-2 text-sm hover:bg-neutral-50">{{ __('admin.cancel') }}</a>
    </div>
</form>
