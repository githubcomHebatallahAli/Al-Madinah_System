<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlightInvoice extends Model
{
        use HasFactory, TracksChangesTrait, HijriDateTrait;
        protected $fillable = [
        'trip_id',
        'hotel_id',
        'flight_id',
        'payment_method_type_id',
        'main_pilgrim_id',
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


        public function pilgrims()
{
    return $this->belongsToMany(Pilgrim::class, 'flight_invoice_pilgrims')
        ->withPivot([
            'creationDate',
            'creationDateHijri',
            'changed_data',
            'seatNumber'
        ]);
}

    public function PilgrimsCount(): void
{
    $this->pilgrimsCount = $this->pilgrims()->count();
    $this->save();
}

public function calculateTotal(): void
{
  $seatPrice = $this->flight->sellingPrice ?? 0;

// حساب عدد المقاعد فعلياً من الـ pivot
$totalSeats = $this->pilgrims->sum(function ($pilgrim) {
    $seats = explode(',', $pilgrim->pivot->seatNumber);
    return count($seats);
});

$this->subtotal = $seatPrice * $totalSeats;
$this->total = $this->subtotal
              - ($this->discount ?? 0)
              + ($this->tax ?? 0);

$this->save();

}

    public function mainPilgrim()
{
    return $this->belongsTo(Pilgrim::class, 'main_pilgrim_id');
}

  public function paymentMethodType()
    {
        return $this->belongsTo(PaymentMethodType::class);
    }

    public function flight()
{
    return $this->belongsTo(Flight::class);
}

public function hotel()
{
    return $this->belongsTo(Hotel::class);
}

public function trip()
{
    return $this->belongsTo(Trip::class);
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

];

protected $attributes = [
    'invoiceStatus' => 'pending',
];
}
