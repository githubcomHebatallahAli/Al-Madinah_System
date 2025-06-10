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
        'trip_id',
        'campaign_id',
        'office_id',
        'group_id',
        'bus_id',
        'bus_driver_id',
        'worker_id',
        'payment_method_type_id',
        'travelDate',
        'travelDateHijri',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paidAmount',
        // 'bookedSeats',
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
        'seatMap',
    ];

    public function mainPilgrim()
{
    return $this->belongsTo(Pilgrim::class, 'main_pilgrim_id');
}


     public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

        public function trip()
    {
        return $this->belongsTo(Trip::class);
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



    public function busDriver()
    {
        return $this->belongsTo(BusDriver::class);
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
            'seatPrice',
            'status',
            'creationDate',
            'creationDateHijri',
            'changed_data',
             'type',
             'position',
        ])
        ->withTimestamps();
}

    public function calculateTotal(): void
{
    $this->loadMissing('pilgrims');
    $this->subtotal = $this->pilgrims->sum('pivot.seatPrice');
    $this->total = $this->subtotal - $this->discount + $this->tax;
    $this->save();
}



      protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoiceNumber = $invoice->generateInvoiceNumber();
        });

            static::updated(function ($invoice) {
        if ($invoice->bus && $invoice->travelDate) {
            $bookedSeats = $invoice->bus->getBookedSeatsForDate($invoice->travelDate);

            if ($bookedSeats >= $invoice->bus->seatNum) {
                $invoice->bus->update(['status' => 'full']);
            } else {
                $invoice->bus->update(['status' => 'available']);
            }
        }
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







    public function checkSeatAvailability($requestedSeats)
    {
        return $this->available_seats >= $requestedSeats;
    }

    public function updateSeatMapAfterBooking(): void
{
   $bookedSeats = $this->pilgrims()
    ->wherePivot('status', 'booked')
    ->pluck('seatNumber') // ✅ استبدل هذا السطر
    ->toArray();


    // احصل على نسخة seatMap الحالية
    $seatMap = $this->seatMap ?? [];

    // تحديث حالة المقاعد
    $updatedSeatMap = array_map(function ($seat) use ($bookedSeats) {
        $seatNumber = strtoupper(trim($seat['seatNumber'] ?? ''));

        if (in_array($seatNumber, $bookedSeats)) {
            $seat['status'] = 'booked';
        }

        return $seat;
    }, $seatMap);

    // حفظ التحديث
    $this->seatMap = $updatedSeatMap;
    $this->save();
}


public function getBookedSeats()
{
    return $this->pilgrims()
    ->wherePivot('status', 'booked')
    ->pluck('seatNumber') // ✅ استبدل هذا السطر
    ->toArray();

}



// app/Models/BusInvoice.php

public function getBookedSeatsAttribute(): int
{
    return collect($this->seatMap)->where('status', 'booked')->count();
}

public function getAvailableSeatsAttribute(): int
{
    return collect($this->seatMap)->where('status', 'available')->count();
}


        public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}

protected $appends = [
    'bookedSeats',
    'availableSeats',
];




    protected $casts = [
    'changed_data' => 'array',
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'tax' => 'decimal:2',
    'total' => 'decimal:2',
    'paidAmount' => 'decimal:2',
    'seatMap' => 'array',
];



}
