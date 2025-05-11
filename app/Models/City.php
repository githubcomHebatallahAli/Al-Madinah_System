<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
     use HasFactory;
    protected $fillable = [
        'admin_id',
        'name',
        'creationDate',
        'creationDateHijri',
        'status',
        'branchesCount',

    ];

        public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function admin()
{
    return $this->belongsTo(Admin::class, 'admin_id');
}




// protected static function booted()
// {
//     static::created(function ($city) {
//         $city->branchesCount = $city->branches()->count();
//         $city->save();
//     });



//     static::deleted(function ($city) {
//         if (method_exists($city, 'isForceDeleting') && $city->isForceDeleting()) {
//             return;
//         }

//         if (!$city->trashed()) {
//             $city->branchesCount = $city->branches()->count();
//             $city->save();
//         }
//     });

// }

// public function getbranchesCountAttribute()
//         {
//             return $this->branches()->count();
//         }


protected static function booted()
{
    static::created(function ($city) {
        $city->branchesCount = $city->branches()->count();
        $city->save();
    });
}



protected $casts = [
    'changed_data' => 'array',
];

}
