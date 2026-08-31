<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'role' => UserRole::Administrator,
        ]);

        User::factory()->create([
            'name' => 'Firmante',
            'email' => 'signer@example.com',
            'role' => UserRole::Signer,
        ]);
    }
}
