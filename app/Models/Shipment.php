<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipment extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'supplier_id',
        'company_id',
        'service_id',
        'shipmentItemsCount',
        'totalPrice',
        'description',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

        public function company()
    {
        return $this->belongsTo(Company::class);
    }

        public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function items()
{
    return $this->hasMany(ShipmentItem::class);
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
    static::created(function ($shipment) {
        $shipment->updateItemsCount();
    });
}

public function updateItemsCount()
{
    $this->shipmentItemsCount = $this->items()->count();
    $this->saveQuietly();
}

public function getShipmentItemsCountAttribute($value)
{
    return $value ?? 0;
}
}
