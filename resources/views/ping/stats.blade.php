<x-ping-layout>
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <div class="bg-grid mb-8 border border-neutral-950 px-4 py-6 sm:px-6">
            <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ __('ping.nav_stats') }}</p>
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('ping.stats_title') }}</h1>
            <p class="mt-2 max-w-3xl text-xs text-neutral-500 sm:text-sm">{{ __('ping.stats_subtitle') }}</p>
            <p class="mono mt-3 text-[11px] text-neutral-400">{{ __('ping.stats_privacy_note') }}</p>
        </div>

        <form method="GET" class="mb-8 grid gap-3 border border-neutral-200 bg-neutral-50 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="stats-isp" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.stats_isp') }}</label>
                <select id="stats-isp" name="isp" class="js-select2 w-full" data-width="100%" data-placeholder="{{ __('ping.stats_all_isps') }}" data-allow-clear="true">
                    <option value="">{{ __('ping.stats_all_isps') }}</option>
                    @foreach($isps as $isp)
                        <option value="{{ $isp }}" @selected($filters['isp'] === $isp)>{{ $isp }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="stats-provider" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.stats_provider') }}</label>
                <select id="stats-provider" name="provider" class="js-select2 w-full" data-width="100%" data-placeholder="{{ __('ping.stats_all_providers') }}">
                    <option value="">{{ __('ping.stats_all_providers') }}</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider }}" @selected($filters['provider'] === $provider)>{{ $provider }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="stats-country" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.stats_country') }}</label>
                <select id="stats-country" name="country" class="js-select2 w-full" data-width="100%" data-minimum-results-for-search="Infinity">
                    <option value="">{{ __('ping.stats_all_countries') }}</option>
                    @foreach($countries as $code)
                        <option value="{{ $code }}" @selected($filters['country'] === $code)>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="stats-min" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.stats_min_samples') }}</label>
                <input id="stats-min" type="number" name="min_samples" min="1" max="50" value="{{ $filters['min_samples'] }}"
                    class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full border border-neutral-950 bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
                    {{ __('ping.filter') }}
                </button>
            </div>
        </form>

        @if($rows->isNotEmpty())
            <div class="mb-6 space-y-2">
                @foreach($rows->take(5) as $highlight)
                    <p class="border-l-2 border-neutral-950 pl-3 text-sm text-neutral-700">{{ $highlight->summary }}</p>
                @endforeach
            </div>
        @endif

        <div class="overflow-x-auto border border-neutral-950 bg-white">
            <table class="js-datatable w-full text-left text-sm" data-dt-per-page="25" data-dt-nosort="8">
                <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                    <tr>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_isp') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_country') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_provider') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.target') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.host') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_resolved_ip') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_avg') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_range') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_samples') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $ms = (float) $row->avg_latency_ms;
                            $tone = $ms < 80 ? 'text-green-600' : ($ms < 180 ? 'text-yellow-600' : 'text-red-600');
                        @endphp
                        <tr class="border-b border-neutral-100 hover:bg-neutral-50" title="{{ $row->summary }}">
                            <td class="px-3 py-3">
                                <div class="font-medium">{{ $row->isp }}</div>
                                @if($row->asn)
                                    <div class="mono text-[10px] text-neutral-400">AS{{ ltrim((string) $row->asn, 'AS') }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 mono text-xs">{{ $row->country_code ?: '—' }}</td>
                            <td class="px-3 py-3">{{ $row->provider }}</td>
                            <td class="px-3 py-3">{{ $row->target_name }}</td>
                            <td class="px-3 py-3 mono text-xs text-neutral-600">{{ $row->host }}</td>
                            <td class="px-3 py-3 mono text-xs">{{ $row->resolved_ip }}</td>
                            <td class="px-3 py-3 text-right mono font-medium {{ $tone }}">{{ $row->avg_latency_ms }} ms</td>
                            <td class="px-3 py-3 text-right mono text-xs text-neutral-500">{{ $row->min_latency_ms }}–{{ $row->max_latency_ms }}</td>
                            <td class="px-3 py-3 text-right mono text-xs text-neutral-500">{{ $row->samples }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center text-sm text-neutral-500">
                                {{ __('ping.stats_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-ping-layout>
