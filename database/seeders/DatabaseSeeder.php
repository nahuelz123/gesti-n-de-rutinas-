<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GymSeeder::class,
            UserSeeder::class,
            ExerciseSeeder::class,
            RoutineSeeder::class,
            AssignmentSeeder::class,
            ExerciseLogSeeder::class,
        ]);
    }
}