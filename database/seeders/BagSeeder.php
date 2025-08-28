<?php

namespace Database\Seeders;

use App\Models\Bag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bag::factory()->count(20)->create();

    }
}
