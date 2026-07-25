<?php

namespace Tests\Feature;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private function seedSuccessfulResults(string $isp, string $provider, string $host, string $ip, int $count, float $avg): PingTarget
    {
        $target = PingTarget::create([
            'name' => $provider.' Node',
            'host' => $host,
            'category' => 'other',
            'provider' => $provider,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        for ($i = 0; $i < $count; $i++) {
            PingResult::create([
                'ping_target_id' => $target->id,
                'session_id' => sprintf('11111111-1111-1111-1111-%012d', $i),
                'status' => 'success',
                'avg_latency_ms' => $avg + $i,
                'min_latency_ms' => $avg,
                'max_latency_ms' => $avg + 10,
                'resolved_ip' => $ip,
                'client_ip' => '203.0.113.'.($i + 1),
                'client_isp' => $isp,
                'client_asn' => '9121',
                'client_country_code' => 'TR',
                'user_id' => $user->id,
                'tested_at' => now()->subMinutes($i),
            ]);
        }

        return $target;
    }

    public function test_stats_page_is_public(): void
    {
        $this->get(route('stats.index'))
            ->assertOk()
            ->assertSee(__('ping.stats_title'), false);
    }

    public function test_stats_aggregates_by_isp_and_hides_small_buckets(): void
    {
        $this->seedSuccessfulResults('Turk Telekom', 'Cloudflare', '1.1.1.1', '1.1.1.1', 3, 40);
        $this->seedSuccessfulResults('Vodafone TR', 'AWS', 'aws.example.com', '3.120.1.1', 1, 55);

        $response = $this->get(route('stats.index', ['min_samples' => 3]));

        $response->assertOk();
        $response->assertSee('Turk Telekom (TR) kullanıcıları', false);
        $response->assertSee('Cloudflare', false);
        $response->assertSee('1.1.1.1', false);
        // Below the k-anonymity threshold: absent from the table (may still appear in filter lists).
        $response->assertDontSee('aws.example.com', false);
        $response->assertDontSee('3.120.1.1', false);
        $response->assertDontSee('203.0.113.', false);
    }

    public function test_stats_summary_sentence_is_rendered(): void
    {
        $this->seedSuccessfulResults('Turk Telekom', 'Cloudflare', '1.1.1.1', '1.1.1.1', 3, 40);

        $response = $this->get(route('stats.index', ['min_samples' => 3]));

        $response->assertOk();
        $response->assertSee('Turk Telekom', false);
        $response->assertSee('40', false);
    }

    public function test_stats_never_leaks_client_ip_or_username(): void
    {
        $this->seedSuccessfulResults('Turk Telekom', 'Cloudflare', '1.1.1.1', '1.1.1.1', 3, 40);

        $html = $this->get(route('stats.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('203.0.113.', $html);
        $this->assertStringNotContainsString('user_id', $html);
    }
}
