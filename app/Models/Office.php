<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Office extends Model
{
    use HasFactory,TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'branch_id',
        'name',
        'address',
        'phoNum1',
        'phoNum2',
        'creationDate',
        "status",
        'campaignsCount',
        'creationDateHijri',
        'changed_data'
    ];

         public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

            public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


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
        static::created(function ($office) {
            $office->branch->increment('officesCount');
        });

        static::updated(function ($office) {
            if ($office->wasChanged('branch_id')) {
                Branch::find($office->getOriginal('branch_id'))->decrement('officesCount');
                $office->branch->increment('officesCount');
            }
        });

    }

    protected $casts = [
    'changed_data' => 'array',
];
}

