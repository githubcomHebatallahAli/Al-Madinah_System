<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelInvoice extends Model
{
      use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'bus_id',
        'bus_driver_id',
        'main_pilgrim_id',
        'hotel_id',
        'payment_method_type_id',
        'pilgrimsCount',
        'residenceDate',
        'residenceDate',
        'bookingSource',
        'roomNum',
        'need',
        'sleep',
        'numDay',
        'subtotal',
        'discount',
        'tax',
        'paidAmount',
        'total',
        'description',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        ];

            public function mainPilgrim()
{
    return $this->belongsTo(Pilgrim::class, 'main_pilgrim_id');
}

        public function bus()
    {
        return $this->belongsTo(Bus::class);
    }


       public function busDriver()
    {
        return $this->belongsTo(BusDriver::class);
    }

        public function paymentMethodType()
    {
        return $this->belongsTo(PaymentMethodType::class);
    }


    public function pilgrims()
{
    return $this->belongsToMany(Pilgrim::class, 'hotel_invoice_pilgrims');

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
