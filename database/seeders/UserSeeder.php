<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── SUPER ADMIN (no pertenece a ningún gym específico, gym_id=1) ───
        User::create([
            'gym_id'            => 1,
            'name'              => 'Super Admin',
            'email'             => 'super@visionfit.com',
            'password'          => Hash::make('password'),
            'role'              => 'super_admin',
            'email_verified_at' => now(),
        ]);

        // ─── ADMINS ───────────────────────────────────────────────────────
        User::create([
            'gym_id'            => 1,
            'name'              => 'Admin Central',
            'email'             => 'admin@visionfit.com',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'gym_id'            => 2,
            'name'              => 'Admin Norte',
            'email'             => 'admin.norte@visionfit.com',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        // ─── COACHES ──────────────────────────────────────────────────────
        User::create([
            'gym_id'            => 1,
            'name'              => 'Carlos Méndez',
            'email'             => 'coach.carlos@visionfit.com',
            'password'          => Hash::make('password'),
            'role'              => 'coach',
            'email_verified_at' => now(),
        ]);

        User::create([
            'gym_id'            => 1,
            'name'              => 'Laura Gómez',
            'email'             => 'coach.laura@visionfit.com',
            'password'          => Hash::make('password'),
            'role'              => 'coach',
            'email_verified_at' => now(),
        ]);

        User::create([
            'gym_id'            => 2,
            'name'              => 'Martín Ruiz',
            'email'             => 'coach.martin@visionfit.com',
            'password'          => Hash::make('password'),
            'role'              => 'coach',
            'email_verified_at' => now(),
        ]);

        // ─── CLIENTS GYM 1 ────────────────────────────────────────────────
        User::create([
            'gym_id'            => 1,
            'name'              => 'Lucas Fernández',
            'email'             => 'lucas@cliente.com',
            'password'          => Hash::make('password'),
            'role'              => 'client',
            'email_verified_at' => now(),
            'medical_notes'     => 'Sin lesiones previas.',
        ]);

        User::create([
            'gym_id'            => 1,
            'name'              => 'Sofía Ramírez',
            'email'             => 'sofia@cliente.com',
            'password'          => Hash::make('password'),
            'role'              => 'client',
            'email_verified_at' => now(),
            'medical_notes'     => 'Lesión de rodilla derecha (2022), evitar sentadilla profunda.',
        ]);

        User::create([
            'gym_id'            => 1,
            'name'              => 'Matías Torres',
            'email'             => 'matias@cliente.com',
            'password'          => Hash::make('password'),
            'role'              => 'client',
            'email_verified_at' => now(),
        ]);

        User::create([
            'gym_id'            => 1,
            'name'              => 'Valentina López',
            'email'             => 'valentina@cliente.com',
            'password'          => Hash::make('password'),
            'role'              => 'client',
            'email_verified_at' => now(),
        ]);

        // ─── CLIENTS GYM 2 ────────────────────────────────────────────────
        User::create([
            'gym_id'            => 2,
            'name'              => 'Agustín Morales',
            'email'             => 'agustin@cliente.com',
            'password'          => Hash::make('password'),
            'role'              => 'client',
            'email_verified_at' => now(),
        ]);

        User::create([
            'gym_id'            => 2,
            'name'              => 'Camila Herrera',
            'email'             => 'camila@cliente.com',
            'password'          => Hash::make('password'),
            'role'              => 'client',
            'email_verified_at' => now(),
        ]);
    }
}