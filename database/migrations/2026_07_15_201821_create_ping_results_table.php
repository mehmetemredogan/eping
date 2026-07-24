<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ping_target_id')->constrained()->cascadeOnDelete();
            $table->uuid('session_id')->nullable()->index();
            $table->string('status')->default('pending');
            $table->decimal('min_latency_ms', 10, 2)->nullable();
            $table->decimal('max_latency_ms', 10, 2)->nullable();
            $table->decimal('avg_latency_ms', 10, 2)->nullable();
            $table->decimal('jitter_ms', 10, 2)->nullable();
            $table->decimal('packet_loss_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('packets_sent')->default(4);
            $table->unsignedTinyInteger('packets_received')->default(0);
            $table->string('resolved_ip')->nullable();
            $table->string('rdns')->nullable();
            $table->json('dns_records')->nullable();
            $table->json('edns_data')->nullable();
            $table->text('ping_raw_output')->nullable();
            $table->string('client_ip')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->timestamps();

            $table->index('tested_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_results');
    }
};
