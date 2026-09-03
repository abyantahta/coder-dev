<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Line;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $line1 = Line::where('name', 'Line 1')->firstOrFail();
        $line2 = Line::where('name', 'Line 2')->firstOrFail();

        User::create([
            'name' => 'General Manager',
            'email' => 'gm@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Gm,
        ]);

        User::create([
            'name' => 'Unit Head',
            'email' => 'unithead@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::UnitHead,
        ]);

        $groupHead = User::create([
            'name' => 'Group Head',
            'email' => 'grouphead@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::GroupHead,
        ]);
        $groupHead->lines()->sync([$line1->id, $line2->id]);

        $leader1 = User::create([
            'name' => 'Leader Line 1',
            'email' => 'leader1@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Leader,
        ]);
        $leader1->lines()->sync([$line1->id]);

        $leader2 = User::create([
            'name' => 'Leader Line 2',
            'email' => 'leader2@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Leader,
        ]);
        $leader2->lines()->sync([$line2->id]);
    }
}
