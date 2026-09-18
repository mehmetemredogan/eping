<?php

namespace Tests\Feature;

use App\Models\NetworkQualityTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkQualityWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_index_page_is_accessible(): void
    {
        $response = $this->get('/quality');
        $response->assertStatus(200);
        $response->assertSee('Ağ Kalitesi');
    }

    public function test_quality_show_page_displays_details(): void
    {
        $test = NetworkQualityTest::create([
            'client_ip' => '1.1.1.1',
            'score' => 95,
            'grade' => 'A+',
            'status' => 'excellent',
            'summary' => 'Harika bağlantı kalitesi',
            'avg_dns_ms' => 12.5,
            'avg_tcp_ms' => 25.0,
            'avg_tls_ms' => 30.0,
            'avg_ttfb_ms' => 55.0,
            'avg_latency_ms' => 65.0,
            'packet_loss_percent' => 0.0,
            'results' => [
                [
                    'name' => 'Cloudflare DNS',
                    'url' => 'https://1.1.1.1',
                    'category' => 'cdn',
                    'dns_ms' => 5.2,
                    'tcp_ms' => 12.1,
                    'tls_ms' => 15.0,
                    'ttfb_ms' => 20.0,
                    'total_ms' => 25.0,
                    'status_code' => 200,
                    'ok' => true,
                ],
            ],
            'insights' => [
                'grade' => 'A+',
                'dns_health' => 'excellent',
            ],
            'tested_at' => now(),
        ]);

        $response = $this->get('/quality/' . $test->id);
        $response->assertStatus(200);
        $response->assertSee('Cloudflare DNS');
        $response->assertSee('95');
    }
}
