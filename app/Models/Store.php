<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'branch_id',
        'name',
        'address',
        'productsCount',
        'workersCount',
        'status',
        'creationDate'
    ];

        public function workers()
    {
        return $this->hasMany(Worker::class);
    }

    //     public function products()
    // {
    //     return $this->hasMany(Product::class);
    // }
}
