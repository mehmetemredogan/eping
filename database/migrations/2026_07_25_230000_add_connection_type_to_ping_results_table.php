<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->string('connection_type', 16)->nullable()->after('client_country_code');
            $table->index('connection_type');
        });
    }

    public function down(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->dropIndex(['connection_type']);
            $table->dropColumn('connection_type');
        });
    }
};
