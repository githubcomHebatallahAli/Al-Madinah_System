<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'city_id',
        'name',
        'address',
        'creationDate',
        'creationDateHijri',
        'tripsCount',
        'storesCount',
        'workersCount',
        'officesCount',
        'status',
        'changed_data'
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

//         public function admin()
// {
//     return $this->belongsTo(Admin::class, 'admin_id');
// }

    protected $casts = [
    'changed_data' => 'array',
];



protected static function booted()
{
    static::saved(function ($branch) {
        if ($branch->wasChanged('city_id')) {
            if (!is_null($branch->getOriginal('city_id'))) {
                City::where('id', $branch->getOriginal('city_id'))
                    ->decrement('branchesCount');
            }

            if ($branch->city_id) {
                City::where('id', $branch->city_id)
                    ->increment('branchesCount');
            }
        }
        elseif ($branch->wasRecentlyCreated && $branch->city_id) {
            City::where('id', $branch->city_id)
                ->increment('branchesCount');
        }
    });
}









}
