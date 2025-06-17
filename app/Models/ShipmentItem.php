<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShipmentItem extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        // 'added_by',
        // 'added_by_type',
        // 'updated_by',
        // 'updated_by_type',
        'shipment_id',
        'item_id',
        'item_type',
        'quantity',
        'unitPrice',
        'totalPrice',
        'rentalStart',
        'rentalEnd',
        'rentalStartHijri',
        'rentalEndHijri',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'DateTimeTripHijri',
        'DateTimeTrip',
        'seatNum',
        'class'


    ];

    public function shipment()
{
    return $this->belongsTo(Shipment::class);
}

public function item()
{
    return $this->morphTo();
}

protected $hidden = ['created_at', 'updated_at'];

     protected $casts = [
    'changed_data' => 'array',
    'seatNum' => 'array',
];


 protected static function booted()
    {
      static::created(function ($item) {
        $item->shipment->updateItemsCount();
    });

    static::updated(function ($item) {
        $item->shipment->updateItemsCount();
    });


    }


}
