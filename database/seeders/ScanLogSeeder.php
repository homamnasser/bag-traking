<?php

namespace Database\Seeders;

use App\Models\Scan_Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScanLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Scan_Log::factory()->count(20)->create();
    }
}
