<x-admin-layout :header="__('admin.results_title')">
    <form method="GET" class="mb-6 flex flex-wrap gap-2">
        <select name="target_id" data-width="220px">
            <option value="">{{ __('admin.all_targets') }}</option>
            @foreach($targets as $t)
                <option value="{{ $t->id }}" @selected(request('target_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        <select name="status" data-width="170px" data-minimum-results-for-search="Infinity">
            <option value="">{{ __('admin.all_statuses') }}</option>
            <option value="success" @selected(request('status') == 'success')>{{ __('ping.status_success') }}</option>
            <option value="failed" @selected(request('status') == 'failed')>{{ __('ping.status_failed') }}</option>
            <option value="timeout" @selected(request('status') == 'timeout')>{{ __('ping.status_timeout') }}</option>
        </select>
        <input type="text" name="session_id" value="{{ request('session_id') }}" placeholder="{{ __('admin.session_id_placeholder') }}"
            class="border border-neutral-300 bg-white px-3 py-2 mono text-sm focus:border-neutral-950 focus:outline-none">
        <button class="border border-neutral-300 bg-white px-4 py-2 text-sm hover:bg-neutral-50">{{ __('admin.filter') }}</button>
    </form>

    <div class="overflow-x-auto border border-neutral-950 bg-white">
        <table class="js-datatable w-full text-left text-sm" data-dt-paging="false" data-dt-nosort="6">
            <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_date') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_target') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_ip') }}</th>
                    <th class="hidden px-4 py-3 font-medium lg:table-cell">{{ __('admin.col_client_dns') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_avg') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('admin.col_status') }}</th>
                    <th class="px-4 py-3 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $result)
                    @php
                        $ms = $result->avg_latency_ms !== null ? (float) $result->avg_latency_ms : null;
                        $latencyTone = $ms === null ? 'text-neutral-400' : ($ms < 80 ? 'text-green-600' : ($ms < 180 ? 'text-yellow-600' : 'text-red-600'));
                    @endphp
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="whitespace-nowrap px-4 py-3 text-neutral-500">{{ $result->tested_at?->format('d.m.Y H:i') }}</td>
                        <td class="max-w-[10rem] truncate px-4 py-3 font-medium sm:max-w-none">{{ $result->target?->name }}</td>
                        <td class="px-4 py-3 mono text-xs text-neutral-600">{{ $result->resolved_ip ?? '—' }}</td>
                        <td class="hidden px-4 py-3 mono text-xs text-neutral-500 lg:table-cell">{{ $result->client_dns['dns']['ip'] ?? '—' }}</td>
                        <td class="px-4 py-3 mono {{ $latencyTone }}">
                            {{ $ms !== null ? $ms.' ms' : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($result->status === 'success')
                                <span class="text-green-600">{{ __('ping.status_success') }}</span>
                            @elseif($result->status === 'timeout')
                                <span class="text-yellow-600">{{ __('ping.status_timeout') }}</span>
                            @else
                                <span class="text-red-600">{{ __('ping.status_failed') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            <a href="{{ route('admin.results.show', $result) }}" class="text-neutral-950 hover:underline">{{ __('admin.col_details') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-neutral-400">{{ __('admin.no_logs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $results->links() }}</div>
</x-admin-layout>
