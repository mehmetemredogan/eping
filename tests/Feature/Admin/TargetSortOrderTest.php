<?php

namespace Tests\Feature\Admin;

use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetSortOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_sort_order_is_max_plus_one_for_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        PingTarget::create([
            'name' => 'A',
            'host' => 'a.example.com',
            'category' => 'aws',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        PingTarget::create([
            'name' => 'B',
            'host' => 'b.example.com',
            'category' => 'aws',
            'is_active' => true,
            'sort_order' => 7,
        ]);
        PingTarget::create([
            'name' => 'C',
            'host' => 'c.example.com',
            'category' => 'azure',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.targets.next-sort-order', ['category' => 'aws']))
            ->assertOk()
            ->assertJson([
                'category' => 'aws',
                'next' => 8,
            ]);

        $this->actingAs($admin)
            ->getJson(route('admin.targets.next-sort-order', ['category' => 'cloudflare']))
            ->assertOk()
            ->assertJson([
                'category' => 'cloudflare',
                'next' => 1,
            ]);
    }

    public function test_guest_cannot_fetch_next_sort_order(): void
    {
        $this->getJson(route('admin.targets.next-sort-order', ['category' => 'aws']))
            ->assertUnauthorized();
    }
}
