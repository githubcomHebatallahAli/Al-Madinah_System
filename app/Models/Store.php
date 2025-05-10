<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'address',
        'productsCount',
        'workersCount',
        'status',
        'creationDate'
    ];

        public function worker()
    {
        return $this->hasMany(Worker::class);
    }

    //     public function product()
    // {
    //     return $this->hasMany(Product::class);
    // }
}
