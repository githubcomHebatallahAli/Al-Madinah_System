<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'branch_id',
        'name',
        'address',
        'productsCount',
        'workersCount',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function workers()
    {
        return $this->hasMany(Worker::class);
    }

    //     public function products()
    // {
    //     return $this->hasMany(Product::class);
    // }

//     public function admin()
// {
//     return $this->belongsTo(Admin::class, 'admin_id');
// }

public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}


        protected static function booted()
    {
        static::created(function ($store) {
            $store->branch->increment('storesCount');
        });

        static::updated(function ($store) {
            if ($store->wasChanged('branch_id')) {
                Branch::find($store->getOriginal('branch_id'))->decrement('storesCount');
                $store->branch->increment('storesCount');
            }
        });

        // إذا كان لديك حذف
        static::deleted(function ($store) {
            $store->branch->decrement('storesCount');
        });
    }


    protected $casts = [
    'changed_data' => 'array',
];

}

