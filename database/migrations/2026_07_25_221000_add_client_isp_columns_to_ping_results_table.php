<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->string('client_asn', 32)->nullable()->after('client_geo');
            $table->string('client_isp')->nullable()->after('client_asn');
            $table->string('client_country_code', 2)->nullable()->after('client_isp');

            $table->index(['client_isp', 'status', 'tested_at']);
            $table->index(['client_asn', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('ping_results', function (Blueprint $table) {
            $table->dropIndex(['client_isp', 'status', 'tested_at']);
            $table->dropIndex(['client_asn', 'status']);
            $table->dropColumn(['client_asn', 'client_isp', 'client_country_code']);
        });
    }
};
