<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_privacy_and_cookies_pages_are_public(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee(__('legal.terms_title'), false)
            ->assertSee(__('legal.terms_s5_title'), false);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee(__('legal.privacy_title'), false);

        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSee(__('legal.cookies_title'), false);
    }

    public function test_register_page_links_to_terms_and_requires_acceptance(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('legal.terms'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee('name="terms"', false);

        $this->from(route('register'))->post(route('register'), [
            'username' => 'legal_user',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha' => 'XXXX',
        ])->assertSessionHasErrors('terms');
    }

    public function test_favicon_is_linked_from_homepage(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('favicon.svg'), false);
    }
}
