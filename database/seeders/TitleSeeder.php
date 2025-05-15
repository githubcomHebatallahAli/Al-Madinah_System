<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('titles')->insert([
            'branch_id' => 1,
            'name' => 'Branch Manager',
            'workersCount' => 0,
            'creationDate' => Carbon::now(),
            'creationDateHijri' => '1446-10-06',
            'status' => 'active',
        ]);
            DB::table('titles')->insert([
            'branch_id' => 1,
            'name' => 'Delegate',
            'workersCount' => 0,
            'creationDate' => Carbon::now(),
            'creationDateHijri' => '1446-10-06',
            'status' => 'active',
        ]);
            DB::table('titles')->insert([
            'branch_id' => 1,
            'name' => 'Supervisor',
            'workersCount' => 0,
            'creationDate' => Carbon::now(),
            'creationDateHijri' => '1446-10-06',
            'status' => 'active',
        ]);
            DB::table('titles')->insert([
            'branch_id' => 1,
            'name' => 'Accountant',
            'workersCount' => 0,
            'creationDate' => Carbon::now(),
            'creationDateHijri' => '1446-10-06',
            'status' => 'active',
        ]);
            DB::table('titles')->insert([
            'branch_id' => 1,
            'name' => 'Storekeeper',
            'workersCount' => 0,
            'creationDate' => Carbon::now(),
            'creationDateHijri' => '1446-10-06',
            'status' => 'active',
        ]);
    }
    }

