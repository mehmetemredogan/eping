<?php

namespace Tests\Feature;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_detailed_history_log_with_traceroute(): void
    {
        $user = User::factory()->create();
        $target = PingTarget::create([
            'name' => 'CF',
            'host' => '1.1.1.1',
            'category' => 'other',
            'is_active' => true,
        ]);

        $result = PingResult::create([
            'ping_target_id' => $target->id,
            'session_id' => '11111111-1111-1111-1111-111111111111',
            'status' => 'success',
            'avg_latency_ms' => 22,
            'min_latency_ms' => 18,
            'max_latency_ms' => 30,
            'user_id' => $user->id,
            'tested_at' => now(),
            'network_analysis' => [
                'status' => 'good',
                'status_label' => 'İyi',
                'summary' => 'ok',
                'path' => [
                    'tool' => 'tracert',
                    'hop_count' => 3,
                    'reached' => true,
                    'hops' => [
                        ['ttl' => 1, 'ip' => '192.168.1.1', 'avg_ms' => 10, 'timeout' => false, 'kind' => 'private'],
                        ['ttl' => 2, 'ip' => '10.0.0.1', 'avg_ms' => 12, 'timeout' => false, 'kind' => 'private'],
                        ['ttl' => 3, 'ip' => '1.1.1.1', 'avg_ms' => 50, 'timeout' => false, 'kind' => 'public'],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('history.show', $result));

        $response->assertOk();
        $response->assertSee('1.1.1.1', false);
        $response->assertSee(__('ping.trace_bottleneck'), false);
        $response->assertSee(__('ping.traceroute_section'), false);
    }

    public function test_user_cannot_view_another_users_history_detail(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $target = PingTarget::create([
            'name' => 'CF',
            'host' => '1.1.1.1',
            'category' => 'other',
            'is_active' => true,
        ]);

        $result = PingResult::create([
            'ping_target_id' => $target->id,
            'session_id' => '11111111-1111-1111-1111-111111111112',
            'status' => 'success',
            'avg_latency_ms' => 20,
            'user_id' => $owner->id,
            'tested_at' => now(),
        ]);

        $this->actingAs($other)->get(route('history.show', $result))->assertNotFound();
    }
}
