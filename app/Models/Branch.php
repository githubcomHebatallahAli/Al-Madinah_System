<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
        use HasFactory;


    protected $fillable = [
        'city_id',
        'name',
        'address',
        'creationDate',
        'status',
        'location',
        'tripsCount',
        'storesCount',
        'titlesCount',
        'workersCount',
        'status'
    ];

            public function title()
    {
        return $this->hasMany(Title::class);
    }

            public function trip()
    {
        return $this->hasMany(Trip::class);
    }

            public function store()
    {
        return $this->hasMany(Store::class);
    }
}
