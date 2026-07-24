<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('id');
        });

        $users = DB::table('users')->orderBy('id')->get();
        foreach ($users as $user) {
            $base = null;
            if (! empty($user->email) && str_contains((string) $user->email, '@')) {
                $base = strtolower(explode('@', (string) $user->email)[0]);
            } elseif (! empty($user->name)) {
                $base = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '', (string) $user->name) ?: 'user');
            } else {
                $base = 'user'.$user->id;
            }

            $username = $base;
            $i = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base.$i;
                $i++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN username SET NOT NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_username_unique ON users (username)');

            DB::statement('ALTER TABLE users ALTER COLUMN name DROP NOT NULL');
            DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');

            // Drop email unique constraint if it exists.
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
        } else {
            // sqlite (tests): NOT NULL retrofits need table rebuilds, a unique index is enough.
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_username_unique ON users (username)');
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
