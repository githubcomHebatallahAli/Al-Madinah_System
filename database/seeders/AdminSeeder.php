<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admins')->insert([
            'name'=> 'Eslam',
            'email' => 'superAdmin@mail.com',
            'password' => Hash::make('superAdmin@123'),
            'role_id' => '1',
            'status' => 'active',
        ]);

        DB::table('admins')->insert([
            'name'=> 'AboEsraa',
            'email' => 'admin@mail.com',
            'password' => Hash::make('admin@234'),
            'role_id' => '2',
            'status' => 'active',
        ]);

        // DB::table('admins')->insert([
        //     'name' => 'Employee',
        //     'email' => 'employee@mail.com',
        //     'password' => Hash::make('employee@543'),
        //     'phoNum'=>'0111334455',
        //     'role_id' => '3',
        //     'status' => 'active',
        //     'type' => 'station',
        // ]);

    }
}
