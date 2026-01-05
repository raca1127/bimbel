<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Guru
        User::firstOrCreate([
            'email' => 'guru1@example.com'
        ], [
            'name' => 'Guru 1',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        User::firstOrCreate([
            'email' => 'guru2@example.com'
        ], [
            'name' => 'Guru 2',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        // seeds used by FullDataSeeder fallback
        User::firstOrCreate(['email' => 'guru_seed@example.com'], ['name' => 'Seed Guru', 'password' => Hash::make('password'), 'role' => 'guru']);

        // Pelajar
        User::firstOrCreate(['email' => 'pelajar1@example.com'], ['name' => 'Pelajar 1', 'password' => Hash::make('password'), 'role' => 'pelajar', 'guru_status' => 'none', 'is_blocked' => false]);
        User::firstOrCreate(['email' => 'pelajar2@example.com'], ['name' => 'Pelajar 2', 'password' => Hash::make('password'), 'role' => 'pelajar', 'guru_status' => 'none', 'is_blocked' => false]);
        User::firstOrCreate(['email' => 'pelajar_seed@example.com'], ['name' => 'Seed Pelajar', 'password' => Hash::make('password'), 'role' => 'pelajar']);

        // Admin
        User::firstOrCreate(['email' => 'admin1@example.com'], ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'guru_status' => 'none', 'is_blocked' => false]);
        User::firstOrCreate(['email' => 'admin_seed@example.com'], ['name' => 'Seed Admin', 'password' => Hash::make('adminpass'), 'role' => 'admin']);
    }
}
