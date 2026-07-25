<x-admin-layout :header="__('admin.providers_title')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-neutral-500">{{ __('admin.providers_hint') }}</p>
        <a href="{{ route('admin.providers.create') }}" class="border border-neutral-950 bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
            {{ __('admin.new_provider') }}
        </a>
    </div>

    <div class="overflow-x-auto border border-neutral-950 bg-white">
        <table class="js-datatable w-full text-left text-sm" data-dt-per-page="25" data-dt-nosort="3">
            <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_provider') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_target_count') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_description') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('admin.col_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($providers as $provider)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $provider->name }}</td>
                        <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $targetCounts[$provider->name] ?? 0 }}</td>
                        <td class="max-w-[28rem] px-4 py-3 text-xs text-neutral-500">
                            @if(filled($provider->description))
                                <span class="line-clamp-2">{{ \Illuminate\Support\Str::limit($provider->description, 160) }}</span>
                            @else
                                <span class="text-neutral-300">{{ __('admin.no_description_dash') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            <a href="{{ route('admin.providers.edit', $provider) }}" class="text-neutral-950 underline-offset-2 hover:underline">{{ __('admin.edit') }}</a>
                            @if(($targetCounts[$provider->name] ?? 0) === 0)
                                <form method="POST" action="{{ route('admin.providers.destroy', $provider) }}" class="ml-3 inline" onsubmit="return confirm(@js(__('admin.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-700 underline-offset-2 hover:underline">{{ __('admin.delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-neutral-400">{{ __('admin.no_providers') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
