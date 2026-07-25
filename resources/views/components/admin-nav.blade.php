@props([
    'variant' => 'sidebar', // sidebar | mobile
])

@php
    $links = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => __('admin.nav_dashboard')],
        ['route' => 'admin.targets.index', 'pattern' => 'admin.targets.*', 'label' => __('admin.nav_targets')],
        ['route' => 'admin.categories.index', 'pattern' => 'admin.categories.*', 'label' => __('admin.nav_categories')],
        ['route' => 'admin.providers.index', 'pattern' => 'admin.providers.*', 'label' => __('admin.nav_providers')],
        ['route' => 'admin.results.index', 'pattern' => 'admin.results.*', 'label' => __('admin.nav_results')],
    ];
@endphp

@if($variant === 'sidebar')
    <nav {{ $attributes->class('space-y-0.5 p-3 text-xs uppercase tracking-widest') }}>
        @foreach($links as $link)
            <a
                href="{{ route($link['route']) }}"
                @class([
                    'block px-3 py-2.5 transition-colors',
                    'bg-neutral-950 text-white' => request()->routeIs($link['pattern']),
                    'text-neutral-600 hover:bg-neutral-100' => ! request()->routeIs($link['pattern']),
                ])
            >{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ route('history.index') }}" class="mt-4 block border-t border-neutral-200 px-3 py-2.5 pt-4 text-neutral-500 transition-colors hover:bg-neutral-100">
            ← {{ __('admin.nav_back_to_panel') }}
        </a>
    </nav>
@else
    <nav {{ $attributes->class('flex flex-col gap-1 p-3 text-xs uppercase tracking-widest') }}>
        @foreach($links as $link)
            <a
                href="{{ route($link['route']) }}"
                @click="close()"
                @class([
                    'px-3 py-2.5',
                    'bg-neutral-950 text-white' => request()->routeIs($link['pattern']),
                    'text-neutral-600' => ! request()->routeIs($link['pattern']),
                ])
            >{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ route('history.index') }}" class="px-3 py-2.5 text-neutral-500" @click="close()">
            ← {{ __('admin.nav_back_to_panel') }}
        </a>
    </nav>
@endif
