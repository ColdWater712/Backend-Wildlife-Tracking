<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'role' => fake()->randomElement([
                'administrator_sistem',
                'peneliti_ekologi',
                'konservasionis_lapangan',
                'pengambil_kebijakan'
            ]),
            'created_at' => now(),
        ];
    }
}