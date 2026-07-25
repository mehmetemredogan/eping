<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_explains_the_product_and_links_to_the_client(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('ping.home_brand'), false);
        $response->assertSee(__('ping.home_download'), false);
        $response->assertSee('https://github.com/mehmetemredogan/eping', false);
    }
}
