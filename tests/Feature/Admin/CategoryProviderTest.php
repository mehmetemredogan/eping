<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\PingTarget;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryProviderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'slug' => 'custom_cloud',
            'name_tr' => 'Özel Bulut',
            'name_en' => 'Custom Cloud',
            'sort_order' => 50,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'slug' => 'custom_cloud',
            'name_tr' => 'Özel Bulut',
            'name_en' => 'Custom Cloud',
        ]);
        $this->assertArrayHasKey('custom_cloud', PingTarget::categories());
    }

    public function test_renaming_category_slug_updates_targets(): void
    {
        $admin = $this->admin();
        $category = Category::query()->where('slug', 'cdn')->firstOrFail();

        PingTarget::create([
            'name' => 'CDN Edge',
            'host' => 'cdn.example.com',
            'category' => 'cdn',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'slug' => 'edge_cdn',
            'name_tr' => 'CDN',
            'name_en' => 'CDN',
            'sort_order' => 150,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('ping_targets', ['host' => 'cdn.example.com', 'category' => 'edge_cdn']);
        $this->assertDatabaseMissing('categories', ['slug' => 'cdn']);
    }

    public function test_cannot_delete_category_in_use(): void
    {
        $admin = $this->admin();
        $category = Category::query()->where('slug', 'aws')->firstOrFail();

        PingTarget::create([
            'name' => 'AWS',
            'host' => 'aws.example.com',
            'category' => 'aws',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['slug' => 'aws']);
    }

    public function test_admin_can_create_and_rename_provider(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.providers.store'), [
            'name' => 'Acme Cloud',
            'description' => '**Acme** provider',
        ])->assertRedirect(route('admin.providers.index'));

        $provider = Provider::query()->where('name', 'Acme Cloud')->firstOrFail();

        PingTarget::create([
            'name' => 'Acme Node',
            'host' => 'acme.example.com',
            'category' => 'other',
            'provider' => 'Acme Cloud',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.providers.update', $provider), [
            'name' => 'Acme Networks',
            'description' => 'Updated',
        ])->assertRedirect(route('admin.providers.index'));

        $this->assertDatabaseHas('providers', ['name' => 'Acme Networks']);
        $this->assertDatabaseHas('ping_targets', [
            'host' => 'acme.example.com',
            'provider' => 'Acme Networks',
        ]);
    }

    public function test_cannot_delete_provider_in_use(): void
    {
        $admin = $this->admin();
        $provider = Provider::create(['name' => 'Busy Provider']);

        PingTarget::create([
            'name' => 'Node',
            'host' => 'busy.example.com',
            'category' => 'other',
            'provider' => 'Busy Provider',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.providers.destroy', $provider));

        $response->assertRedirect(route('admin.providers.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('providers', ['name' => 'Busy Provider']);
    }

    public function test_guest_cannot_manage_categories(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }
}
