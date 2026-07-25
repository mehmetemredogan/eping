<x-ping-layout>
    <x-page-shell>
        <x-page-header
            :eyebrow="__('ping.nav_stats')"
            :title="__('ping.stats_title')"
            :subtitle="__('ping.stats_subtitle')"
        >
            <x-slot:meta>{{ __('ping.stats_privacy_note') }}</x-slot:meta>
        </x-page-header>

        <form method="GET" class="mb-8 grid gap-3 border border-neutral-200 bg-neutral-50 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <x-form-label for="stats-isp" :value="__('ping.stats_isp')" />
                <select id="stats-isp" name="isp" class="js-select2 w-full" data-width="100%" data-placeholder="{{ __('ping.stats_all_isps') }}" data-allow-clear="true">
                    <option value="">{{ __('ping.stats_all_isps') }}</option>
                    @foreach($isps as $isp)
                        <option value="{{ $isp }}" @selected($filters['isp'] === $isp)>{{ $isp }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-form-label for="stats-provider" :value="__('ping.stats_provider')" />
                <select id="stats-provider" name="provider" class="js-select2 w-full" data-width="100%" data-placeholder="{{ __('ping.stats_all_providers') }}">
                    <option value="">{{ __('ping.stats_all_providers') }}</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider }}" @selected($filters['provider'] === $provider)>{{ $provider }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-form-label for="stats-country" :value="__('ping.stats_country')" />
                <select id="stats-country" name="country" class="js-select2 w-full" data-width="100%" data-minimum-results-for-search="Infinity">
                    <option value="">{{ __('ping.stats_all_countries') }}</option>
                    @foreach($countries as $code)
                        <option value="{{ $code }}" @selected($filters['country'] === $code)>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-form-label for="stats-min" :value="__('ping.stats_min_samples')" />
                <input id="stats-min" type="number" name="min_samples" min="1" max="50" value="{{ $filters['min_samples'] }}"
                    class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
            </div>
            <div class="flex items-end">
                <x-ui-button type="submit" variant="primary" block>{{ __('ping.filter') }}</x-ui-button>
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
            <table class="js-datatable w-full text-left text-sm" data-dt-per-page="25" data-dt-nosort="11">
                <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                    <tr>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_isp') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_country') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_provider') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.target') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.host') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('ping.stats_resolved_ip') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_avg_overall') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_avg_wifi') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_avg_ethernet') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_range') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_samples') }}</th>
                        <th class="px-3 py-3 font-medium text-right">{{ __('ping.stats_samples_link') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
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
                            <td class="px-3 py-3 text-right"><x-latency :ms="$row->avg_latency_ms" class="font-medium" /></td>
                            <td class="px-3 py-3 text-right"><x-latency :ms="$row->avg_wifi_ms" class="text-xs" /></td>
                            <td class="px-3 py-3 text-right"><x-latency :ms="$row->avg_ethernet_ms" class="text-xs" /></td>
                            <td class="px-3 py-3 text-right mono text-xs text-neutral-500">{{ $row->min_latency_ms }}–{{ $row->max_latency_ms }}</td>
                            <td class="px-3 py-3 text-right mono text-xs text-neutral-500">{{ $row->samples }}</td>
                            <td class="px-3 py-3 text-right mono text-[10px] text-neutral-500">
                                {{ __('ping.stats_samples_wifi_short') }} {{ $row->samples_wifi }}
                                · {{ __('ping.stats_samples_ethernet_short') }} {{ $row->samples_ethernet }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-16 text-center text-sm text-neutral-500">
                                {{ __('ping.stats_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-page-shell>
</x-ping-layout>
