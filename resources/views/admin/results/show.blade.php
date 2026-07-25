<x-admin-layout :header="__('admin.result_detail_title', ['id' => $result->id])">
    <div class="mb-4">
        <a href="{{ route('admin.results.index') }}" class="text-sm text-neutral-500 hover:text-neutral-950">{{ __('admin.back_to_logs') }}</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-4 border border-neutral-950 bg-white p-5">
            <h2 class="text-sm font-semibold">{{ __('admin.ping_section') }}</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-neutral-400">{{ __('admin.col_target') }}</dt><dd class="font-medium">{{ $result->target?->name }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.col_host') }}</dt><dd class="mono text-xs">{{ $result->target?->host }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_status') }}</dt><dd>{{ $result->status }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_time') }}</dt><dd>{{ $result->tested_at?->format('d.m.Y H:i:s') }}</dd></div>
                @php
                    $ms = $result->avg_latency_ms !== null ? (float) $result->avg_latency_ms : null;
                    $latencyTone = $ms === null ? 'text-neutral-400' : ($ms < 80 ? 'text-green-600' : ($ms < 180 ? 'text-yellow-600' : 'text-red-600'));
                @endphp
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_average') }}</dt><dd class="mono {{ $latencyTone }}">{{ $ms !== null ? $ms.' ms' : '—' }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_min_max') }}</dt><dd class="mono">{{ $result->min_latency_ms ?? '—' }} / {{ $result->max_latency_ms ?? '—' }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_jitter') }}</dt><dd class="mono">{{ $result->jitter_ms ?? '—' }} ms</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_packet_loss') }}</dt><dd>{{ $result->packet_loss_percent ?? 0 }}%</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_packets') }}</dt><dd>{{ $result->packets_received }}/{{ $result->packets_sent }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_client_ip') }}</dt><dd class="mono text-xs">{{ $result->client_ip ?? '—' }}</dd></div>
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
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_connection_type') }}</dt><dd>{{ $linkLabel }}</dd></div>
            </dl>

            @if($result->client_geo)
                <div class="border-t border-neutral-100 pt-4">
                    <h3 class="mb-2 text-xs uppercase tracking-wider text-neutral-400">{{ __('admin.client_geo_section') }}</h3>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-neutral-400">{{ __('admin.field_city') }}</dt><dd>{{ $result->client_geo['cityName'] ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-neutral-400">{{ __('admin.field_region') }}</dt><dd>{{ $result->client_geo['regionName'] ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-neutral-400">{{ __('admin.field_country') }}</dt><dd>{{ ($result->client_geo['countryName'] ?? '—').' '.($result->client_geo['countryCode'] ?? '') }}</dd></div>
                        <div><dt class="text-xs text-neutral-400">{{ __('admin.field_continent') }}</dt><dd>{{ $result->client_geo['continent'] ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-neutral-400">{{ __('admin.field_asn') }}</dt><dd class="text-xs">{{ ($result->client_geo['asn'] ?? '').' '.($result->client_geo['asnOrganization'] ?? '—') }}</dd></div>
                        <div><dt class="text-xs text-neutral-400">{{ __('admin.field_proxy') }}</dt><dd>{{ isset($result->client_geo['isProxy']) ? ($result->client_geo['isProxy'] ? __('ping.yes') : __('ping.no')) : '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs text-neutral-400">{{ __('admin.field_coordinates') }}</dt><dd class="mono text-xs">{{ ($result->client_geo['latitude'] ?? '—').', '.($result->client_geo['longitude'] ?? '—') }}</dd></div>
                    </dl>
                </div>
            @endif

            @if($result->client_dns)
                <div class="border-t border-neutral-100 pt-4">
                    <h3 class="mb-2 text-xs uppercase tracking-wider text-neutral-400">{{ __('admin.client_dns_section') }}</h3>
                    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-neutral-400">{{ __('admin.field_dns_server') }}</dt>
                            <dd class="mono text-xs">{{ $result->client_dns['dns']['ip'] ?? '—' }}</dd>
                            <dd class="mt-0.5 text-xs text-neutral-500">{{ $result->client_dns['dns']['geo'] ?? '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-400">{{ __('admin.field_edns_subnet') }}</dt>
                            <dd class="mono text-xs">{{ $result->client_dns['edns']['ip'] ?? '—' }}</dd>
                            <dd class="mt-0.5 text-xs text-neutral-500">{{ $result->client_dns['edns']['geo'] ?? '' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-neutral-400">{{ __('admin.field_source') }}</dt>
                            <dd class="text-xs text-neutral-500">{{ $result->client_dns['source'] ?? '—' }} · {{ $result->client_dns['detected_at'] ?? '' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        <div class="space-y-4 border border-neutral-950 bg-white p-5">
            <h2 class="text-sm font-semibold">{{ __('admin.target_dns_section') }}</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-xs text-neutral-400">IP</dt><dd class="mono">{{ $result->resolved_ip ?? '—' }}</dd></div>
                <div><dt class="text-xs text-neutral-400">{{ __('admin.field_rdns') }}</dt><dd class="mono break-all text-xs">{{ $result->rdns ?? '—' }}</dd></div>
            </dl>

            <div>
                <h3 class="mb-2 text-xs uppercase tracking-wider text-neutral-400">{{ __('admin.target_dns_records_section') }}</h3>
                @if($result->dns_records)
                    <div class="max-h-40 space-y-1 overflow-y-auto">
                        @foreach($result->dns_records as $record)
                            <div class="mono border border-neutral-100 bg-neutral-50 px-2 py-1.5 text-[11px]">
                                <span class="font-medium">{{ $record['type'] ?? '?' }}</span>
                                {{ $record['value'] ?? '' }}
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-400">{{ __('admin.no_records') }}</p>
                @endif
            </div>

            <div>
                <h3 class="mb-2 text-xs uppercase tracking-wider text-neutral-400">{{ __('admin.target_edns_section') }}</h3>
                @if($result->edns_data)
                    <pre class="overflow-x-auto border border-neutral-100 bg-neutral-50 p-3 mono text-[11px] text-neutral-600">{{ json_encode($result->edns_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="text-sm text-neutral-400">{{ __('admin.no_edns') }}</p>
                @endif
            </div>
        </div>

        @php
            $path = is_array($result->network_analysis) ? ($result->network_analysis['path'] ?? null) : null;
            $hops = is_array($path) ? ($path['hops'] ?? []) : [];
        @endphp
        <div class="border border-neutral-950 bg-white p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold">{{ __('admin.traceroute_section') }}</h2>
            @if(is_array($path))
                <dl class="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div><dt class="text-xs text-neutral-400">{{ __('admin.field_trace_tool') }}</dt><dd class="mono text-xs">{{ $path['tool'] ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-neutral-400">{{ __('admin.field_trace_hops') }}</dt><dd class="mono">{{ $path['hop_count'] ?? count($hops) }}</dd></div>
                    <div><dt class="text-xs text-neutral-400">{{ __('admin.field_trace_reached') }}</dt><dd>{{ ! empty($path['reached']) ? __('ping.yes') : __('ping.no') }}</dd></div>
                    <div><dt class="text-xs text-neutral-400">{{ __('admin.field_trace_duration') }}</dt><dd class="mono">{{ isset($path['duration_ms']) ? $path['duration_ms'].' ms' : '—' }}</dd></div>
                    <div class="col-span-2 sm:col-span-4"><dt class="text-xs text-neutral-400">{{ __('admin.field_trace_command') }}</dt><dd class="mono break-all text-xs text-neutral-600">{{ $path['command'] ?? '—' }}</dd></div>
                </dl>
                @if(! empty($path['error']))
                    <p class="mb-3 text-sm text-red-600">{{ $path['error'] }}</p>
                @endif
                @if(count($hops) > 0)
                    <div class="overflow-x-auto border border-neutral-100">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-neutral-200 text-[10px] uppercase tracking-widest text-neutral-400">
                                <tr>
                                    <th class="px-2 py-2 font-medium">{{ __('admin.field_trace_hop') }}</th>
                                    <th class="px-2 py-2 font-medium">{{ __('admin.field_trace_ip') }}</th>
                                    <th class="px-2 py-2 font-medium">{{ __('admin.field_trace_rtt') }}</th>
                                    <th class="px-2 py-2 font-medium">{{ __('admin.field_trace_kind') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hops as $hop)
                                    <tr class="border-b border-neutral-50">
                                        <td class="px-2 py-1.5 mono">{{ $hop['ttl'] ?? '—' }}</td>
                                        <td class="px-2 py-1.5 mono">{{ ! empty($hop['timeout']) ? '*' : ($hop['ip'] ?? '—') }}</td>
                                        <td class="px-2 py-1.5 mono text-neutral-600">
                                            @if(! empty($hop['rtts_ms']))
                                                {{ implode(' / ', array_map(fn ($v) => round((float) $v, 1).'ms', $hop['rtts_ms'])) }}
                                            @elseif(isset($hop['avg_ms']))
                                                {{ round((float) $hop['avg_ms'], 1) }} ms
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-2 py-1.5 text-neutral-500">{{ $hop['kind'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(! empty($path['raw']))
                    <h3 class="mb-2 mt-4 text-xs uppercase tracking-wider text-neutral-400">{{ __('admin.trace_raw_section') }}</h3>
                    <pre class="max-h-80 overflow-auto whitespace-pre-wrap border border-neutral-100 bg-neutral-50 p-4 mono text-[11px] text-neutral-600">{{ $path['raw'] }}</pre>
                @endif
            @else
                <p class="text-sm text-neutral-400">{{ __('admin.no_traceroute') }}</p>
            @endif
        </div>

        <div class="border border-neutral-950 bg-white p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold">{{ __('admin.raw_output_section') }}</h2>
            <pre class="overflow-x-auto whitespace-pre-wrap border border-neutral-100 bg-neutral-50 p-4 mono text-[11px] text-neutral-600">{{ $result->ping_raw_output ?? __('admin.no_raw_output') }}</pre>
        </div>
    </div>
</x-admin-layout>
