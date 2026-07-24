@php
    /** @var \App\Models\PingTarget $target */
@endphp
<div class="border-b border-neutral-100 px-3 py-3 last:border-b-0" :class="{ 'bg-neutral-50': expanded[{{ $target->id }}] }">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                @if($target->country_code)
                    <span class="mono shrink-0 rounded border border-neutral-200 bg-white px-1.5 py-0.5 text-[10px] font-medium uppercase text-neutral-600">
                        {{ strtoupper($target->country_code) }}
                    </span>
                @endif
                <div class="truncate font-medium text-neutral-950">{{ $target->name }}</div>
            </div>
            <div class="mt-1 mono break-all text-xs text-neutral-500">{{ $target->host }}</div>
            <div class="mt-0.5 text-xs text-neutral-400">{{ $target->location ?: '—' }} · {{ $target->category_label }}</div>
        </div>
        <div class="shrink-0 text-right">
            <template x-if="isLoading({{ $target->id }})">
                <span class="mono text-xs text-neutral-400" x-text="i18n.measuring"></span>
            </template>
            <template x-if="!isLoading({{ $target->id }}) && results[{{ $target->id }}]?.status === 'success'">
                <div
                    class="mono text-base font-medium"
                    :class="latencyClass(results[{{ $target->id }}]?.avg_latency_ms)"
                    x-text="formatMs(results[{{ $target->id }}]?.avg_latency_ms)"
                ></div>
            </template>
            <template x-if="!isLoading({{ $target->id }}) && results[{{ $target->id }}] && results[{{ $target->id }}].status !== 'success' && results[{{ $target->id }}].status !== 'pending'">
                <span class="text-xs font-medium text-red-600" x-text="statusLabel(results[{{ $target->id }}]?.status)"></span>
            </template>
            <template x-if="!isLoading({{ $target->id }}) && !results[{{ $target->id }}]">
                <span class="mono text-xs text-neutral-300">—</span>
            </template>
        </div>
    </div>

    <div class="mt-3 flex items-center gap-2">
        <button
            type="button"
            class="border border-neutral-950 bg-white px-3 py-1.5 text-xs font-medium text-neutral-950 transition-colors hover:bg-neutral-950 hover:text-white disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-neutral-950"
            :disabled="isLoading({{ $target->id }}) || loading"
            @click="runSingle({ id: {{ $target->id }}, host: @js($target->host) })"
        >
            {{ __('ping.test') }}
        </button>
        <button
            type="button"
            class="px-2 py-1.5 text-xs text-neutral-400 hover:text-neutral-950"
            x-show="results[{{ $target->id }}] && results[{{ $target->id }}].status !== 'pending'"
            x-cloak
            @click="toggleDetails({{ $target->id }})"
        >
            <span x-text="expanded[{{ $target->id }}] ? i18n.hide : i18n.details"></span>
        </button>
    </div>

    <div
        x-show="expanded[{{ $target->id }}]"
        x-cloak
        class="mt-3 grid gap-4 border-t border-neutral-200 pt-3"
    >
        @include('ping._target-details', ['target' => $target])
    </div>
</div>
