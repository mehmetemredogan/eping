<x-admin-layout :header="__('admin.new_provider_title')">
    <div class="mb-4">
        <a href="{{ route('admin.providers.index') }}" class="text-sm text-neutral-500 hover:text-neutral-950">{{ __('admin.back_to_providers') }}</a>
    </div>

    @include('admin.providers._form')
</x-admin-layout>
