<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShipmentInvoice extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'shipment_id',
        'payment_method_type_id',
        'discount',
        'totalPriceAfterDiscount',
        'description',
        'paidAmount',
        'remainingAmount',
        'invoice',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];



        public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

        public function paymentMethodType()
    {
        return $this->belongsTo(PaymentMethodType::class);
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
}
