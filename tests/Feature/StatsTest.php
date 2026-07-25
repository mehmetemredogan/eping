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

    private function seedSuccessfulResults(
        string $isp,
        string $provider,
        string $host,
        string $ip,
        int $count,
        float $avg,
        ?string $connectionType = null,
    ): PingTarget {
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
                'connection_type' => $connectionType,
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
        $response->assertSee('Turk Telekom', false);
        $response->assertSee('Cloudflare', false);
        $response->assertSee('1.1.1.1', false);
        // Below the k-anonymity threshold: absent from the table (may still appear in filter lists).
        $response->assertDontSee('aws.example.com', false);
        $response->assertDontSee('3.120.1.1', false);
        $response->assertDontSee('203.0.113.', false);
    }

    public function test_stats_row_data_is_rendered_in_table(): void
    {
        $this->seedSuccessfulResults('Turk Telekom', 'Cloudflare', '1.1.1.1', '1.1.1.1', 3, 40);

        $response = $this->get(route('stats.index', ['min_samples' => 3]));

        $response->assertOk();
        $response->assertSee('Turk Telekom', false);
        $response->assertSee('41 ms', false);
        $response->assertDontSee('kullanıcıları', false);
    }

    public function test_stats_never_leaks_client_ip_or_username(): void
    {
        $this->seedSuccessfulResults('Turk Telekom', 'Cloudflare', '1.1.1.1', '1.1.1.1', 3, 40);

        $html = $this->get(route('stats.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('203.0.113.', $html);
        $this->assertStringNotContainsString('user_id', $html);
    }

    public function test_stats_split_wifi_and_ethernet_averages(): void
    {
        $target = PingTarget::create([
            'name' => 'Cloudflare Node',
            'host' => '1.1.1.1',
            'category' => 'other',
            'provider' => 'Cloudflare',
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        foreach ([
            ['wifi', 30.0],
            ['wifi', 50.0],
            ['ethernet', 20.0],
            ['ethernet', 40.0],
        ] as $i => [$link, $avg]) {
            PingResult::create([
                'ping_target_id' => $target->id,
                'session_id' => sprintf('22222222-2222-2222-2222-%012d', $i),
                'status' => 'success',
                'avg_latency_ms' => $avg,
                'min_latency_ms' => $avg,
                'max_latency_ms' => $avg + 5,
                'resolved_ip' => '1.1.1.1',
                'client_ip' => '203.0.113.'.($i + 10),
                'client_isp' => 'Turk Telekom',
                'client_asn' => '9121',
                'client_country_code' => 'TR',
                'connection_type' => $link,
                'user_id' => $user->id,
                'tested_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->get(route('stats.index', ['min_samples' => 3]));

        $response->assertOk();
        $response->assertSee(__('ping.stats_avg_overall'), false);
        $response->assertSee(__('ping.stats_avg_wifi'), false);
        $response->assertSee(__('ping.stats_avg_ethernet'), false);
        // overall avg = (30+50+20+40)/4 = 35; wifi = 40; ethernet = 30
        $response->assertSee('35 ms', false);
        $response->assertSee('40 ms', false);
        $response->assertSee('30 ms', false);
    }
}
