<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('workers')->insert([
            'title_id' => '1',
            'store_id' => null,
            'role_id' => '2',
            'name'=> 'Branch Manager',
            'email' => 'branchManager@mail.com',
            'password' => Hash::make('branchManager@234'),
            'idNum' => '12345678901234',
            'personPhoNum' => '01000000000',
            'branchPhoNum' => '034444444',
            'salary' => 5000.00,
            'status' => 'active',
            'dashboardAccess' => 'ok',
            'creationDate' => now(),
            'creationDateHijri' => '1446-10-06',
        ]);
    }
}
