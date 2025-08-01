<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // UserSignatureSeeder::class, // Disabled - using real files instead
            PoApprovalSeeder::class,
            PoPrintSeeder::class,
        ]);
    }
}