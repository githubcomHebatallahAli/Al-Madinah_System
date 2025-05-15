<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('stores')->insert([
            'admin_id' => 1, // بدون مشرف حالياً
            'branch_id' => 1,
            'name' => 'Store Alexandria 1',
            'address' => 'شارع جمال عبد الناصر - سيدي بشر',
            'productsCount' => 0,
            'workersCount' => 0,
            'creationDate' => Carbon::now(),
            'creationDateHijri' => '1446-10-06',
            'status' => 'active',
        ]);
    }
}
