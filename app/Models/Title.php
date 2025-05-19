<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Title extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'branch_id',
        'name',
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
    return $this->morphTo('added_by');
}




        protected static function booted()
    {
        static::created(function ($title) {
            $title->branch->increment('workersCount', $title->workers()->count());
        });

        static::updated(function ($title) {
            if ($title->wasChanged('branch_id')) {
                // حساب عدد العمال في العنوان قبل النقل
                $workersCount = $title->workers()->count();

                Branch::find($title->getOriginal('branch_id'))->decrement('workersCount', $workersCount);
                $title->branch->increment('workersCount', $workersCount);
            }
        });

    }

    protected $casts = [
    'changed_data' => 'array',
];


}
