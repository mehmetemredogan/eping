<?php

namespace Tests\Feature\Api;

use App\Models\NetworkQualityTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkQualityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_store_network_quality(): void
    {
        $response = $this->postJson('/api/v1/quality', [
            'score' => 90,
            'grade' => 'A+',
            'status' => 'excellent',
            'results' => [
                ['domain' => 'google.com', 'ok' => true, 'status_code' => 200],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_store_network_quality(): void
    {
        $user = User::factory()->create();

        $payload = [
            'score' => 88,
            'grade' => 'A',
            'status' => 'good',
            'summary' => 'Web erişimi stabil',
            'avg_latency_ms' => 25.4,
            'avg_dns_ms' => 8.2,
            'avg_tcp_ms' => 14.5,
            'avg_tls_ms' => 22.0,
            'avg_ttfb_ms' => 38.1,
            'packet_loss_percent' => 0.0,
            'connection_type' => 'ethernet',
            'results' => [
                [
                    'name' => 'Google',
                    'domain' => 'google.com',
                    'category' => 'search',
                    'ok' => true,
                    'status_code' => 200,
                    'dns_ms' => 4.5,
                    'tcp_ms' => 12.1,
                    'tls_ms' => 18.0,
                    'ttfb_ms' => 30.5,
                    'total_ms' => 35.0,
                ],
                [
                    'name' => 'Cloudflare',
                    'domain' => 'cloudflare.com',
                    'category' => 'cdn',
                    'ok' => true,
                    'status_code' => 200,
                    'dns_ms' => 3.2,
                    'tcp_ms' => 10.0,
                    'tls_ms' => 16.5,
                    'ttfb_ms' => 28.0,
                    'total_ms' => 32.0,
                ],
            ],
            'insights' => ['DNS hızı mükemmel', 'TLS el sıkışması akıcı'],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/quality', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'score', 'grade', 'status', 'summary', 'tested_at']);
        $this->assertDatabaseHas('network_quality_tests', [
            'user_id' => $user->id,
            'score' => 88,
            'grade' => 'A',
            'connection_type' => 'ethernet',
        ]);
    }

    public function test_latest_and_history_endpoints(): void
    {
        $user = User::factory()->create();

        NetworkQualityTest::create([
            'user_id' => $user->id,
            'score' => 95,
            'grade' => 'A+',
            'status' => 'excellent',
            'results' => [
                ['domain' => 'google.com', 'ok' => true, 'status_code' => 200],
            ],
            'tested_at' => now(),
        ]);

        $latestResp = $this->getJson('/api/v1/quality/latest');
        $latestResp->assertStatus(200);
        $latestResp->assertJsonPath('test.score', 95);

        $historyResp = $this->actingAs($user, 'sanctum')->getJson('/api/v1/quality/history');
        $historyResp->assertStatus(200);
        $historyResp->assertJsonPath('count', 1);
    }

    public function test_quality_targets_endpoint(): void
    {
        \App\Models\NetworkQualityTarget::create([
            'name' => 'GitHub',
            'url' => 'https://github.com',
            'category' => 'code',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        \App\Models\NetworkQualityTarget::create([
            'name' => 'Inactive Target',
            'url' => 'https://inactive.com',
            'category' => 'test',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/quality/targets');
        $response->assertStatus(200);
        $response->assertJsonPath('count', 1);
        $response->assertJsonCount(1, 'targets');
        $response->assertJsonFragment(['name' => 'GitHub', 'domain' => 'github.com']);
        $response->assertJsonMissing(['name' => 'Inactive Target']);
    }
}
