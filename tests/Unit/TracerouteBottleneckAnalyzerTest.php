<?php

namespace Tests\Unit;

use App\Services\TracerouteBottleneckAnalyzer;
use PHPUnit\Framework\TestCase;

class TracerouteBottleneckAnalyzerTest extends TestCase
{
    public function test_marks_sharp_rtt_jumps_as_bottlenecks(): void
    {
        $analyzer = new TracerouteBottleneckAnalyzer;

        $hops = $analyzer->annotate([
            ['ttl' => 1, 'ip' => '192.168.1.1', 'avg_ms' => 10.0, 'timeout' => false],
            ['ttl' => 2, 'ip' => '10.0.0.1', 'avg_ms' => 12.0, 'timeout' => false],
            ['ttl' => 3, 'ip' => '1.1.1.1', 'avg_ms' => 50.0, 'timeout' => false],
            ['ttl' => 4, 'ip' => '1.0.0.1', 'avg_ms' => 52.0, 'timeout' => false],
        ]);

        $this->assertFalse($hops[0]['bottleneck']);
        $this->assertFalse($hops[1]['bottleneck']);
        $this->assertTrue($hops[2]['bottleneck']);
        $this->assertSame(38.0, $hops[2]['delta_ms']);
        $this->assertFalse($hops[3]['bottleneck']);
    }

    public function test_skips_timeout_hops_when_computing_delta(): void
    {
        $analyzer = new TracerouteBottleneckAnalyzer;

        $hops = $analyzer->annotate([
            ['ttl' => 1, 'avg_ms' => 8.0, 'timeout' => false],
            ['ttl' => 2, 'timeout' => true],
            ['ttl' => 3, 'avg_ms' => 40.0, 'timeout' => false],
        ]);

        $this->assertFalse($hops[1]['bottleneck']);
        $this->assertTrue($hops[2]['bottleneck']);
        $this->assertSame(32.0, $hops[2]['delta_ms']);
    }
}
