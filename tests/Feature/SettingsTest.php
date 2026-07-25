<?php

namespace Tests\Feature;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_settings(): void
    {
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
    }

    public function test_settings_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee(__('ping.settings_title'), false);
    }

    public function test_user_can_update_username(): void
    {
        $user = User::factory()->create(['username' => 'old_name']);

        $response = $this->actingAs($user)->patch(route('settings.profile'), [
            'username' => 'New_Name',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $this->assertSame('new_name', $user->fresh()->username);
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create(['username' => 'mine']);

        $response = $this->actingAs($user)->from(route('settings.edit'))->patch(route('settings.profile'), [
            'username' => 'taken',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertSame('mine', $user->fresh()->username);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $token = $user->createToken('desktop')->plainTextToken;
        $this->assertNotSame(0, $user->tokens()->count());

        $response = $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        unset($token);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->from(route('settings.edit'))->put(route('settings.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_clear_own_history_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $target = PingTarget::create([
            'name' => 'Example',
            'host' => 'example.com',
            'category' => 'other',
            'provider' => 'Example',
            'is_active' => true,
        ]);

        PingResult::create([
            'ping_target_id' => $target->id,
            'session_id' => '11111111-1111-1111-1111-111111111111',
            'status' => 'success',
            'avg_latency_ms' => 10,
            'user_id' => $user->id,
            'tested_at' => now(),
        ]);
        PingResult::create([
            'ping_target_id' => $target->id,
            'session_id' => '22222222-2222-2222-2222-222222222222',
            'status' => 'success',
            'avg_latency_ms' => 20,
            'user_id' => $other->id,
            'tested_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete(route('settings.history'));

        $response->assertRedirect(route('settings.edit'));
        $this->assertDatabaseMissing('ping_results', ['user_id' => $user->id]);
        $this->assertDatabaseHas('ping_results', ['user_id' => $other->id]);
    }
}
