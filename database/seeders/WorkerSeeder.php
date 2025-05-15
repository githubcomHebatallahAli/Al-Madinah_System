<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('workers')->insert([
        'title_id'=> '1',
        'store_id'=>null,
        'name'=>'Branch Manager',
        'idNum'=> '1234',
        'personPhoNum'=>'0111111',
        'branchPhoNum'=>'0222222222',
        'salary'=>'500',
        'status'=>'active',
        'dashboardAccess'=>'ok',
            ]);

          DB::table('workers')->insert([
        'title_id'=> '2',
        'store_id'=>null,
        'name'=>'Delegate',
        'idNum'=> '1234',
        'personPhoNum'=>'0111111',
        'branchPhoNum'=>'0222222222',
        'salary'=>'500',
        'status'=>'active',
        'dashboardAccess'=>'ok',
            ]);
          DB::table('workers')->insert([
        'title_id'=> '3',
        'store_id'=>null,
        'name'=>'Super Visor',
        'idNum'=> '1234',
        'personPhoNum'=>'0111111',
        'branchPhoNum'=>'0222222222',
        'salary'=>'500',
        'status'=>'active',
        'dashboardAccess'=>'ok',
            ]);
          DB::table('workers')->insert([
        'title_id'=> '4',
        'store_id'=>null,
        'name'=>'ACountant',
        'idNum'=> '1234',
        'personPhoNum'=>'0111111',
        'branchPhoNum'=>'0222222222',
        'salary'=>'500',
        'status'=>'active',
        'dashboardAccess'=>'ok',
            ]);

          DB::table('workers')->insert([
        'title_id'=> '5',
        'store_id'=>'1',
        'name'=>'Storekeeper',
        'idNum'=> '1234',
        'personPhoNum'=>'0111111',
        'branchPhoNum'=>'0222222222',
        'salary'=>'500',
        'status'=>'active',
        'dashboardAccess'=>'ok',
            ]);
    }
}
