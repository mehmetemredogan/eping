@php
    /** @var \App\Models\PingTarget $target */
@endphp
<tr
    class="group border-b border-neutral-100 align-top hover:bg-neutral-50"
    :class="{ 'bg-neutral-50': expanded[{{ $target->id }}] }"
>
    <td class="py-3 pl-4 pr-4">
        <div class="flex items-start gap-2">
            @if($target->country_code)
                <span class="mt-0.5 shrink-0 mono rounded border border-neutral-200 bg-white px-1.5 py-0.5 text-[10px] font-medium uppercase leading-none text-neutral-600">
                    {{ strtoupper($target->country_code) }}
                </span>
            @endif
            <div class="min-w-0">
                <div class="truncate font-medium text-neutral-950">{{ $target->name }}</div>
                <div class="truncate text-xs text-neutral-400">{{ $target->category_label }}</div>
            </div>
        </div>
    </td>
    <td class="py-3 pr-4">
        <span class="mono break-all text-xs text-neutral-600">{{ $target->host }}</span>
    </td>
    <td class="py-3 pr-4 text-neutral-500">
        {{ $target->location ?: '—' }}
    </td>
    <td class="py-3 pr-4 text-right">
        <template x-if="isLoading({{ $target->id }})">
            <span class="mono text-xs text-neutral-400" x-text="i18n.measuring"></span>
        </template>
        <template x-if="!isLoading({{ $target->id }}) && results[{{ $target->id }}]?.status === 'success'">
            <div>
                <div
                    class="mono text-base font-medium leading-none"
                    :class="latencyClass(results[{{ $target->id }}]?.avg_latency_ms)"
                    x-text="formatMs(results[{{ $target->id }}]?.avg_latency_ms)"
                ></div>
                <div class="mt-1 mono text-[10px] text-neutral-400">
                    <span x-text="formatMs(results[{{ $target->id }}]?.min_latency_ms)"></span>
                    /
                    <span x-text="formatMs(results[{{ $target->id }}]?.max_latency_ms)"></span>
                </div>
            </div>
        </template>
        <template x-if="!isLoading({{ $target->id }}) && results[{{ $target->id }}] && results[{{ $target->id }}].status !== 'success' && results[{{ $target->id }}].status !== 'pending'">
            <span class="text-xs font-medium text-red-600" x-text="statusLabel(results[{{ $target->id }}]?.status)"></span>
        </template>
        <template x-if="!isLoading({{ $target->id }}) && !results[{{ $target->id }}]">
            <span class="mono text-xs text-neutral-300">—</span>
        </template>
    </td>
    <td class="py-3 pl-4 pr-4 text-right">
        <div class="flex items-center justify-end gap-2">
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
    </td>
</tr>
<tr
    x-show="expanded[{{ $target->id }}]"
    x-cloak
    class="border-b border-neutral-100 bg-neutral-50"
>
    <td colspan="5" class="px-4 py-4">
        @include('ping._target-details', ['target' => $target])
    </td>
</tr>
