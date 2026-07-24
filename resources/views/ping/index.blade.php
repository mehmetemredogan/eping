<x-ping-layout>
    <div
        class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10"
        x-data="pingApp(@js($targetPayload), @js($i18n), @js($clientGeo))"
    >
        <div class="mb-8 sm:mb-10">
            <div class="bg-grid border border-neutral-950 px-4 py-6 sm:px-6 sm:py-8">
                <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ __('ping.latency_lab') }}</p>
                <h1 class="cursor-blink text-2xl font-semibold tracking-tight text-neutral-950 sm:text-4xl">{{ __('ping.title') }}</h1>
                <p class="mt-3 max-w-2xl text-xs leading-relaxed text-neutral-500 sm:text-sm">
                    {{ __('ping.subtitle') }}
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-[11px] text-neutral-500">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 bg-green-600"></span>
                        {{ __('ping.legend_good') }}
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 bg-yellow-500"></span>
                        {{ __('ping.legend_mid') }}
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 bg-red-600"></span>
                        {{ __('ping.legend_bad') }}
                    </span>
                </div>
            </div>

            <div class="border border-t-0 border-neutral-950 bg-white">
                <div class="flex items-center justify-between border-b border-neutral-200 bg-neutral-50 px-4 py-2">
                    <p class="mono text-[10px] font-medium uppercase tracking-[0.25em] text-neutral-500">{{ __('ping.your_connection') }}</p>
                    <span class="mono text-[10px] text-neutral-400">ifconfig</span>
                </div>
                <div class="grid gap-4 px-4 py-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.your_ip') }}</div>
                        <div class="mono mt-1 truncate text-sm font-medium text-neutral-950">{{ $clientGeo['ipAddress'] ?? $clientIp ?? '—' }}</div>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.your_location') }}</div>
                        <div class="mt-1 text-sm font-medium text-neutral-950">
                            @if(!empty($clientGeo['local']))
                                {{ __('ping.local_network') }}
                            @else
                                {{ $clientLocation ?: '—' }}
                            @endif
                        </div>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.your_isp') }}</div>
                        <div class="mt-1 break-words text-sm font-medium text-neutral-950">{{ $clientGeo['asnOrganization'] ?? '—' }}</div>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.your_country') }}</div>
                        <div class="mt-1 flex items-center gap-2 text-sm font-medium text-neutral-950">
                            @if(!empty($clientGeo['countryCode']))
                                <span class="mono border border-neutral-950 bg-white px-1.5 py-0.5 text-[10px] uppercase">{{ $clientGeo['countryCode'] }}</span>
                            @endif
                            <span>{{ $clientGeo['countryName'] ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-neutral-200 px-4 py-4 sm:grid-cols-2">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.your_dns') }}</div>
                        <template x-if="clientDnsLoading">
                            <div class="mono mt-1 text-sm text-neutral-400" x-text="i18n.dns_detecting"></div>
                        </template>
                        <template x-if="!clientDnsLoading && clientDns?.dns?.ip">
                            <div class="mt-1">
                                <div class="mono text-sm font-medium text-neutral-950" x-text="clientDns.dns.ip"></div>
                                <div class="mt-0.5 truncate text-xs text-neutral-500" x-text="clientDns.dns.geo || ''"></div>
                            </div>
                        </template>
                        <template x-if="!clientDnsLoading && !clientDns?.dns?.ip">
                            <div class="mono mt-1 text-sm text-neutral-400" x-text="clientDnsError ? i18n.dns_unavailable : '—'"></div>
                        </template>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('ping.your_edns') }}</div>
                        <template x-if="clientDnsLoading">
                            <div class="mono mt-1 text-sm text-neutral-400" x-text="i18n.dns_detecting"></div>
                        </template>
                        <template x-if="!clientDnsLoading && clientDns?.edns?.ip">
                            <div class="mt-1">
                                <div class="mono text-sm font-medium text-neutral-950" x-text="clientDns.edns.ip"></div>
                                <div class="mt-0.5 truncate text-xs text-neutral-500" x-text="clientDns.edns.geo || ''"></div>
                                <div class="mt-1 text-[11px] text-neutral-400" x-text="i18n.edns_hint"></div>
                            </div>
                        </template>
                        <template x-if="!clientDnsLoading && !clientDns?.edns?.ip">
                            <div class="mt-1">
                                <div class="mono text-sm text-neutral-400" x-text="clientDnsError ? i18n.dns_unavailable : i18n.edns_missing"></div>
                                <div class="mt-1 text-[11px] text-neutral-400" x-show="!clientDnsError" x-text="i18n.edns_hint"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <form method="GET" class="flex w-full flex-col gap-3 sm:flex-row sm:items-end lg:max-w-3xl">
                <div class="min-w-0 flex-1">
                    <label class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.host') }}</label>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('ping.search_placeholder') }}"
                        class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-950 placeholder:text-neutral-400 focus:border-neutral-950 focus:outline-none focus:ring-0"
                    >
                </div>
                <div class="w-full sm:w-56">
                    <label class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400" for="filter-category">{{ __('ping.all_categories') }}</label>
                    <select id="filter-category" name="category" class="js-select2 w-full" data-placeholder="{{ __('ping.all_categories') }}">
                        <option value="">{{ __('ping.all_categories') }}</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected($selectedCategory === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="border border-neutral-950 bg-white px-4 py-2 text-sm font-medium text-neutral-950 transition-colors hover:bg-neutral-950 hover:text-white sm:mb-0">
                    {{ __('ping.filter') }}
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-3">
                <span class="mono text-xs text-neutral-400">[{{ __('ping.targets_count', ['count' => $totalCount]) }}]</span>
                <button
                    type="button"
                    @click="runAll()"
                    :disabled="loading"
                    class="inline-flex w-full items-center justify-center border border-neutral-950 bg-neutral-950 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-neutral-950 disabled:hover:text-white sm:w-auto"
                >
                    <span x-show="!loading">▶ {{ __('ping.test_all') }}</span>
                    <span x-show="loading" x-cloak class="cursor-blink">{{ __('ping.testing') }}</span>
                </button>
            </div>
        </div>

        @forelse($groupedByProvider as $provider => $targets)
            @php
                $providerKey = \Illuminate\Support\Str::slug($provider) ?: 'provider';
                $needsCollapse = $targets->count() > 5;
                $visible = $needsCollapse ? $targets->take(5) : $targets;
                $hidden = $needsCollapse ? $targets->slice(5) : collect();
            @endphp
            <section class="mb-6 border border-neutral-950" data-provider="{{ $providerKey }}">
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-neutral-950 bg-neutral-50 px-3 py-3 sm:px-4">
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-sm font-semibold tracking-tight text-neutral-950">{{ $provider }}</h2>
                        @if($providerDescriptions->has($provider))
                            <div class="md mt-1 max-w-3xl text-[11px] leading-relaxed text-neutral-500">{!! $providerDescriptions[$provider] !!}</div>
                        @endif
                    </div>
                    <span class="mono shrink-0 border border-neutral-300 bg-white px-2 py-0.5 text-xs text-neutral-500">{{ $targets->count() }}</span>
                </div>

                {{-- Mobile cards --}}
                <div class="divide-y divide-neutral-100 md:hidden">
                    @foreach($visible as $target)
                        @include('ping._target-card', ['target' => $target])
                    @endforeach
                    @foreach($hidden as $target)
                        <div x-show="isProviderExpanded(@js($providerKey))" x-cloak>
                            @include('ping._target-card', ['target' => $target])
                        </div>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden md:block">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-neutral-200 text-[10px] uppercase tracking-widest text-neutral-400">
                                    <th class="py-2 pl-4 pr-4 font-medium">{{ __('ping.target') }}</th>
                                    <th class="py-2 pr-4 font-medium">{{ __('ping.host') }}</th>
                                    <th class="py-2 pr-4 font-medium">{{ __('ping.location') }}</th>
                                    <th class="py-2 pr-4 font-medium text-right">{{ __('ping.latency') }}</th>
                                    <th class="py-2 pl-4 pr-4 font-medium text-right">{{ __('ping.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($visible as $target)
                                    @include('ping._target-row', ['target' => $target])
                                @endforeach
                            </tbody>
                            @if($needsCollapse)
                                <tbody x-show="isProviderExpanded(@js($providerKey))" x-cloak>
                                    @foreach($hidden as $target)
                                        @include('ping._target-row', ['target' => $target])
                                    @endforeach
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>

                @if($needsCollapse)
                    <div class="border-t border-neutral-200 bg-white px-4 py-2">
                        <button
                            type="button"
                            class="mono text-xs font-medium text-neutral-600 hover:text-neutral-950"
                            @click="toggleProvider(@js($providerKey))"
                        >
                            <span x-show="!isProviderExpanded(@js($providerKey))">
                                + {{ __('ping.show_more', ['count' => $hidden->count()]) }}
                            </span>
                            <span x-show="isProviderExpanded(@js($providerKey))" x-cloak>
                                − {{ __('ping.show_less') }}
                            </span>
                        </button>
                    </div>
                @endif
            </section>
        @empty
            <div class="border border-neutral-950 py-16 text-center text-sm text-neutral-500">
                {{ __('ping.empty') }}
            </div>
        @endforelse
    </div>
</x-ping-layout>
