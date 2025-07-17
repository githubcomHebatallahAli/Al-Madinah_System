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
        'totalAfterDiscount',
        'tax',
        'total',
        'paidAmount',
        'seatsCount',
        'pilgrimsCount',
        'description',
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

public function calculateSeatsCount(): int
{
    if (!$this->relationLoaded('pilgrims')) {
        $this->load(['pilgrims' => function($query) {
            $query->withPivot('seatNumber');
        }]);
    }

    return $this->pilgrims->sum(function ($pilgrim) {
        $seatNumbers = $pilgrim->pivot->seatNumber ?? '';
        
        if (empty($seatNumbers)) {
            return 0;
        }

        // تقسيم المقاعد وحسابها بدقة
        $seats = explode(',', $seatNumbers);
        return count(array_filter($seats, 'trim'));
    });
}

public function updateSeatsCount(): void
{
    // فرض إعادة التحميل من الداتا بيز حتى لو كانت العلاقة محملة
    $this->unsetRelation('pilgrims'); 
    $this->load(['pilgrims' => fn ($q) => $q->withPivot('seatNumber')]);

    $this->seatsCount = $this->calculateSeatsCount();
    $this->save();
}


public function calculateTotal(): void
{
    $this->updateSeatsCount(); // تحديث عدد المقاعد أولاً
    
    $seatPrice = $this->flight->sellingPrice ?? 0;
    $this->subtotal = $seatPrice * $this->seatsCount;

    $discount = $this->discount ?? 0;
    $taxRate = $this->tax ?? 0;
    
    $this->totalAfterDiscount = $this->subtotal - $discount;
    $taxAmount = $this->totalAfterDiscount * ($taxRate / 100);
    $this->total = $this->totalAfterDiscount + $taxAmount;
}

// public function calculateTotal(): void
// {
//     $seatPrice = $this->flight->sellingPrice ?? 0;
//     $totalSeats = $this->pilgrims->sum(function ($pilgrim) {
//         $seats = explode(',', $pilgrim->pivot->seatNumber);
//         return count($seats);
//     });


//     $this->subtotal = $seatPrice * $totalSeats;

//     $discount = $this->discount ?? 0;
//     $taxRate = $this->tax ?? 0;
//     $this->totalAfterDiscount = $this->subtotal - $discount;

//     $taxAmount = $this->totalAfterDiscount * ($taxRate / 100);

//     $this->total = $this->totalAfterDiscount + $taxAmount;
// }



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
    return $this->belongsTo(Flight::class, 'flight_id');
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
