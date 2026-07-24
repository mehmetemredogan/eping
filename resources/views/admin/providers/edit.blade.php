<x-admin-layout :header="__('admin.edit_provider_title')">
    <div class="mb-4">
        <a href="{{ route('admin.providers.index') }}" class="text-sm text-neutral-500 hover:text-neutral-950">{{ __('admin.back_to_providers') }}</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.providers.update', $provider) }}" class="space-y-4 border border-neutral-950 bg-white p-5">
            @csrf
            @method('PUT')

            <div>
                <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('admin.col_provider') }}</div>
                <div class="mt-1 text-sm font-semibold">{{ $provider->name }}</div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('admin.description_markdown_label') }}</label>
                <textarea id="description" name="description" rows="8"
                    class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                    placeholder="{{ __('admin.description_placeholder') }}">{{ old('description', $provider->description) }}</textarea>
                <p class="mt-1 text-[11px] text-neutral-400">{{ __('admin.description_hint') }}</p>
                @error('description') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="border border-neutral-950 bg-neutral-950 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">{{ __('admin.save') }}</button>
                <a href="{{ route('admin.providers.index') }}" class="border border-neutral-300 bg-white px-5 py-2 text-sm hover:bg-neutral-50">{{ __('admin.cancel') }}</a>
            </div>
        </form>

        <div class="border border-neutral-950 bg-white p-5">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest">{{ __('admin.preview') }}</h2>
            @if($provider->description_html)
                <div class="md text-sm leading-relaxed text-neutral-600">{!! $provider->description_html !!}</div>
            @else
                <p class="text-sm text-neutral-400">{{ __('admin.no_description') }}</p>
            @endif
        </div>
    </div>
</x-admin-layout>
