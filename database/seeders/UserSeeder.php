<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'full_name' => 'System Administrator',
                'email' => 'admin@company.com',
                'role' => 'admin',
                'approval_level' => 99,
                'is_active' => true,
            ]
        );

        // GM User
        User::firstOrCreate(
            ['username' => 'gm001'],
            [
                'password' => Hash::make('gm123'),
                'full_name' => 'General Manager',
                'email' => 'gm@company.com',
                'role' => 'gm',
                'approval_level' => 3,
                'is_active' => true,
            ]
        );

        // Manager User
        User::firstOrCreate(
            ['username' => 'manager001'],
            [
                'password' => Hash::make('manager123'),
                'full_name' => 'Department Manager',
                'email' => 'manager@company.com',
                'role' => 'manager',
                'approval_level' => 2,
                'is_active' => true,
            ]
        );

        // Regular User
        User::firstOrCreate(
            ['username' => 'user001'],
            [
                'password' => Hash::make('user123'),
                'full_name' => 'Regular User',
                'email' => 'user@company.com',
                'role' => 'user',
                'approval_level' => 1,
                'is_active' => true,
            ]
        );

        // Test Users เพิ่มเติม
        User::firstOrCreate(
            ['username' => 'manager002'],
            [
                'password' => Hash::make('manager123'),
                'full_name' => 'Second Manager',
                'email' => 'manager2@company.com',
                'role' => 'manager',
                'approval_level' => 2,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['username' => 'user002'],
            [
                'password' => Hash::make('user123'),
                'full_name' => 'Second User',
                'email' => 'user2@company.com',
                'role' => 'user',
                'approval_level' => 1,
                'is_active' => true,
            ]
        );
    }
}