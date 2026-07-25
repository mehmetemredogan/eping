<x-ping-layout :title="__('ping.history_detail_title', ['id' => $result->id])">
    <x-page-shell>
        <x-admin-back-link :href="route('history.index')" :label="__('ping.back_to_history')" />

        <x-page-header
            :eyebrow="__('ping.nav_history')"
            :title="__('ping.history_detail_title', ['id' => $result->id])"
            :subtitle="$result->target?->name.' · '.($result->target?->host ?? '')"
        />

        <div class="grid gap-4 lg:grid-cols-2">
            <x-panel :title="__('ping.history_detail_ping')">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.target') }}</dt>
                        <dd class="font-medium">{{ $result->target?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.host') }}</dt>
                        <dd class="mono text-xs">{{ $result->target?->host ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_status') }}</dt>
                        <dd><x-status-badge :status="$result->status" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_time') }}</dt>
                        <dd>{{ $result->tested_at?->format('d.m.Y H:i:s') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.latency') }}</dt>
                        <dd><x-latency :ms="$result->avg_latency_ms" class="font-medium" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_min_max') }}</dt>
                        <dd class="mono text-xs">{{ $result->min_latency_ms ?? '—' }} / {{ $result->max_latency_ms ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_jitter') }}</dt>
                        <dd class="mono text-xs">{{ $result->jitter_ms !== null ? $result->jitter_ms.' ms' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_packet_loss') }}</dt>
                        <dd>{{ $result->packet_loss_percent ?? 0 }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_packets') }}</dt>
                        <dd>{{ $result->packets_received }}/{{ $result->packets_sent }}</dd>
                    </div>
                    @php
                        $link = $result->connection_type
                            ?? (is_array($result->network_analysis) ? ($result->network_analysis['connection_type'] ?? null) : null);
                        $linkLabel = match ($link) {
                            'wifi' => __('ping.connection_wifi'),
                            'ethernet' => __('ping.connection_ethernet'),
                            'unknown' => __('ping.connection_unknown'),
                            default => '—',
                        };
                    @endphp
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.history_connection') }}</dt>
                        <dd>{{ $linkLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">IP</dt>
                        <dd class="mono text-xs">{{ $result->resolved_ip ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-400">{{ __('ping.your_dns') }}</dt>
                        <dd class="mono text-xs">{{ $result->client_dns['dns']['ip'] ?? '—' }}</dd>
                    </div>
                </dl>
            </x-panel>

            <x-panel :title="__('ping.history_detail_network')">
                @php
                    $analysis = is_array($result->network_analysis) ? $result->network_analysis : null;
                @endphp
                @if($analysis)
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-neutral-400">{{ __('ping.history_network_status') }}</dt>
                            <dd class="font-medium">{{ $analysis['status_label'] ?? ($analysis['status'] ?? '—') }}</dd>
                        </div>
                        @if(! empty($analysis['summary']))
                            <div>
                                <dt class="text-xs text-neutral-400">{{ __('ping.history_summary') }}</dt>
                                <dd class="text-neutral-700">{{ $analysis['summary'] }}</dd>
                            </div>
                        @endif
                        @if(! empty($analysis['path_summary']))
                            <div>
                                <dt class="text-xs text-neutral-400">{{ __('ping.history_path_summary') }}</dt>
                                <dd class="text-neutral-700">{{ $analysis['path_summary'] }}</dd>
                            </div>
                        @endif
                        @if(! empty($analysis['insights']) && is_array($analysis['insights']))
                            <div>
                                <dt class="mb-1 text-xs text-neutral-400">{{ __('ping.history_insights') }}</dt>
                                <ul class="list-disc space-y-1 pl-5 text-neutral-700">
                                    @foreach($analysis['insights'] as $insight)
                                        <li>{{ $insight }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="text-sm text-neutral-400">{{ __('ping.history_no_analysis') }}</p>
                @endif
            </x-panel>

            <div class="lg:col-span-2">
                <x-traceroute-path :path="is_array($result->network_analysis) ? ($result->network_analysis['path'] ?? null) : null" />
            </div>

            <x-panel :title="__('ping.history_raw_output')" class="lg:col-span-2">
                <pre class="overflow-x-auto whitespace-pre-wrap border border-neutral-100 bg-neutral-50 p-4 mono text-[11px] text-neutral-600">{{ $result->ping_raw_output ?? __('ping.history_no_raw') }}</pre>
            </x-panel>
        </div>
    </x-page-shell>
</x-ping-layout>
