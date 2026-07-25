@props([
    'messages' => null,
    'for' => null,
])

@php
    $list = $messages;
    if ($for !== null) {
        $list = $errors->get($for);
    }
    $list = collect(Arr::wrap($list ?? []))->filter();
@endphp

@foreach($list as $message)
    <p {{ $attributes->class('mt-1 text-xs text-red-600') }}>[!] {{ $message }}</p>
@endforeach
