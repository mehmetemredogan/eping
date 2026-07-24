<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
