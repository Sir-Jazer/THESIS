<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'System',
            'last_name'  => 'Administrator',
            'email'      => 'admin@system.com',
            'password'   => 'Admin@1234',
            'role'       => 'admin',
            'status'     => 'active',
        ]);
    }
}
