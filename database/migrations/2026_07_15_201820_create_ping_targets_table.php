<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_targets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->string('category')->default('other');
            $table->string('provider')->nullable();
            $table->string('location')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_targets');
    }
};
