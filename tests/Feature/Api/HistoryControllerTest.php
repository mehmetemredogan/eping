<?php

namespace Tests\Feature\Api;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
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

    private function makeResult(User $user, PingTarget $target, float $avgMs, \DateTimeInterface $testedAt): PingResult
    {
        return PingResult::create([
            'ping_target_id' => $target->id,
            'user_id' => $user->id,
            'status' => 'success',
            'avg_latency_ms' => $avgMs,
            'min_latency_ms' => $avgMs - 5,
            'max_latency_ms' => $avgMs + 5,
            'packet_loss_percent' => 0,
            'packets_sent' => 4,
            'packets_received' => 4,
            'tested_at' => $testedAt,
        ]);
    }

    public function test_trend_requires_authentication(): void
    {
        $this->getJson('/api/v1/results/trend')->assertStatus(401);
    }

    public function test_trend_reports_insufficient_data_with_no_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/results/trend');

        $response->assertOk();
        $this->assertSame('insufficient_data', $response->json('overall.trend'));
    }

    public function test_target_trend_detects_improvement(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        // Baseline: 5 older results averaging ~100ms.
        for ($i = 10; $i > 5; $i--) {
            $this->makeResult($user, $target, 100, now()->subHours($i));
        }
        // Recent: last results averaging ~50ms (50% faster).
        for ($i = 4; $i >= 0; $i--) {
            $this->makeResult($user, $target, 50, now()->subMinutes($i));
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/results/trend?target_id={$target->id}");

        $response->assertOk();
        $response->assertJsonPath('target.trend', 'improving');
    }

    public function test_target_trend_detects_degradation(): void
    {
        $user = User::factory()->create();
        $target = $this->target();

        for ($i = 10; $i > 5; $i--) {
            $this->makeResult($user, $target, 40, now()->subHours($i));
        }
        for ($i = 4; $i >= 0; $i--) {
            $this->makeResult($user, $target, 120, now()->subMinutes($i));
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/results/trend?target_id={$target->id}");

        $response->assertOk();
        $response->assertJsonPath('target.trend', 'degrading');
    }
}
