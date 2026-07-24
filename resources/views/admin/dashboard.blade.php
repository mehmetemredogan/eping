<x-admin-layout :header="__('admin.dashboard_title')">
    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="border border-neutral-950 bg-white p-5">
            <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('admin.total_targets') }}</div>
            <div class="mono mt-2 text-3xl font-semibold">{{ $targetCount }}</div>
            <div class="mt-1 text-xs text-green-600">{{ __('admin.active_suffix', ['count' => $activeTargetCount]) }}</div>
        </div>
        <div class="border border-neutral-950 bg-white p-5">
            <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('admin.total_tests') }}</div>
            <div class="mono mt-2 text-3xl font-semibold">{{ number_format($resultCount) }}</div>
        </div>
        <div class="border border-neutral-950 bg-white p-5">
            <div class="text-[10px] uppercase tracking-widest text-neutral-400">{{ __('admin.categories') }}</div>
            <div class="mono mt-2 text-3xl font-semibold">{{ $categoryStats->count() }}</div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="border border-neutral-950 bg-white p-5">
            <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest">{{ __('admin.category_breakdown') }}</h2>
            <div class="space-y-2 text-sm">
                @foreach(\App\Models\PingTarget::categories() as $key => $label)
                    @if($categoryStats->has($key))
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-neutral-500">{{ $label }}</span>
                            <span class="mono border border-neutral-200 px-2 py-0.5 text-xs">{{ $categoryStats[$key] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="border border-neutral-950 bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xs font-semibold uppercase tracking-widest">{{ __('admin.recent_tests') }}</h2>
                <a href="{{ route('admin.results.index') }}" class="mono text-xs text-neutral-500 hover:text-neutral-950">{{ __('admin.view_all') }}</a>
            </div>
            <div class="space-y-2">
                @forelse($recentResults as $result)
                    @php
                        $ms = $result->avg_latency_ms !== null ? (float) $result->avg_latency_ms : null;
                        $latencyTone = $ms === null ? 'text-neutral-400' : ($ms < 80 ? 'text-green-600' : ($ms < 180 ? 'text-yellow-600' : 'text-red-600'));
                    @endphp
                    <a href="{{ route('admin.results.show', $result) }}" class="block border border-neutral-200 px-3 py-2 transition-colors hover:border-neutral-950">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium">{{ $result->target?->name }}</span>
                            @if($ms !== null)
                                <span class="mono {{ $latencyTone }}">{{ $ms }} ms</span>
                            @else
                                <span class="text-xs text-red-600">{{ $result->status }}</span>
                            @endif
                        </div>
                        <div class="mono mt-1 text-xs text-neutral-400">
                            {{ $result->tested_at?->format('d.m.Y H:i') }} · {{ $result->resolved_ip }}
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-neutral-400">{{ __('admin.no_tests_yet') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
