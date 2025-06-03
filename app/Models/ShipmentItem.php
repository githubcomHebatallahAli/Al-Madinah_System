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
        'changed_data'
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
];


}
