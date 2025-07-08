<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainInvoice extends Model
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
        'description',
        'seatsCount',
        'busSubtotal',
        'ihramSuppliesCount',
        'ihramSubtotal',
        'subtotal',
        'discount',
        'totalAfterDiscount',
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

public function hotels()
{
    return $this->belongsToMany(Hotel::class, 'main_invoice_hotels')
                ->withPivot([
        'checkInDate',
        'checkInDateHijri',
        'checkOutDate',
        'checkOutDateHijri',
        'numBed',
        'numRoom',
        'bookingSource',
        'roomNum',
        'need',
        'sleep',
        'numDay',
        'hotelSubtotal',
        // 'creationDate',
        // 'creationDateHijri',
        // 'changed_data'
                ]);
}


    public function pilgrims()
{
    return $this->belongsToMany(Pilgrim::class, 'main_invoice_pilgrims')
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

public function ihramSupplies()
{
    return $this->belongsToMany(IhramSupply::class, 'main_invoice_supplies')
                ->withPivot('quantity', 'price', 'total','creationDate',
            'creationDateHijri', 'changed_data');
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
    'totalAfterDiscount'=>'decimal:2',
    'tax' => 'decimal:2',
    'total' => 'decimal:2',
    'paidAmount' => 'decimal:2',
    'bedPrice' => 'decimal:2',
    'roomPrice' => 'decimal:2',
    'seatPrice' => 'decimal:2',
];

protected $attributes = [
    'invoiceStatus' => 'pending',
];

public function updateHotelRooms($roomNumber, $action = 'occupy')
{
    $updated = false;

    foreach ($this->hotels as $hotel) {
        $currentRooms = $hotel->roomNum ?? [];

        if ($action === 'occupy') {
            if (($key = array_search($roomNumber, $currentRooms)) !== false) {
                unset($currentRooms[$key]);
                $hotel->roomNum = array_values($currentRooms);
                $hotel->save();
                $updated = true;
            }
        } else {
            if (!in_array($roomNumber, $currentRooms)) {
                $currentRooms[] = $roomNumber;
                sort($currentRooms);
                $hotel->roomNum = $currentRooms;
                $hotel->save();
                $updated = true;
            }
        }
    }

    return $updated;
}



public function calculateTotals(): void
{
    $this->busSubtotal = $this->calculateBusTotal();
    $this->ihramSubtotal = $this->calculateIhramTotal();
    $hotelTotal = $this->calculateHotelTotal();

    $this->subtotal = $this->busSubtotal + $this->ihramSubtotal + $hotelTotal;

    $discount = $this->discount ?? 0;
    $this->totalAfterDiscount = max($this->subtotal - $discount, 0);

    $taxRate = $this->tax ?? 0;
    $taxAmount = round($this->totalAfterDiscount * ($taxRate / 100), 2);

    $this->total = round($this->totalAfterDiscount + $taxAmount, 2);
}

// protected function calculateBusTotal(): float
// {
//     $seatPrice = $this->busTrip->bus->seatPrice ?? 0;

//     $totalSeats = $this->pilgrims->sum(function ($pilgrim) {
//         return count(explode(',', $pilgrim->pivot->seatNumber));
//     });

//     return $seatPrice * $totalSeats;
// }

public function calculateSeatsCount(): int
{
    // لو العلاقة مش محمّلة، نحمّلها علشان نقدر نحسب
    if (!$this->relationLoaded('pilgrims')) {
        $this->load('pilgrims');
    }

    return $this->pilgrims->sum(function ($pilgrim) {
        $seatNumbers = $pilgrim->pivot->seatNumber ?? '';

        if (empty($seatNumbers)) {
            return 0;
        }

        return count(array_filter(explode(',', $seatNumbers)));
    });
}


public function updateSeatsCount(): void
{
    $count = $this->calculateSeatsCount();
    $this->seatsCount = $count ?? 0; // تأكد إنها مش null
    $this->save();
}


protected function calculateBusTotal(): float
{
    $this->updateSeatsCount(); // تحدث القيمة وتحفظها
    $seatPrice = $this->busTrip->bus->seatPrice ?? 0;

    return $seatPrice * $this->seatsCount;
}


protected function calculateIhramTotal(): float
{
    return $this->ihramSupplies->sum(function ($supply) {
        return $supply->pivot->total ?? 0;
    });
}



protected function calculateHotelTotal(): float
{
    return $this->hotels->sum(function ($hotel) {
        $bedPrice = $hotel->bedPrice ?? 0;
        $roomPrice = $hotel->sellingPrice ?? 0;
        $numDays = $hotel->pivot->numDay ?? 1;
        $numRooms = $hotel->pivot->numRoom ?? 1;
        $numBeds = $hotel->pivot->numBed ?? $this->pilgrimsCount ?? $this->pilgrims()->count();

        if ($hotel->pivot->sleep === 'room') {
            return $roomPrice * $numDays * $numRooms;
        }

        return $bedPrice * $numBeds * $numDays;
    });
}

public function calculateHotelTotalForPivot(Hotel $hotel, array $hotelData): float
{
    $bedPrice = $hotel->bedPrice ?? 0;
    $roomPrice = $hotel->sellingPrice ?? 0;
    $numDays = $hotelData['numDay'] ?? 1;
    $numRooms = $hotelData['numRoom'] ?? 1;
    $numBeds = $hotelData['numBed'] ?? $this->pilgrimsCount ?? $this->pilgrims()->count();

    if (($hotelData['sleep'] ?? null) === 'room') {
        return $roomPrice * $numDays * $numRooms;
    }

    return $bedPrice * $numBeds * $numDays;
}


public function updateIhramSuppliesCount()
{
    $this->ihramSuppliesCount = $this->ihramSupplies()->count();
    $this->save();
}


public function getIhramSuppliesCountAttribute()
{
    return $this->attributes['ihramSuppliesCount'] ?? 0;
}

protected static function booted()
{
    static::creating(function ($invoice) {
        $invoice->invoiceNumber = $invoice->generateInvoiceNumber();
    });

    static::created(function ($invoice) {
        $invoice->load('ihramSupplies');
        $invoice->updateIhramSuppliesCount();
            //  $invoice->calculateSeatsCount();
    });

    //   static::updated(function ($invoice) {
    //     if ($invoice->isDirty('pilgrimsCount')) {
    //         // $invoice->calculateSeatsCount();
    //     }
    // });
}


}
