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
        'trip_id',
        'bus_invoice_id',
        'main_pilgrim_id',
        'hotel_id',
        'payment_method_type_id',
        'pilgrimsCount',
        'checkInDate',
        'checkInDateHijri',
        'checkOutDate',
        'checkOutDateHijri',
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
        'incomplete_pilgrims',
        ];

            public function mainPilgrim()
{
    return $this->belongsTo(Pilgrim::class, 'main_pilgrim_id');
}

        public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

        public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }


       public function busInvoice()
    {
        return $this->belongsTo(BusInvoice::class);
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

    public function pilgrims()
{
    return $this->belongsToMany(Pilgrim::class, 'hotel_invoice_pilgrims')
        ->withPivot([
            'type',
            'creationDate',
            'creationDateHijri',
            'changed_data',
        ]);
}

public function PilgrimsCount(): void
{
    $this->pilgrimsCount = $this->pilgrims()->count();
    $this->save();
}

public function calculateTotal(): void
{
    if (!isset($this->pilgrimsCount)) {
        $this->PilgrimsCount();
    }

    $bedPrice = $this->hotel->bedPrice ?? 0;
    $roomPrice = $this->hotel->roomPrice ?? 0;
    $numDays = $this->numDay ?? 1;


    if ($this->sleep === 'room') {

        $this->subtotal = $roomPrice * $numDays;
    } else {

        $this->subtotal = $bedPrice * $this->pilgrimsCount * $numDays;
    }


    $this->total = $this->subtotal
                  - ($this->discount ?? 0)
                  + ($this->tax ?? 0);

    $this->save();
}



    protected $casts = [
    'changed_data' => 'array',
    'incomplete_pilgrims'=>'array',
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'tax' => 'decimal:2',
    'total' => 'decimal:2',
    'paidAmount' => 'decimal:2',
    'bedPrice' => 'decimal:2',
    'roomPrice' => 'decimal:2',
];

protected $attributes = [
    'invoiceStatus' => 'pending',
    'paymentStatus' => 'pending'
];
}
