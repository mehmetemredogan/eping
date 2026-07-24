<?php

namespace Tests\Feature\Api;

use App\Models\PingTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_targets_without_a_provider_are_grouped_under_other(): void
    {
        PingTarget::create([
            'name' => 'No Provider',
            'host' => 'example.com',
            'category' => 'other',
            'provider' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/targets');

        $response->assertOk();
        $groups = collect($response->json('groups'));
        $other = $groups->firstWhere('provider', 'Other');

        $this->assertNotNull($other, 'Expected an "Other" group for targets without a provider.');
        $this->assertCount(1, $other['targets']);
    }

    public function test_targets_are_ordered_by_sort_order_within_provider(): void
    {
        PingTarget::create([
            'name' => 'Second', 'host' => 'b.example.com', 'category' => 'other',
            'provider' => 'Acme', 'is_active' => true, 'sort_order' => 2,
        ]);
        PingTarget::create([
            'name' => 'First', 'host' => 'a.example.com', 'category' => 'other',
            'provider' => 'Acme', 'is_active' => true, 'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/targets');

        $response->assertOk();
        $groups = collect($response->json('groups'));
        $acme = $groups->firstWhere('provider', 'Acme');

        $this->assertSame('First', $acme['targets'][0]['name']);
        $this->assertSame('Second', $acme['targets'][1]['name']);
    }

    public function test_inactive_targets_are_excluded(): void
    {
        PingTarget::create([
            'name' => 'Hidden', 'host' => 'hidden.example.com', 'category' => 'other',
            'provider' => 'Acme', 'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/targets');

        $response->assertOk();
        $this->assertSame(0, $response->json('count'));
    }
}
