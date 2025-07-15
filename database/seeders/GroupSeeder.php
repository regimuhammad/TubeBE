<?php

namespace Database\Seeders;

use App\Models\arisan_group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        arisan_group::create([
            'user_id' => 1,
            'name' => 'arisan bulanan',
            'code' => 12345,
            'amount' => '10000',
            'start_date' => '2025-07-07',
            'duration' => '3 Bulan',
        ]);

    }
}
