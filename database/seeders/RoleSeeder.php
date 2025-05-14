<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            'name' => 'Super Admin',
            'guardName'=> 'admin',
            'status'=> 'active'
        ]);

        DB::table('roles')->insert([
            'name' => 'Branch Manager',
            'guardName'=> 'worker',
            'status'=> 'active'
        ]);

        DB::table('roles')->insert([
            'name' => 'Delegate',
            'guardName'=> 'worker',
            'status'=> 'active'
        ]);

        DB::table('roles')->insert([
            'name' => 'Supervisor',
            'guardName'=> 'worker',
            'status'=> 'active'
        ]);


        DB::table('roles')->insert([
            'name' => 'Accountant',
            'guardName'=> 'worker',
            'status'=> 'active'
        ]);

        DB::table('roles')->insert([
            'name' => 'Storekeeper',
            'guardName'=> 'worker',
            'status'=> 'active'
        ]);


    }
}
