<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_tr');
            $table->string('name_en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['slug' => 'game_server', 'name_tr' => 'Oyun Sunucusu', 'name_en' => 'Game Server', 'sort_order' => 10],
            ['slug' => 'aws', 'name_tr' => 'Amazon AWS', 'name_en' => 'Amazon AWS', 'sort_order' => 20],
            ['slug' => 'azure', 'name_tr' => 'Microsoft Azure', 'name_en' => 'Microsoft Azure', 'sort_order' => 30],
            ['slug' => 'gcp', 'name_tr' => 'Google Cloud', 'name_en' => 'Google Cloud', 'sort_order' => 40],
            ['slug' => 'cloudflare', 'name_tr' => 'Cloudflare', 'name_en' => 'Cloudflare', 'sort_order' => 50],
            ['slug' => 'digitalocean', 'name_tr' => 'DigitalOcean', 'name_en' => 'DigitalOcean', 'sort_order' => 60],
            ['slug' => 'oracle', 'name_tr' => 'Oracle Cloud', 'name_en' => 'Oracle Cloud', 'sort_order' => 70],
            ['slug' => 'alibaba', 'name_tr' => 'Alibaba Cloud', 'name_en' => 'Alibaba Cloud', 'sort_order' => 80],
            ['slug' => 'hetzner', 'name_tr' => 'Hetzner', 'name_en' => 'Hetzner', 'sort_order' => 90],
            ['slug' => 'vultr', 'name_tr' => 'Vultr', 'name_en' => 'Vultr', 'sort_order' => 100],
            ['slug' => 'linode', 'name_tr' => 'Linode / Akamai', 'name_en' => 'Linode / Akamai', 'sort_order' => 110],
            ['slug' => 'ovh', 'name_tr' => 'OVHcloud', 'name_en' => 'OVHcloud', 'sort_order' => 120],
            ['slug' => 'scaleway', 'name_tr' => 'Scaleway', 'name_en' => 'Scaleway', 'sort_order' => 130],
            ['slug' => 'gaming_platform', 'name_tr' => 'Oyun Platformu', 'name_en' => 'Gaming Platform', 'sort_order' => 140],
            ['slug' => 'cdn', 'name_tr' => 'CDN', 'name_en' => 'CDN', 'sort_order' => 150],
            ['slug' => 'other', 'name_tr' => 'Diğer', 'name_en' => 'Other', 'sort_order' => 999],
        ];

        foreach ($defaults as $row) {
            DB::table('categories')->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
