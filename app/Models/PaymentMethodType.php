<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethodType extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'payment_method_id',
        'type',
        'by'
    ];

        public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

        public function shipmentInvoices()
{
    return $this->hasMany(ShipmentInvoice::class);
}

            public function busInvoices()
    {
        return $this->hasMany(BusInvoice::class);
    }

            public function flightInvoices()
{
    return $this->hasMany(FlightInvoice::class);
}

        public function IhramInvoices()
{
    return $this->hasMany(IhramInvoice::class);
}

        public function HotelInvoices()
{
    return $this->hasMany(HotelInvoice::class);
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
