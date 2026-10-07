<?php

namespace Database\Factories;

use App\Modules\Billing\Models\Plan;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => User::ROL_USUARIO,
            'tasa_libre_riesgo' => '0',
            'estado' => User::ESTADO_ACTIVO,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superadmin(): static
    {
        return $this->state(fn () => ['rol' => User::ROL_SUPERADMIN, 'plan_id' => null]);
    }

    public function suspendido(): static
    {
        return $this->state(fn () => ['estado' => User::ESTADO_SUSPENDIDO]);
    }
}
