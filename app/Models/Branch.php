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

    public function admin()
{
    return $this->belongsTo(Admin::class, 'admin_id');
}

protected static function booted()
{
    static::saved(function ($branch) {
        // إذا كان هناك تغيير في المدينة
        if ($branch->wasChanged('city_id')) {
            // تقليل العدد في المدينة القديمة
            if (!is_null($branch->getOriginal('city_id'))) {
                City::where('id', $branch->getOriginal('city_id'))
                    ->decrement('branchesCount');
            }

            // زيادة العدد في المدينة الجديدة
            if ($branch->city_id) {
                City::where('id', $branch->city_id)
                    ->increment('branchesCount');
            }
        }
        // إذا كان إنشاء جديد وليس تحديث
        elseif ($branch->wasRecentlyCreated && $branch->city_id) {
            City::where('id', $branch->city_id)
                ->increment('branchesCount');
        }
    });
}









}
