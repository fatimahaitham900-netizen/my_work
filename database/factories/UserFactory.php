<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'عبدالله محمد القحطاني',
                'خالد سعد العتيبي',
                'فهد ناصر الدوسري',
                'سلطان علي الحربي',
                'تركي سليمان الشمري',
                'ماجد حسن الغامدي',
            ]),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::ExecutiveViewer,
            'job_title' => 'محلل تقارير تنفيذية',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Admin,
            'job_title' => 'مدير النظام التنفيذي',
        ]);
    }

    public function operationsManager(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::OperationsManager,
            'job_title' => 'مدير عمليات الأسطول',
        ]);
    }

    public function fleetSupervisor(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::FleetSupervisor,
            'job_title' => 'مشرف المعدات والورشة',
        ]);
    }

    public function executiveViewer(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::ExecutiveViewer,
            'job_title' => 'مدير المكتب التنفيذي',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }
}
