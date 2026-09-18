<x-ping-layout>
    <x-page-shell>
        <div class="mb-4">
            <a href="{{ route('quality.index') }}" class="inline-flex items-center text-xs uppercase tracking-widest text-neutral-500 hover:text-neutral-950 transition-colors">
                ← Ağ Kalitesine Dön
            </a>
        </div>

        <x-page-header
            :eyebrow="'Test Detayı #' . $test->id"
            :title="'Ağ Kalitesi Raporu: ' . $test->grade . ' (' . $test->score . '/100)'"
            :subtitle="$test->summary ?? 'Web servisleri gerçek erişim performansı ve gecikme kırılımları.'"
        />

        <section class="border border-neutral-950 bg-white">
            <div class="border-b border-neutral-950 bg-neutral-950 px-6 py-4 text-white flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="mono text-3xl font-black">{{ $test->grade }}</span>
                    <div class="border-l border-neutral-700 pl-4">
                        <div class="text-xs uppercase tracking-widest text-neutral-400">{{ __('ping.quality_score') }}</div>
                        <div class="mono text-xl font-bold">{{ $test->score }} <span class="text-sm font-normal text-neutral-400">/ 100</span></div>
                    </div>
                </div>
                <div class="text-right text-xs">
                    <div class="mono text-neutral-300">{{ $test->tested_at?->translatedFormat('d F Y · H:i:s') }}</div>
                    <div class="mt-0.5 text-neutral-400">
                        {{ $test->connection_type ? strtoupper($test->connection_type) : 'LINK' }}
                        @if($test->client_isp)
                            · {{ $test->client_isp }}
                        @endif
                        @if($test->client_country_code)
                            ({{ $test->client_country_code }})
                        @endif
                    </div>
                </div>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-2 divide-x divide-y divide-neutral-200 border-b border-neutral-200 sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0">
                <div class="p-4">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_dns_latency') }}</div>
                    <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                        {{ $test->avg_dns_ms !== null ? $test->avg_dns_ms . ' ms' : '—' }}
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_tcp_latency') }}</div>
                    <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                        {{ $test->avg_tcp_ms !== null ? $test->avg_tcp_ms . ' ms' : '—' }}
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_tls_latency') }}</div>
                    <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                        {{ $test->avg_tls_ms !== null ? $test->avg_tls_ms . ' ms' : '—' }}
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-500">{{ __('ping.quality_ttfb_latency') }}</div>
                    <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                        {{ $test->avg_ttfb_ms !== null ? $test->avg_ttfb_ms . ' ms' : '—' }}
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-500">Ort. Gecikme</div>
                    <div class="mt-1 mono text-lg font-semibold text-neutral-950">
                        {{ $test->avg_latency_ms !== null ? $test->avg_latency_ms . ' ms' : '—' }}
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-500">Paket Kaybı</div>
                    <div class="mt-1 mono text-lg font-semibold {{ (float)$test->packet_loss_percent > 0 ? 'text-rose-600' : 'text-neutral-950' }}">
                        %{{ number_format((float)$test->packet_loss_percent, 1) }}
                    </div>
                </div>
            </div>

            @if(!empty($test->insights))
                <div class="border-b border-neutral-200 bg-neutral-50/50 p-6">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-700">{{ __('ping.quality_insights') }}</h3>
                    <ul class="space-y-1.5 text-sm text-neutral-700">
                        @foreach($test->insights as $insight)
                            <li class="flex items-start gap-2">
                                <span class="text-neutral-400 mt-1">•</span>
                                <span>{{ $insight }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Tested Domains Table -->
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
                        @foreach($test->results as $r)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-neutral-950">{{ $r['name'] ?? ($r['domain'] ?? $r['url'] ?? '') }}</div>
                                    <div class="mono text-xs text-neutral-500">{{ $r['domain'] ?? ($r['url'] ?? '') }}</div>
                                </td>
                                <td class="px-4 py-2.5">
                                    @if(!empty($r['ok']))
                                        <span class="inline-flex items-center gap-1.5 border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 mono">
                                            {{ $r['status_code'] ?? '200 OK' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 border border-rose-300 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-800 mono">
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
    </x-page-shell>
</x-ping-layout>
