<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('network_quality_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('score')->default(0); // 0 - 100
            $table->string('grade', 8)->default('F'); // A+, A, B, C, D, F
            $table->string('status', 32)->default('unknown'); // excellent, good, fair, poor, degraded
            $table->string('summary', 255)->nullable();
            $table->decimal('avg_latency_ms', 8, 2)->nullable();
            $table->decimal('avg_dns_ms', 8, 2)->nullable();
            $table->decimal('avg_tcp_ms', 8, 2)->nullable();
            $table->decimal('avg_tls_ms', 8, 2)->nullable();
            $table->decimal('avg_ttfb_ms', 8, 2)->nullable();
            $table->decimal('packet_loss_percent', 5, 2)->default(0);
            $table->string('client_ip', 45)->nullable();
            $table->json('client_geo')->nullable();
            $table->string('client_isp', 128)->nullable();
            $table->string('client_asn', 32)->nullable();
            $table->string('client_country_code', 8)->nullable();
            $table->string('connection_type', 16)->nullable(); // wifi, ethernet, unknown
            $table->json('results'); // individual probe measurements per domain
            $table->json('insights')->nullable(); // list of insight strings
            $table->timestamp('tested_at');
            $table->timestamps();

            $table->index('tested_at');
            $table->index('grade');
            $table->index('connection_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_quality_tests');
    }
};
