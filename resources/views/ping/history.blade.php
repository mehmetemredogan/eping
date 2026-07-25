<x-ping-layout>
    <x-page-shell>
        <x-page-header
            :eyebrow="__('ping.nav_history')"
            :title="__('ping.history_title')"
            :subtitle="__('ping.history_subtitle')"
        />

        <form method="GET" class="mb-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="w-full sm:w-auto">
                <x-form-label for="history-date" :value="__('ping.history_date')" />
                <select id="history-date" name="date" class="js-select2 w-full border border-neutral-300 sm:w-auto" data-width="100%" data-minimum-results-for-search="Infinity">
                    <option value="">{{ __('ping.history_all_dates') }}</option>
                    @foreach($availableDates as $day)
                        <option value="{{ $day }}" @selected($selectedDate === (string) $day)>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui-button type="submit" variant="secondary">{{ __('ping.filter') }}</x-ui-button>
            </div>
        </form>

        @forelse($groupedByDate as $day => $results)
            <section class="mb-6 border border-neutral-950">
                <div class="flex items-center justify-between border-b border-neutral-950 bg-neutral-50 px-4 py-3">
                    <h2 class="mono text-sm font-semibold">{{ $day }}</h2>
                    <span class="mono border border-neutral-300 bg-white px-2 py-0.5 text-xs text-neutral-500">{{ $results->count() }}</span>
                </div>

                <div class="divide-y divide-neutral-100 md:hidden">
                    @foreach($results as $result)
                        <a href="{{ route('history.show', $result) }}" class="block px-4 py-3 transition-colors hover:bg-neutral-50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-neutral-950">{{ $result->target?->name ?? '—' }}</div>
                                    <div class="mt-0.5 mono break-all text-xs text-neutral-500">{{ $result->target?->host ?? '—' }}</div>
                                    <div class="mt-1 text-xs text-neutral-400">{{ $result->tested_at?->format('H:i:s') }}</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <x-latency :ms="$result->avg_latency_ms" class="text-sm font-medium" />
                                    <div class="mt-1">
                                        <x-status-badge :status="$result->status" />
                                    </div>
                                    <div class="mt-2 text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.history_open_detail') }} →</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="js-datatable w-full text-left text-sm" data-dt-paging="false" data-dt-nosort="7">
                        <thead>
                            <tr class="border-b border-neutral-100 text-[10px] uppercase tracking-widest text-neutral-400">
                                <th class="px-4 py-2 font-medium">{{ __('ping.history_time') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.target') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.host') }}</th>
                                <th class="px-4 py-2 font-medium text-right">{{ __('ping.latency') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.history_status') }}</th>
                                <th class="px-4 py-2 font-medium">IP</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.your_dns') }}</th>
                                <th class="px-4 py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                                    <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $result->tested_at?->format('H:i:s') }}</td>
                                    <td class="px-4 py-3 font-medium">
                                        <a href="{{ route('history.show', $result) }}" class="hover:underline">{{ $result->target?->name ?? '—' }}</a>
                                    </td>
                                    <td class="px-4 py-3 mono text-xs text-neutral-600">{{ $result->target?->host ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right"><x-latency :ms="$result->avg_latency_ms" /></td>
                                    <td class="px-4 py-3"><x-status-badge :status="$result->status" /></td>
                                    <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $result->resolved_ip ?? '—' }}</td>
                                    <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $result->client_dns['dns']['ip'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('history.show', $result) }}" class="text-xs uppercase tracking-widest text-neutral-500 hover:text-neutral-950">
                                            {{ __('ping.history_open_detail') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="border border-neutral-950 py-16 text-center text-sm text-neutral-500">
                {{ __('ping.history_empty') }}
            </div>
        @endforelse
    </x-page-shell>
</x-ping-layout>
