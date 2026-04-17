<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account (change password after first login)
        User::firstOrCreate(
            ['email' => 'admin@classquiz.local'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'timezone' => 'Asia/Dhaka',
            ]
        );

        // Sample teacher account
        User::firstOrCreate(
            ['email' => 'teacher@classquiz.local'],
            [
                'name'     => 'Teacher',
                'password' => Hash::make('password'),
                'role'     => 'teacher',
                'timezone' => 'Asia/Dhaka',
            ]
        );

        $this->call(SampleQuizSeeder::class);
    }
}
