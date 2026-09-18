<?php

namespace Tests\Feature\Admin;

use App\Models\NetworkQualityTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkQualityTargetAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_quality_targets_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        NetworkQualityTarget::create([
            'name' => 'Google Search',
            'url' => 'https://www.google.com',
            'category' => 'search',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/quality-targets');
        $response->assertStatus(200);
        $response->assertSee('Google Search');
    }

    public function test_admin_can_create_quality_target(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/quality-targets', [
            'name' => 'Cloudflare CDN',
            'url' => 'https://1.1.1.1',
            'category' => 'cdn',
            'sort_order' => 5,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/quality-targets');
        $this->assertDatabaseHas('network_quality_targets', [
            'name' => 'Cloudflare CDN',
            'url' => 'https://1.1.1.1',
            'domain' => '1.1.1.1',
            'category' => 'cdn',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_quality_target(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = NetworkQualityTarget::create([
            'name' => 'Old Name',
            'url' => 'https://old.com',
            'category' => 'old',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put("/admin/quality-targets/{$target->id}", [
            'name' => 'New Name',
            'url' => 'https://new.com/test',
            'category' => 'updated',
            'sort_order' => 10,
        ]);

        $response->assertRedirect('/admin/quality-targets');
        $this->assertDatabaseHas('network_quality_targets', [
            'id' => $target->id,
            'name' => 'New Name',
            'domain' => 'new.com',
            'category' => 'updated',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_quality_target(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = NetworkQualityTarget::create([
            'name' => 'To Delete',
            'url' => 'https://delete.me',
            'category' => 'temp',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/quality-targets/{$target->id}");
        $response->assertRedirect('/admin/quality-targets');
        $this->assertDatabaseMissing('network_quality_targets', [
            'id' => $target->id,
        ]);
    }

    public function test_non_admin_cannot_access_quality_targets(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $response = $this->actingAs($user)->get('/admin/quality-targets');
        $response->assertStatus(403);
    }
}
