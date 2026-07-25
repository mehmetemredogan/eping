@props([
    'href',
    'label' => null,
])

<div {{ $attributes->class('mb-4') }}>
    <a href="{{ $href }}" class="text-sm text-neutral-500 transition-colors hover:text-neutral-950">
        {{ $label ?? __('admin.back_to_logs') }}
    </a>
</div>
