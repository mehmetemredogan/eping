<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * There is no public homepage: guests are redirected to the login page,
     * and authenticated users are redirected to their member panel.
     */
    public function test_the_root_url_redirects_guests_to_stats(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('stats.index', absolute: false));
    }

    public function test_the_root_url_redirects_authenticated_users_to_the_member_panel(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertRedirect(route('history.index', absolute: false));
    }
}
