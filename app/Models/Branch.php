<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
        use HasFactory;
    protected $fillable = [
        'admin_id',
        'city_id',
        'name',
        'address',
        'creationDate',
        'creationDateHijri',
        'tripsCount',
        'storesCount',
        'workersCount',
        'status'
    ];

        public function titles()
    {
        return $this->hasMany(Title::class);
    }

        public function trips()
    {
        return $this->hasMany(Trip::class);
    }

        public function stores()
    {
        return $this->hasMany(Store::class);
    }

        public function offices()
    {
        return $this->hasMany(Office::class);
    }

            public function city()
    {
        return $this->belongsTo(City::class);
    }

    protected $casts = [
    'changed_data' => 'array',
];

public function workers()
{
    return $this->hasManyThrough(Worker::class, Title::class);
}

protected static function booted()
{
    static::created(function ($branch) {
        $branch->storesCount = $branch->stores()->count();
        $branch->tripsCount = $branch->trips()->count();
        $branch->workersCount = $branch->workers()->count();
        $branch->save();
    });
}
}
