<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->json('network_analysis')->nullable()->after('client_dns');
        });
    }

    public function down(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->dropColumn('network_analysis');
        });
    }
};
