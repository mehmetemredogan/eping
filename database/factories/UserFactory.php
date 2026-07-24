<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName().random_int(10, 99),
            'password' => static::$password ??= 'password',
            'is_admin' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
