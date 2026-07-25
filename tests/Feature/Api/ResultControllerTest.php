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

    public function test_stores_connection_type_and_traceroute_raw_detail(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/targets/{$target->id}/results", [
            'status' => 'success',
            'avg_latency_ms' => 22,
            'min_latency_ms' => 18,
            'max_latency_ms' => 30,
            'packets_sent' => 4,
            'packets_received' => 4,
            'connection_type' => 'wifi',
            'network_analysis' => [
                'status' => 'good',
                'summary' => 'ok',
                'connection_type' => 'wifi',
                'path' => [
                    'tool' => 'tracert',
                    'command' => 'tracert -d -h 20 example.com',
                    'hop_count' => 2,
                    'reached' => true,
                    'local_hops' => 1,
                    'public_hops' => 1,
                    'timeout_hops' => 0,
                    'raw' => "  1  192.168.1.1  1 ms\n  2  93.184.216.34  20 ms\n",
                    'hops' => [
                        ['ttl' => 1, 'ip' => '192.168.1.1', 'avg_ms' => 1.0, 'timeout' => false, 'kind' => 'private'],
                        ['ttl' => 2, 'ip' => '93.184.216.34', 'avg_ms' => 20.0, 'timeout' => false, 'kind' => 'public'],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ping_results', [
            'ping_target_id' => $target->id,
            'user_id' => $user->id,
            'connection_type' => 'wifi',
        ]);

        $stored = \App\Models\PingResult::query()->where('ping_target_id', $target->id)->first();
        $this->assertNotNull($stored);
        $this->assertSame('wifi', $stored->connection_type);
        $this->assertSame('tracert', $stored->network_analysis['path']['tool'] ?? null);
        $this->assertStringContainsString('192.168.1.1', $stored->network_analysis['path']['raw'] ?? '');
        $this->assertCount(2, $stored->network_analysis['path']['hops'] ?? []);
    }
}
