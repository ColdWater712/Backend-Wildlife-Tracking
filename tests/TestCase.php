<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = false;

    protected function seedAll(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    /** ID entitas seed berdasarkan kolom unik (aman untuk MySQL yang tidak mereset auto-increment). */
    protected function idOf(string $model, string $column, string $value): int
    {
        $class = "App\\Models\\{$model}";

        return (int) $class::query()->where($column, $value)->value((new $class())->getKeyName());
    }

    /** Login lewat endpoint asli dan kembalikan header Authorization. */
    protected function loginAs(string $username): array
    {
        $email = User::where('username', $username)->value('email');
        $token = $this->postJson('/api/login', ['email' => $email, 'password' => 'password123'])
            ->assertOk()
            ->json('data.token');

        $this->app['auth']->forgetGuards();

        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }
}
