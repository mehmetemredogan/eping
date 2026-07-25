@props([
    'variant' => 'desktop', // desktop | mobile
])

@php
    $links = [
        ['route' => 'home', 'pattern' => 'home', 'label' => __('ping.nav_home'), 'auth' => false],
        ['route' => 'stats.index', 'pattern' => 'stats.*', 'label' => __('ping.nav_stats'), 'auth' => false],
        ['route' => 'history.index', 'pattern' => 'history.*', 'label' => __('ping.nav_history'), 'auth' => true],
        ['route' => 'settings.edit', 'pattern' => 'settings.*', 'label' => __('ping.nav_settings'), 'auth' => true],
    ];
@endphp

@if($variant === 'desktop')
    <nav {{ $attributes->class('hidden items-center gap-5 text-xs uppercase tracking-widest sm:flex') }}>
        @foreach($links as $link)
            @if(! $link['auth'] || auth()->check())
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'border-b border-neutral-950 pb-0.5 text-neutral-950' => request()->routeIs($link['pattern']),
                        'text-neutral-500 transition-colors hover:text-neutral-950' => ! request()->routeIs($link['pattern']),
                    ])
                >{{ $link['label'] }}</a>
            @endif
        @endforeach
        @auth
            @if(auth()->user()->is_admin)
                <a
                    href="{{ route('admin.dashboard') }}"
                    @class([
                        'border-b border-neutral-950 pb-0.5 text-neutral-950' => request()->routeIs('admin.*'),
                        'text-neutral-500 transition-colors hover:text-neutral-950' => ! request()->routeIs('admin.*'),
                    ])
                >{{ __('ping.nav_admin') }}</a>
            @endif
        @endauth
    </nav>
@else
    <nav {{ $attributes->class('mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm') }}>
        @foreach($links as $link)
            @if(! $link['auth'] || auth()->check())
                <a
                    href="{{ route($link['route']) }}"
                    @click="close()"
                    @class([
                        'px-2 py-2 uppercase tracking-widest',
                        'bg-neutral-950 text-white' => request()->routeIs($link['pattern']),
                        'text-neutral-600' => ! request()->routeIs($link['pattern']),
                    ])
                >{{ $link['label'] }}</a>
            @endif
        @endforeach
        @auth
            @if(auth()->user()->is_admin)
                <a
                    href="{{ route('admin.dashboard') }}"
                    @click="close()"
                    @class([
                        'px-2 py-2 uppercase tracking-widest',
                        'bg-neutral-950 text-white' => request()->routeIs('admin.*'),
                        'text-neutral-600' => ! request()->routeIs('admin.*'),
                    ])
                >{{ __('ping.nav_admin') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="border-t border-neutral-100 pt-2">
                @csrf
                <button type="submit" class="w-full px-2 py-2 text-left uppercase tracking-widest text-neutral-600">
                    {{ __('ping.logout') }} · {{ auth()->user()->username }}
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="px-2 py-2 uppercase tracking-widest text-neutral-600" @click="close()">{{ __('ping.login') }}</a>
            <a href="{{ route('register') }}" class="px-2 py-2 uppercase tracking-widest text-neutral-600" @click="close()">{{ __('ping.register') }}</a>
        @endauth
        <div class="mt-1 border-t border-neutral-100 pt-3">
            <x-locale-switcher variant="buttons" full />
        </div>
    </nav>
@endif
