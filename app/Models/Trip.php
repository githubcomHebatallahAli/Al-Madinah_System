<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'branch_id',
        'name',
        'pilgrimsCount',
        'status',
        'creationDate',
        'description',
        'creationDateHijri',
        'changed_data'
    ];


        public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

//             public function admin()
// {
//     return $this->belongsTo(Admin::class, 'admin_id');
// }

public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}


      protected static function booted()
    {
        static::created(function ($trip) {
            $trip->branch->increment('tripsCount');
        });

        static::updated(function ($trip) {
            if ($trip->wasChanged('branch_id')) {
                Branch::find($trip->getOriginal('branch_id'))->decrement('tripsCount');

                $trip->branch->increment('tripsCount');
            }
        });


    }

        protected $casts = [
    'changed_data' => 'array',
];


}



