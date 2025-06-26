<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class BusInvoice extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'invoiceNumber',
        'main_pilgrim_id',
        'bus_trip_id',
        'campaign_id',
        'office_id',
        'group_id',
        'worker_id',
        'payment_method_type_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paidAmount',
        'pilgrimsCount',
        'invoiceStatus',
        'reason',
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



    public function busTrip()
{
    return $this->belongsTo(BusTrip::class, 'bus_trip_id');
}

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function paymentMethodType()
    {
        return $this->belongsTo(PaymentMethodType::class);
    }


           public function hotelInvoices()
    {
        return $this->hasMany(HotelInvoice::class, 'bus_invoice_id');
    }


    public function pilgrims()
{
    return $this->belongsToMany(Pilgrim::class, 'bus_invoice_pilgrims')
        ->withPivot([
            'seatNumber',
            'status',
            'creationDate',
            'creationDateHijri',
            'changed_data',
             'type',
             'position',
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

    $seatPrice = $this->busTrip->bus->seatPrice ?? 0;

    $this->subtotal = $seatPrice * $this->pilgrimsCount;
    $this->total = $this->subtotal
                  - ($this->discount ?? 0)
                  + ($this->tax ?? 0);

    // $this->save();
}



      protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoiceNumber = $invoice->generateInvoiceNumber();
        });


    }

    public function generateInvoiceNumber()
{
    try {
        $lastNumber = static::max('id') ?? 0;
        $nextNumber = $lastNumber + 1;

        $hijriDate = $this->extractHijriDateParts($this->getHijriDate());

        return sprintf(
            'BUS-%04d/%s/%s/%s',
            $nextNumber,
            $hijriDate['year'],
            $hijriDate['month'],
            $hijriDate['day']
        );

    } catch (\Exception $e) {
        $lastNumber = static::max('id') ?? 0;
        return sprintf(
            'BUS-%04d/%s',
            $lastNumber + 1,
            now()->format('Y-m-d')
        );
    }
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
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'tax' => 'decimal:2',
    'total' => 'decimal:2',
    'paidAmount' => 'decimal:2',
    'seatPrice' => 'decimal:2',
];

protected $attributes = [
    'invoiceStatus' => 'pending',
];

}
