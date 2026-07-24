<x-admin-layout :header="__('admin.targets_title')">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_placeholder') }}"
                class="border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
            <select name="category" data-width="200px">
                <option value="">{{ __('admin.all') }}</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(request('category') == $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="border border-neutral-300 bg-white px-4 py-2 text-sm hover:bg-neutral-50">{{ __('admin.filter') }}</button>
        </form>
        <a href="{{ route('admin.targets.create') }}" class="border border-neutral-950 bg-neutral-950 px-4 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
            + {{ __('admin.new_target') }}
        </a>
    </div>

    <div class="overflow-x-auto border border-neutral-950 bg-white">
        <table class="js-datatable w-full min-w-[720px] text-left text-sm" data-dt-paging="false" data-dt-nosort="5">
            <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_host') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_category') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_location') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_status') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('admin.col_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($targets as $target)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="px-4 py-3 font-medium">{{ $target->name }}</td>
                        <td class="px-4 py-3 mono text-xs text-neutral-600">{{ $target->host }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $target->category_label }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $target->location }}</td>
                        <td class="px-4 py-3">
                            @if($target->is_active)
                                <span class="text-green-600">{{ __('admin.status_active') }}</span>
                            @else
                                <span class="text-neutral-400">{{ __('admin.status_inactive') }}</span>
                            @endif
                        </td>
                        <td class="space-x-3 px-4 py-3 text-right text-xs">
                            <a href="{{ route('admin.targets.edit', $target) }}" class="text-neutral-950 underline-offset-2 hover:underline">{{ __('admin.edit') }}</a>
                            <form action="{{ route('admin.targets.destroy', $target) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('admin.delete_confirm') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">{{ __('admin.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-neutral-400">{{ __('admin.no_targets') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $targets->links() }}</div>
</x-admin-layout>
