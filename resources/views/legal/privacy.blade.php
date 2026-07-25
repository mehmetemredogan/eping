<x-legal-page
    :title="__('legal.privacy_title')"
    :eyebrow="__('legal.nav_privacy')"
    :updated="__('legal.privacy_updated')"
    exclude="privacy"
>
    <x-slot:intro>{{ __('legal.privacy_intro') }}</x-slot:intro>

    <x-legal-section :title="__('legal.privacy_s1_title')">
        <p>{{ __('legal.privacy_s1_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.privacy_s2_title')">
        <p>{{ __('legal.privacy_s2_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.privacy_s3_title')">
        <ul class="list-disc space-y-2 pl-5">
            @foreach(__('legal.privacy_s3_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </x-legal-section>
    <x-legal-section :title="__('legal.privacy_s4_title')">
        <p>{{ __('legal.privacy_s4_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.privacy_s5_title')">
        <p>{{ __('legal.privacy_s5_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.privacy_s6_title')">
        <p>{{ __('legal.privacy_s6_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.privacy_s7_title')">
        <p>{{ __('legal.privacy_s7_body') }}</p>
    </x-legal-section>
</x-legal-page>
