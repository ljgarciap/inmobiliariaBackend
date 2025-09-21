<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Usuario Test',
            'email' => 'test@test.com',
            'password' => Bcrypt('12345'),
            'email_verified_at' => now(),
        ]);
    }
}
