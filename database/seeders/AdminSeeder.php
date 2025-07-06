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
            'name'=> 'Super Admin',
            'email' => 'super@mail.com',
            'password' => Hash::make('super@654'),
            'role_id' => '1',
            'status' => 'active',
        ]);



    }
}
