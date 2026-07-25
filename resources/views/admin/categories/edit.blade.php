<x-admin-layout :header="__('admin.edit_category_title')">
    <div class="mb-4">
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-neutral-500 hover:text-neutral-950">{{ __('admin.back_to_categories') }}</a>
    </div>

    @include('admin.categories._form', ['category' => $category, 'targetCount' => $targetCount])
</x-admin-layout>
