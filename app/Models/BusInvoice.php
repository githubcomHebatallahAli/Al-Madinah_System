<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

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
        'seatPrice',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paidAmount',
        'pilgrimsCount',
        'invoiceStatus',
        'reason',
        'paymentStatus',
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
        return $this->belongsTo(BusTrip::class);
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
        ])
        ->withTimestamps();
}

public function PilgrimsCount(): void
{
    $this->loadCount('pilgrims');
    $this->pilgrimsCount = $this->pilgrimsCount;
    $this->save();
}


public function calculateTotal(): void
{

    if (is_null($this->pilgrimsCount)) {
        $this->PilgrimsCount();
    }

    $this->subtotal = $this->seatPrice * $this->pilgrimsCount;
    $discount = $this->discount ?? 0;
    $tax = $this->tax ?? 0;

    $this->total = $this->subtotal - $discount + $tax;

    $this->save();
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
    'paymentStatus' => 'pending'
];

}
