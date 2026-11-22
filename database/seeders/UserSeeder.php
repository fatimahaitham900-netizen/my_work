<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'هيثم',
                'email' => 'admin@fleet.local',
                'role' => UserRole::Admin,
                'job_title' => 'مدير النظام التنفيذي',
                'password' => '123456***',
                'is_active' => true,
            ],
            [
                'name' => 'م. خالد العتيبي',
                'email' => 'ops@fleet.local',
                'role' => UserRole::OperationsManager,
                'job_title' => 'مدير عمليات الأسطول والمعدات',
                'password' => 'password',
                'is_active' => false,
            ],
            [
                'name' => 'فهد ناصر الحربي',
                'email' => 'supervisor@fleet.local',
                'role' => UserRole::FleetSupervisor,
                'job_title' => 'مشرف الصيانة والمعدات',
                'password' => 'password',
                'is_active' => false,
            ],
            [
                'name' => 'أ. سلطان القحطاني',
                'email' => 'executive@fleet.local',
                'role' => UserRole::ExecutiveViewer,
                'job_title' => 'مدير المكتب التنفيذي',
                'password' => 'password',
                'is_active' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'job_title' => $userData['job_title'],
                    'is_active' => $userData['is_active'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($userData['password']),
                ],
            );
        }
    }
}
