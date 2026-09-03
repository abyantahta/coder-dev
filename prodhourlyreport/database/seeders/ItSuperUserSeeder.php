<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ItSuperUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'abyan@example.com'],
            [
                'name' => 'Abyan',
                'password' => Hash::make('password'),
                'role' => UserRole::ItSuperUser,
                'is_active' => true,
            ],
        );
    }
}
