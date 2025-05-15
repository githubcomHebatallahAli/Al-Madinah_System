<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('worker_logins')->insert([
            'worker_id' => '1',
            'role_id' => '2',
            'email' => 'branchManager@mail.com',
            'password' => Hash::make('branchManager@234'),

        ]);
         DB::table('worker_logins')->insert([
            'worker_id' => '2',
            'store_id' => null,
            'role_id' => '3',
            'email' => 'delegate@mail.com',
            'password' => Hash::make('delegate@345'),
        ]);
         DB::table('worker_logins')->insert([
            'title_id' => '3',
            'store_id' => null,
            'role_id' => '4',
            'email' => 'supervisor@mail.com',
            'password' => Hash::make('supervisor@456'),
        ]);

         DB::table('worker_logins')->insert([
            'worker_id' => '4',
            'store_id' => null,
            'role_id' => '5',
            'email' => 'accountant@mail.com',
            'password' => Hash::make('accountant@567'),
        ]);

         DB::table('worker_logins')->insert([
            'worker_id' => '5',
            'store_id' => '1',
            'role_id' => '6',
            'email' => 'storekeeper@mail.com',
            'password' => Hash::make('storekeeper@678'),
        ]);
    }
}
