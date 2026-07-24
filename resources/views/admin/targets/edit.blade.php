<x-admin-layout :header="__('admin.edit_target_title')">
    @include('admin.targets._form', ['target' => $target, 'categories' => $categories])
</x-admin-layout>
