<x-legal-page
    :title="__('legal.terms_title')"
    :eyebrow="__('legal.nav_terms')"
    :updated="__('legal.terms_updated')"
    exclude="terms"
>
    <x-slot:intro>{{ __('legal.terms_intro') }}</x-slot:intro>

    <x-legal-section :title="__('legal.terms_s1_title')">
        <p>{{ __('legal.terms_s1_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s2_title')">
        <p>{{ __('legal.terms_s2_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s3_title')">
        <p>{{ __('legal.terms_s3_intro') }}</p>
        <ul class="list-disc space-y-2 pl-5">
            @foreach(__('legal.terms_s3_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s4_title')">
        <p>{{ __('legal.terms_s4_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s5_title')" emphasis>
        <p>{{ __('legal.terms_s5_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s6_title')">
        <p>{{ __('legal.terms_s6_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s7_title')">
        <p>{{ __('legal.terms_s7_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s8_title')">
        <p>{{ __('legal.terms_s8_body') }}</p>
    </x-legal-section>
    <x-legal-section :title="__('legal.terms_s9_title')">
        <p>{{ __('legal.terms_s9_body') }}</p>
    </x-legal-section>
</x-legal-page>
