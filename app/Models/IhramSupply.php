<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IhramSupply extends Model
{
        use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'service_id',
        'company_id',
        'store_id',
        'name',
        'size',
        'description',
        'quantity',
        'sellingPrice',
        'purchesPrice',
        'profit',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function service()
    {
        return $this->belongsTo(Service::class);
    }

        public function company()
    {
        return $this->belongsTo(Company::class);
    }

        public function store()
    {
        return $this->belongsTo(Store::class);
    }

        public function shipmentItems()
{
    return $this->morphMany(ShipmentItem::class, 'item');
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
        static::created(function ($ihramSupplies) {
            $ihramSupplies->store->increment('ihramSuppliesCount');
        });

        static::updated(function ($ihramSupplies) {
            if ($ihramSupplies->wasChanged('store_id')) {
                Store::find($ihramSupplies->getOriginal('store_id'))->decrement('ihramSuppliesCount');
                $ihramSupplies->store->increment('ihramSuppliesCount');
            }
        });
}
}
