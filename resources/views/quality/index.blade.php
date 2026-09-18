<x-ping-layout>
    <x-page-shell>
        <x-page-header
            :eyebrow="__('ping.nav_quality')"
            :title="__('ping.quality_title')"
            :subtitle="__('ping.quality_subtitle')"
        />

        @if($latestTest)
            <!-- Hero Score Card -->
            <section class="mb-8 border border-neutral-950 bg-white">
                <div class="border-b border-neutral-950 bg-neutral-950 px-6 py-4 text-white flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="mono text-3xl font-black tracking-tight">{{ $latestTest->grade }}</span>
                        <div class="border-l border-neutral-700 pl-4">
                            <div class="text-xs uppercase tracking-widest text-neutral-400">{{ __('ping.quality_score') }}</div>
                            <div class="mono text-xl font-bold">{{ $latestTest->score }} <span class="text-sm font-normal text-neutral-400">/ 100</span></div>
                        </div>
                    </div>
                    <div class="text-right text-xs">
                        <div class="mono text-neutral-300">{{ $latestTest->tested_at?->translatedFormat('d F Y · H:i:s') }}</div>
                        <div class="mt-0.5 text-neutral-400">
                            {{ $latestTest->connection_type ? strtoupper($latestTest->connection_type) : 'LINK' }}
                            @if($latestTest->client_isp)
                                · {{ $latestTest->client_isp }}
                            @endif
                            @if($latestTest->client_country_code)
                                ({{ $latestTest->client_country_code }})
                            @endif
                        </div>
                    </div>
                </div>

                @if($latestTest->summary)
                    <div class="border-b border-neutral-200 bg-neutral-50 px-6 py-3 text-sm text-neutral-800">
                        <span class="font-semibold text-neutral-950">Özet:</span> {{ $latestTest->summary }}
                    </div>
                @endif

                <!-- Timing Breakdown Metrics Grid -->
                <div class="grid grid-cols-2 divide-x divide-y divide-neutral-200 border-b border-neutral-200 sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0">
                    <div class="p-4">
                        <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_dns_latency') }}</div>
                        <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                            {{ $latestTest->avg_dns_ms !== null ? $latestTest->avg_dns_ms . ' ms' : '—' }}
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_tcp_latency') }}</div>
                        <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                            {{ $latestTest->avg_tcp_ms !== null ? $latestTest->avg_tcp_ms . ' ms' : '—' }}
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_tls_latency') }}</div>
                        <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                            {{ $latestTest->avg_tls_ms !== null ? $latestTest->avg_tls_ms . ' ms' : '—' }}
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_ttfb_latency') }}</div>
                        <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                            {{ $latestTest->avg_ttfb_ms !== null ? $latestTest->avg_ttfb_ms . ' ms' : '—' }}
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-[10px] uppercase tracking-wider text-neutral-500">Ort. Gecikme</div>
                        <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                            {{ $latestTest->avg_latency_ms !== null ? $latestTest->avg_latency_ms . ' ms' : '—' }}
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-[10px] uppercase tracking-wider text-neutral-500">Paket Kaybı</div>
                        <div class="mt-1 mono text-lg font-semibold {{ (float)$latestTest->packet_loss_percent > 0 ? 'text-rose-600' : 'text-neutral-950' }}">
                            %{{ number_format((float)$latestTest->packet_loss_percent, 1) }}
                        </div>
                    </div>
                </div>

                @if(!empty($latestTest->insights))
                    <!-- Insights -->
                    <div class="border-b border-neutral-200 bg-neutral-50/50 p-6">
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-700">{{ __('ping.quality_insights') }}</h3>
                        <ul class="space-y-1.5 text-sm text-neutral-700">
                            @foreach($latestTest->insights as $insight)
                                <li class="flex items-start gap-2">
                                    <span class="text-neutral-400 mt-1">•</span>
                                    <span>{{ $insight }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Per-Domain Real Web Access Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-neutral-200 bg-neutral-100 text-[10px] uppercase tracking-widest text-neutral-600">
                            <tr>
                                <th class="px-4 py-3 font-semibold">{{ __('ping.target') }}</th>
                                <th class="px-4 py-3 font-semibold">Durum</th>
                                <th class="px-4 py-3 font-semibold text-right">{{ __('ping.quality_dns_latency') }}</th>
                                <th class="px-4 py-3 font-semibold text-right">{{ __('ping.quality_tcp_latency') }}</th>
                                <th class="px-4 py-3 font-semibold text-right">{{ __('ping.quality_tls_latency') }}</th>
                                <th class="px-4 py-3 font-semibold text-right">{{ __('ping.quality_ttfb_latency') }}</th>
                                <th class="px-4 py-3 font-semibold text-right">{{ __('ping.quality_total_latency') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach($latestTest->results as $r)
                                <tr class="hover:bg-neutral-50 transition-colors">
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium text-neutral-950">{{ $r['name'] ?? $r['domain'] }}</div>
                                        <div class="mono text-xs text-neutral-500">{{ $r['domain'] }}</div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if(!empty($r['ok']))
                                            <span class="inline-flex items-center gap-1.5 rounded-none border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 mono">
                                                {{ $r['status_code'] ?? '200 OK' }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-none border border-rose-300 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-800 mono">
                                                {{ $r['error'] ?? 'HATA' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right mono text-xs">
                                        {{ isset($r['dns_ms']) ? number_format($r['dns_ms'], 1) . ' ms' : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right mono text-xs">
                                        {{ isset($r['tcp_ms']) ? number_format($r['tcp_ms'], 1) . ' ms' : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right mono text-xs">
                                        {{ isset($r['tls_ms']) ? number_format($r['tls_ms'], 1) . ' ms' : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right mono text-xs font-semibold text-neutral-950">
                                        {{ isset($r['ttfb_ms']) ? number_format($r['ttfb_ms'], 1) . ' ms' : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right mono text-xs text-neutral-600">
                                        {{ isset($r['total_ms']) ? number_format($r['total_ms'], 1) . ' ms' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <!-- Empty State -->
            <div class="border border-neutral-950 bg-white p-12 text-center">
                <div class="mono text-2xl font-bold text-neutral-950 mb-2">Ağ Kalitesi Testi</div>
                <p class="text-neutral-600 max-w-lg mx-auto text-sm mb-6">
                    {{ __('ping.quality_no_tests') }}
                </p>
                <div class="inline-block bg-neutral-950 text-white font-mono text-xs px-4 py-2.5">
                    eping quality
                </div>
            </div>
        @endif

        @if(!empty($userTests) && $userTests->count() > 1)
            <!-- User History -->
            <section class="mt-12 border border-neutral-950 bg-white">
                <div class="border-b border-neutral-950 bg-neutral-50 px-4 py-3 flex items-center justify-between">
                    <h2 class="mono text-sm font-semibold">Test Geçmişiniz</h2>
                    <span class="mono border border-neutral-300 bg-white px-2 py-0.5 text-xs text-neutral-500">{{ $userTests->count() }} test</span>
                </div>
                <div class="divide-y divide-neutral-100">
                    @foreach($userTests as $test)
                        <a href="{{ route('quality.show', $test) }}" class="flex items-center justify-between px-4 py-3 transition-colors hover:bg-neutral-50">
                            <div class="flex items-center gap-4">
                                <span class="mono font-bold text-base px-2 py-0.5 border border-neutral-950">{{ $test->grade }}</span>
                                <div>
                                    <div class="font-medium text-sm text-neutral-950">Skor: {{ $test->score }}/100 · {{ $test->status }}</div>
                                    <div class="text-xs text-neutral-500 mono">{{ $test->tested_at?->diffForHumans() }} ({{ $test->tested_at?->format('d.m.Y H:i') }})</div>
                                </div>
                            </div>
                            <div class="text-right text-xs mono text-neutral-600">
                                <div>TTFB: {{ $test->avg_ttfb_ms ? $test->avg_ttfb_ms . ' ms' : '—' }}</div>
                                <div class="text-[10px] text-neutral-400 uppercase tracking-widest mt-1">İncele →</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </x-page-shell>
</x-ping-layout>
