@props([
    'path' => null,
])

@php
    /** @var \App\Services\TracerouteBottleneckAnalyzer $analyzer */
    $analyzer = app(\App\Services\TracerouteBottleneckAnalyzer::class);
    $annotated = $analyzer->annotatePath(is_array($path) ? $path : null);
    $hops = is_array($annotated) ? ($annotated['hops'] ?? []) : [];
@endphp

<section {{ $attributes->class('border border-neutral-950 bg-white p-5') }}>
    <h2 class="mb-3 text-sm font-semibold">{{ __('ping.traceroute_section') }}</h2>

    @if(is_array($annotated))
        <dl class="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div>
                <dt class="text-xs text-neutral-400">{{ __('ping.field_trace_tool') }}</dt>
                <dd class="mono text-xs">{{ $annotated['tool'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-neutral-400">{{ __('ping.field_trace_hops') }}</dt>
                <dd class="mono">{{ $annotated['hop_count'] ?? count($hops) }}</dd>
            </div>
            <div>
                <dt class="text-xs text-neutral-400">{{ __('ping.field_trace_reached') }}</dt>
                <dd>{{ ! empty($annotated['reached']) ? __('ping.yes') : __('ping.no') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-neutral-400">{{ __('ping.field_trace_duration') }}</dt>
                <dd class="mono">{{ isset($annotated['duration_ms']) ? $annotated['duration_ms'].' ms' : '—' }}</dd>
            </div>
            <div class="col-span-2 sm:col-span-4">
                <dt class="text-xs text-neutral-400">{{ __('ping.field_trace_command') }}</dt>
                <dd class="mono break-all text-xs text-neutral-600">{{ $annotated['command'] ?? '—' }}</dd>
            </div>
        </dl>

        @if(! empty($annotated['error']))
            <p class="mb-3 text-sm text-red-600">{{ $annotated['error'] }}</p>
        @endif

        @if(count($hops) > 0)
            <p class="mb-2 text-[11px] text-neutral-500">{{ __('ping.trace_bottleneck_hint') }}</p>
            <div class="overflow-x-auto border border-neutral-100">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-neutral-200 text-[10px] uppercase tracking-widest text-neutral-400">
                        <tr>
                            <th class="px-2 py-2 font-medium">{{ __('ping.field_trace_hop') }}</th>
                            <th class="px-2 py-2 font-medium">{{ __('ping.field_trace_ip') }}</th>
                            <th class="px-2 py-2 font-medium">{{ __('ping.field_trace_rtt') }}</th>
                            <th class="px-2 py-2 font-medium">{{ __('ping.field_trace_delta') }}</th>
                            <th class="px-2 py-2 font-medium">{{ __('ping.field_trace_kind') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hops as $hop)
                            <tr @class([
                                'border-b border-neutral-50',
                                'bg-amber-50' => ! empty($hop['bottleneck']),
                            ])>
                                <td class="px-2 py-1.5 mono">
                                    {{ $hop['ttl'] ?? '—' }}
                                    @if(! empty($hop['bottleneck']))
                                        <span class="ml-1 inline-block border border-amber-700 px-1 text-[9px] font-medium uppercase tracking-wider text-amber-800">{{ __('ping.trace_bottleneck') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5 mono">{{ ! empty($hop['timeout']) ? '*' : ($hop['ip'] ?? '—') }}</td>
                                <td @class([
                                    'px-2 py-1.5 mono',
                                    'font-medium text-amber-800' => ! empty($hop['bottleneck']),
                                    'text-neutral-600' => empty($hop['bottleneck']),
                                ])>
                                    @if(! empty($hop['rtts_ms']))
                                        {{ implode(' / ', array_map(fn ($v) => round((float) $v, 1).'ms', $hop['rtts_ms'])) }}
                                    @elseif(isset($hop['avg_ms']))
                                        {{ round((float) $hop['avg_ms'], 1) }} ms
                                    @else
                                        —
                                    @endif
                                </td>
                                <td @class([
                                    'px-2 py-1.5 mono',
                                    'font-medium text-amber-800' => ! empty($hop['bottleneck']),
                                    'text-neutral-500' => empty($hop['bottleneck']),
                                ])>
                                    @if(isset($hop['delta_ms']))
                                        {{ $hop['delta_ms'] > 0 ? '+' : '' }}{{ $hop['delta_ms'] }} ms
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

        @if(! empty($annotated['raw']))
            <h3 class="mb-2 mt-4 text-xs uppercase tracking-wider text-neutral-400">{{ __('ping.trace_raw_section') }}</h3>
            <pre class="max-h-80 overflow-auto whitespace-pre-wrap border border-neutral-100 bg-neutral-50 p-4 mono text-[11px] text-neutral-600">{{ $annotated['raw'] }}</pre>
        @endif
    @else
        <p class="text-sm text-neutral-400">{{ __('ping.no_traceroute') }}</p>
    @endif
</section>
