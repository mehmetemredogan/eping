<x-legal-page
    :title="__('legal.cookies_title')"
    :eyebrow="__('legal.nav_cookies')"
    :updated="__('legal.cookies_updated')"
    exclude="cookies"
>
    <x-slot:intro>{{ __('legal.cookies_intro') }}</x-slot:intro>

    <x-legal-section :title="__('legal.cookies_s1_title')">
        <ul class="list-disc space-y-2 pl-5">
            @foreach(__('legal.cookies_s1_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </x-legal-section>
    <x-legal-section :title="__('legal.cookies_s2_title')">
        <p>{{ __('legal.cookies_s2_body') }}</p>
    </x-legal-section>
</x-legal-page>
