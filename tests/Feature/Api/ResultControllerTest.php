<?php

namespace Tests\Feature\Api;

use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use RefreshDatabase;

    private function target(): PingTarget
    {
        return PingTarget::create([
            'name' => 'Example',
            'host' => 'example.com',
            'category' => 'other',
            'provider' => 'Example Inc',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_store_a_result(): void
    {
        $target = $this->target();

        $response = $this->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'success',
            'avg_latency_ms' => 20,
            'min_latency_ms' => 15,
            'max_latency_ms' => 25,
            'packets_sent' => 4,
            'packets_received' => 4,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_store_a_successful_result_with_default_packets_sent(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'success',
            'avg_latency_ms' => 20,
            'min_latency_ms' => 15,
            'max_latency_ms' => 25,
            'packets_received' => 4,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ping_results', [
            'ping_target_id' => $target->id,
            'user_id' => $user->id,
            // Default must match the Go client's default sample count (4),
            // not the previous inconsistent default of 3.
            'packets_sent' => 4,
        ]);
    }

    public function test_success_status_requires_latency_fields(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'success',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avg_latency_ms', 'min_latency_ms', 'max_latency_ms']);
    }

    public function test_min_latency_cannot_exceed_max_latency(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'success',
            'avg_latency_ms' => 20,
            'min_latency_ms' => 30,
            'max_latency_ms' => 10,
            'packets_received' => 4,
        ]);

        $response->assertStatus(422);
    }

    public function test_packets_received_cannot_exceed_packets_sent(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'success',
            'avg_latency_ms' => 20,
            'min_latency_ms' => 15,
            'max_latency_ms' => 25,
            'packets_sent' => 2,
            'packets_received' => 4,
        ]);

        $response->assertStatus(422);
    }

    public function test_failed_status_does_not_require_latency_fields(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'failed',
            'packets_received' => 0,
        ]);

        $response->assertStatus(201);
    }
}
