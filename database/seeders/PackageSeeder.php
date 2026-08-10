<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        Package::create([
            'name' => 'Paket Mingguan (7 Hari)',
            'duration_days' => 7,
            'price' => 10000,
            'is_active' => true,
        ]);

        Package::create([
            'name' => 'Paket Bulanan (30 Hari)',
            'duration_days' => 30,
            'price' => 25000,
            'is_active' => true,
        ]);
    }
}
