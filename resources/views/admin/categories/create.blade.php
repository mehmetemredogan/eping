<x-admin-layout :header="__('admin.new_category_title')">
    <x-admin-back-link :href="route('admin.categories.index')" :label="__('admin.back_to_categories')" />

    @include('admin.categories._form')
</x-admin-layout>
