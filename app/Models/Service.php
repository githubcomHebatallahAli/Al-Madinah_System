<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory,HijriDateTrait,TracksChangesTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'branch_id',
        'name',
        'status',
        'description',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];


        public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

        public function companies()
    {
        return $this->hasMany(Company::class);
    }
    
        public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}

     protected $casts = [
    'changed_data' => 'array',
];

      protected static function booted()
    {
        static::created(function ($service) {
            $service->branch->increment('servicesCount');
        });

        static::updated(function ($service) {
            if ($service->wasChanged('branch_id')) {
                Branch::find($service->getOriginal('branch_id'))->decrement('servicesCount');

                $service->branch->increment('servicesCount');
            }
        });

    }



}
