<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class PingService
{
    public function ping(string $host, int $count = 4, int $timeoutSeconds = 3): array
    {
        $host = $this->sanitizeHost($host);
        $command = $this->buildCommand($host, $count, $timeoutSeconds);

        $result = Process::timeout($count * $timeoutSeconds + 10)->run($command);
        $output = $result->output().$result->errorOutput();

        return $this->parseOutput($output, $count, $result->successful() || str_contains($output, 'time=') || str_contains($output, 'time<'));
    }

    private function sanitizeHost(string $host): string
    {
        $host = trim($host);

        if (! preg_match('/^[a-zA-Z0-9.\-:]+$/', $host)) {
            throw new RuntimeException('Geçersiz host adresi.');
        }

        return $host;
    }

    private function buildCommand(string $host, int $count, int $timeoutSeconds): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $timeoutMs = $timeoutSeconds * 1000;

            return "ping -n {$count} -w {$timeoutMs} {$host}";
        }

        return "ping -c {$count} -W {$timeoutSeconds} {$host}";
    }

    private function parseOutput(string $output, int $packetsSent, bool $hasResponse): array
    {
        $latencies = [];
        $resolvedIp = null;

        if (PHP_OS_FAMILY === 'Windows') {
            if (preg_match('/Pinging .+ \[([^\]]+)\]/', $output, $m)) {
                $resolvedIp = $m[1];
            } elseif (preg_match('/Pinging ([^\s]+)/', $output, $m)) {
                $resolvedIp = filter_var($m[1], FILTER_VALIDATE_IP) ? $m[1] : null;
            }

            preg_match_all('/(?:time[=<])([\d.]+)\s*ms/i', $output, $matches);
            $latencies = array_map('floatval', $matches[1] ?? []);

            $packetsReceived = count($latencies);
            $packetLoss = null;

            if (preg_match('/\((\d+)% loss\)/', $output, $lossMatch)) {
                $packetLoss = (float) $lossMatch[1];
            } elseif (preg_match('/Lost = (\d+)/', $output, $lostMatch)) {
                $lost = (int) $lostMatch[1];
                $packetLoss = $packetsSent > 0 ? ($lost / $packetsSent) * 100 : 100;
            }

            $min = $max = $avg = null;
            if (preg_match('/Minimum = (\d+)ms, Maximum = (\d+)ms, Average = (\d+)ms/', $output, $stats)) {
                $min = (float) $stats[1];
                $max = (float) $stats[2];
                $avg = (float) $stats[3];
            }
        } else {
            if (preg_match('/PING [^(]+\(([^)]+)\)/', $output, $m)) {
                $resolvedIp = $m[1];
            }

            preg_match_all('/time=([\d.]+)\s*ms/', $output, $matches);
            $latencies = array_map('floatval', $matches[1] ?? []);

            $packetsReceived = count($latencies);
            $packetLoss = null;

            if (preg_match('/(\d+(?:\.\d+)?)% packet loss/', $output, $lossMatch)) {
                $packetLoss = (float) $lossMatch[1];
            }

            $min = $max = $avg = null;
            if (preg_match('/min\/avg\/max\/(?:mdev|stddev) = ([\d.]+)\/([\d.]+)\/([\d.]+)/', $output, $stats)) {
                $min = (float) $stats[1];
                $avg = (float) $stats[2];
                $max = (float) $stats[3];
            }
        }

        if ($packetLoss === null) {
            $packetsReceived = count($latencies);
            $packetLoss = $packetsSent > 0
                ? (($packetsSent - $packetsReceived) / $packetsSent) * 100
                : 100;
        }

        if ($min === null && $latencies !== []) {
            $min = min($latencies);
            $max = max($latencies);
            $avg = array_sum($latencies) / count($latencies);
        }

        $jitter = $this->calculateJitter($latencies);
        $status = $hasResponse && $packetsReceived > 0 ? 'success' : 'failed';

        if (str_contains(strtolower($output), 'timed out') && $packetsReceived === 0) {
            $status = 'timeout';
        }

        return [
            'status' => $status,
            'min_latency_ms' => $min,
            'max_latency_ms' => $max,
            'avg_latency_ms' => $avg,
            'jitter_ms' => $jitter,
            'packet_loss_percent' => $packetLoss,
            'packets_sent' => $packetsSent,
            'packets_received' => $packetsReceived,
            'resolved_ip' => $resolvedIp,
            'raw_output' => $output,
        ];
    }

    private function calculateJitter(array $latencies): ?float
    {
        if (count($latencies) < 2) {
            return null;
        }

        $differences = [];
        for ($i = 1; $i < count($latencies); $i++) {
            $differences[] = abs($latencies[$i] - $latencies[$i - 1]);
        }

        return array_sum($differences) / count($differences);
    }
}
