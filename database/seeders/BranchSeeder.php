<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('branches')->insert([
            'city_id'=> '1',
            'name' => 'Hogag',
            'address'=> 'vvv',
            'status' => 'active',
        ]);
    }
}
