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
        // عندما يتم إضافة أو تعديل الفرع، نقوم بتحديث عدد الفروع في المدينة المرتبطة
        if ($branch->city) {
            $branch->city->update([
                'branchesCount' => $branch->city->branches()->count()
            ]);
        }
    });

    static::updating(function ($branch) {
        // نخزن المدينة القديمة إذا كان سيتم تغيير city_id فقط
        if ($branch->isDirty('city_id')) {
            // نستخدم old_city_id كمؤقت فقط في الذاكرة
            $branch->setAttribute('old_city_id', $branch->getOriginal('city_id'));
        }
    });

    static::updated(function ($branch) {
        // إذا كان هناك تغيير في city_id، نقوم بتحديث المدينة القديمة والجديدة
        if (isset($branch->old_city_id) && $branch->old_city_id != $branch->city_id) {
            // تحديث المدينة القديمة
            $oldCity = \App\Models\City::find($branch->old_city_id);
            if ($oldCity) {
                $oldCity->update([
                    'branchesCount' => $oldCity->branches()->count()
                ]);
            }

            // تحديث المدينة الجديدة
            if ($branch->city) {
                $branch->city->update([
                    'branchesCount' => $branch->city->branches()->count()
                ]);
            }
        }
    });
}









}
