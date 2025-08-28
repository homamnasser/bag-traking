<?php

namespace Database\Seeders;

use App\Models\DriverAreaService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DriverAreaServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DriverAreaService::factory()->count(10)->create();

    }
}
