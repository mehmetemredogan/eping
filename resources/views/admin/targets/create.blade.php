<x-admin-layout :header="__('admin.new_target_title')">
    @include('admin.targets._form', ['categories' => $categories, 'providers' => $providers])
</x-admin-layout>
