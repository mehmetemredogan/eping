@php
    /** @var \App\Models\PingTarget $target */
@endphp
<div class="grid gap-4 sm:grid-cols-3" x-show="results[{{ $target->id }}]">
    <div>
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('ping.network') }}</h3>
        <dl class="space-y-1.5 text-xs">
            <div class="flex gap-2">
                <dt class="w-16 shrink-0 text-neutral-400">IP</dt>
                <dd class="mono break-all text-neutral-800" x-text="results[{{ $target->id }}]?.resolved_ip || '—'"></dd>
            </div>
            <div class="flex gap-2">
                <dt class="w-16 shrink-0 text-neutral-400">rDNS</dt>
                <dd class="mono break-all text-neutral-800" x-text="results[{{ $target->id }}]?.rdns || '—'"></dd>
            </div>
            <div class="flex gap-2">
                <dt class="w-16 shrink-0 text-neutral-400">{{ __('ping.loss') }}</dt>
                <dd class="mono" x-text="(results[{{ $target->id }}]?.packet_loss_percent ?? 0) + '%'"></dd>
            </div>
            <div class="flex gap-2">
                <dt class="w-16 shrink-0 text-neutral-400">Jitter</dt>
                <dd class="mono" x-text="formatMs(results[{{ $target->id }}]?.jitter_ms)"></dd>
            </div>
        </dl>
    </div>
    <div>
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('ping.target_dns') }}</h3>
        <div class="max-h-28 space-y-1 overflow-y-auto">
            <template x-for="(rec, i) in (results[{{ $target->id }}]?.dns_records || [])" :key="i">
                <div class="mono text-[11px] text-neutral-600">
                    <span class="font-medium text-neutral-950" x-text="rec.type"></span>
                    <span x-text="' ' + (rec.value || '')"></span>
                </div>
            </template>
            <p
                class="text-xs text-neutral-400"
                x-show="!(results[{{ $target->id }}]?.dns_records || []).length"
                x-text="i18n.no_records"
            ></p>
        </div>
    </div>
    <div>
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('ping.target_edns') }}</h3>
        <dl class="space-y-1.5 mono text-[11px] text-neutral-600">
            <div>{{ __('ping.support') }}: <span x-text="results[{{ $target->id }}]?.edns_data?.support ? i18n.yes : i18n.no"></span></div>
            <div>UDP: <span x-text="results[{{ $target->id }}]?.edns_data?.udp_payload_size || '—'"></span></div>
            <div>Ver: <span x-text="results[{{ $target->id }}]?.edns_data?.edns_version ?? '—'"></span></div>
            <div class="break-all">NS: <span x-text="results[{{ $target->id }}]?.edns_data?.nameserver || '—'"></span></div>
        </dl>
    </div>
</div>
