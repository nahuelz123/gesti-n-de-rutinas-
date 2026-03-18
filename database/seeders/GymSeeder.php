<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GymSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('gyms')->insert([
            [
                'name'       => 'VisionFit Central',
                'logo'       => null,
                'plan'       => 'premium',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'VisionFit Norte',
                'logo'       => null,
                'plan'       => 'basic',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}