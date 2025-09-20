<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@powertime.au'],
            [
                'name' => 'Admin',
                'password' => Hash::make('xk5xnmj7'),
                'role' => 'admin', // make sure your users table has a 'role' column
            ]
        );
    }
}
