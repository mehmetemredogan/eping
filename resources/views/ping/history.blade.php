<x-ping-layout>
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <div class="bg-grid mb-8 border border-neutral-950 px-4 py-6 sm:px-6">
            <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ __('ping.nav_history') }}</p>
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('ping.history_title') }}</h1>
            <p class="mt-2 text-xs text-neutral-500 sm:text-sm">{{ __('ping.history_subtitle') }}</p>
        </div>

        <form method="GET" class="mb-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="w-full sm:w-auto">
                <label for="history-date" class="mb-1 block text-xs font-medium uppercase tracking-wider text-neutral-400">{{ __('ping.history_date') }}</label>
                <select id="history-date" name="date" class="js-select2 w-full border border-neutral-300 sm:w-auto" data-width="100%" data-minimum-results-for-search="Infinity">
                    <option value="">{{ __('ping.history_all_dates') }}</option>
                    @foreach($availableDates as $day)
                        <option value="{{ $day }}" @selected($selectedDate === (string) $day)>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="border border-neutral-950 bg-white px-4 py-2 text-sm transition-colors hover:bg-neutral-950 hover:text-white">{{ __('ping.filter') }}</button>
                <a href="{{ route('ping.index') }}" class="px-4 py-2 text-sm text-neutral-500 hover:text-neutral-950">← {{ __('ping.back_to_test') }}</a>
            </div>
        </form>

        @forelse($groupedByDate as $day => $results)
            <section class="mb-6 border border-neutral-950">
                <div class="flex items-center justify-between border-b border-neutral-950 bg-neutral-50 px-4 py-3">
                    <h2 class="mono text-sm font-semibold">{{ $day }}</h2>
                    <span class="mono border border-neutral-300 bg-white px-2 py-0.5 text-xs text-neutral-500">{{ $results->count() }}</span>
                </div>

                {{-- Mobile --}}
                <div class="divide-y divide-neutral-100 md:hidden">
                    @foreach($results as $result)
                        @php
                            $ms = $result->avg_latency_ms !== null ? (float) $result->avg_latency_ms : null;
                            $latencyTone = $ms === null ? 'text-neutral-400' : ($ms < 80 ? 'text-green-600' : ($ms < 180 ? 'text-yellow-600' : 'text-red-600'));
                        @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-neutral-950">{{ $result->target?->name ?? '—' }}</div>
                                    <div class="mt-0.5 mono break-all text-xs text-neutral-500">{{ $result->target?->host ?? '—' }}</div>
                                    <div class="mt-1 text-xs text-neutral-400">{{ $result->tested_at?->format('H:i:s') }}</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="mono text-sm font-medium {{ $latencyTone }}">
                                        {{ $ms !== null ? $ms.' ms' : '—' }}
                                    </div>
                                    <div class="mt-1 text-xs">
                                        @if($result->status === 'success')
                                            <span class="text-green-600">{{ __('ping.status_success') }}</span>
                                        @elseif($result->status === 'timeout')
                                            <span class="text-yellow-600">{{ __('ping.status_timeout') }}</span>
                                        @else
                                            <span class="text-red-600">{{ __('ping.status_failed') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 grid gap-1 text-xs text-neutral-500">
                                <div>IP: <span class="mono">{{ $result->resolved_ip ?? '—' }}</span></div>
                                @if(!empty($result->client_dns['dns']['ip']))
                                    <div>{{ __('ping.your_dns') }}: <span class="mono">{{ $result->client_dns['dns']['ip'] }}</span></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="js-datatable w-full text-left text-sm" data-dt-paging="false">
                        <thead>
                            <tr class="border-b border-neutral-100 text-xs uppercase tracking-wider text-neutral-400">
                                <th class="px-4 py-2 font-medium">{{ __('ping.history_time') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.target') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.host') }}</th>
                                <th class="px-4 py-2 font-medium text-right">{{ __('ping.latency') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.history_status') }}</th>
                                <th class="px-4 py-2 font-medium">IP</th>
                                <th class="px-4 py-2 font-medium">{{ __('ping.your_dns') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                @php
                                    $ms = $result->avg_latency_ms !== null ? (float) $result->avg_latency_ms : null;
                                    $latencyTone = $ms === null ? 'text-neutral-400' : ($ms < 80 ? 'text-green-600' : ($ms < 180 ? 'text-yellow-600' : 'text-red-600'));
                                @endphp
                                <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                                    <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $result->tested_at?->format('H:i:s') }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $result->target?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 mono text-xs text-neutral-600">{{ $result->target?->host ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right mono {{ $latencyTone }}">
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
                                    <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $result->resolved_ip ?? '—' }}</td>
                                    <td class="px-4 py-3 mono text-xs text-neutral-500">{{ $result->client_dns['dns']['ip'] ?? '—' }}</td>
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
    </div>
</x-ping-layout>
