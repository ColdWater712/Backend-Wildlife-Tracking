<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => Str::lower(Str::random(10)),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'status' => 'aktif',
        ];
    }

    public function role(string $name): static
    {
        return $this->state(fn () => ['role_id' => Role::where('name_role', $name)->value('role_id')]);
    }
}
