<x-admin-layout :header="__('admin.categories_title')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-neutral-500">{{ __('admin.categories_hint') }}</p>
        <a href="{{ route('admin.categories.create') }}" class="border border-neutral-950 bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
            {{ __('admin.new_category') }}
        </a>
    </div>

    <div class="overflow-x-auto border border-neutral-950 bg-white">
        <table class="js-datatable w-full text-left text-sm" data-dt-per-page="25" data-dt-nosort="5">
            <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_slug') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_name_tr') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_name_en') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.field_sort_order') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_target_count') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('admin.col_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="px-4 py-3 mono text-xs">{{ $category->slug }}</td>
                        <td class="px-4 py-3 font-medium">{{ $category->name_tr }}</td>
                        <td class="px-4 py-3">{{ $category->name_en }}</td>
                        <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $targetCounts[$category->slug] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-xs">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-neutral-950 underline-offset-2 hover:underline">{{ __('admin.edit') }}</a>
                            @if($category->slug !== 'other' && ($targetCounts[$category->slug] ?? 0) === 0)
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="ml-3 inline" onsubmit="return confirm(@js(__('admin.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-700 underline-offset-2 hover:underline">{{ __('admin.delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-neutral-400">{{ __('admin.no_categories') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
