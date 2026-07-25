<x-admin-layout :header="__('admin.edit_provider_title')">
    <x-admin-back-link :href="route('admin.providers.index')" :label="__('admin.back_to_providers')" />

    @include('admin.providers._form', ['provider' => $provider, 'targetCount' => $targetCount])
</x-admin-layout>
