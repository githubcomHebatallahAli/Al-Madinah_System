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
        return $this->belongsTo(BusInvoice::class, 'bus_invoice_id');
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
    $roomPrice = $this->hotel->sellingPrice ?? 0;
    $numDays = $this->numDay ?? 1;


    if ($this->sleep === 'room') {

        $this->subtotal = $roomPrice * $numDays;
    } else {

        $this->subtotal = $bedPrice * $this->pilgrimsCount * $numDays;
    }


    $this->total = $this->subtotal
                  - ($this->discount ?? 0)
                  + ($this->tax ?? 0);


}



    protected $casts = [
    'changed_data' => 'array',
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
];

    protected static function booted()
    {
        static::updating(function ($invoice) {
            // إذا تم تغيير رقم الغرفة
            if ($invoice->isDirty('roomNum')) {
                $originalRoom = $invoice->getOriginal('roomNum');
                $newRoom = $invoice->roomNum;

                // إعادة الغرفة القديمة إذا كانت مختلفة
                if ($originalRoom && $originalRoom != $newRoom) {
                    $invoice->releaseSpecificRoom($originalRoom);
                }

                // حجز الغرفة الجديدة
                if ($newRoom) {
                    $invoice->occupySpecificRoom($newRoom);
                }
            }

            // إذا تم إنهاء الإقامة بتحديث عدد الأيام
            if ($invoice->isDirty('numDay') && $invoice->numDay <= 0) {
                $invoice->releaseRoom();
            }

            // إذا تم تحديث تاريخ الخروج ليكون تاريخ قديم
            if ($invoice->isDirty('checkOutDate') && $invoice->checkOutDate <= now()) {
                $invoice->releaseRoom();
            }
        });

        static::created(function ($invoice) {
            // حجز الغرفة تلقائياً عند الإنشاء
            if ($invoice->roomNum) {
                $invoice->occupyRoom();
            }
        });
    }

    /**
     * حجز الغرفة الحالية في الفاتورة
     */
    public function occupyRoom(): bool
    {
        if (!$this->hotel || !$this->roomNum) return false;

        $hotel = $this->hotel;
        $currentRooms = $hotel->roomNum ?? [];

        if (!in_array($this->roomNum, $currentRooms)) {
            $currentRooms[] = $this->roomNum;
            sort($currentRooms);
            return $hotel->update(['roomNum' => $currentRooms]);
        }

        return true;
    }

    /**
     * إعادة الغرفة الحالية في الفاتورة
     */
    public function releaseRoom(): bool
    {
        if (!$this->hotel || !$this->roomNum) return false;

        $hotel = $this->hotel;
        $currentRooms = $hotel->roomNum ?? [];

        if (($key = array_search($this->roomNum, $currentRooms)) !== false) {
            unset($currentRooms[$key]);
            return $hotel->update(['roomNum' => array_values($currentRooms)]);
        }

        return true;
    }

    /**
     * حجز غرفة محددة (للاستخدام عند تغيير رقم الغرفة)
     */
    protected function occupySpecificRoom($roomNumber): bool
    {
        if (!$this->hotel) return false;

        $hotel = $this->hotel;
        $currentRooms = $hotel->roomNum ?? [];

        if (!in_array($roomNumber, $currentRooms)) {
            $currentRooms[] = $roomNumber;
            sort($currentRooms);
            return $hotel->update(['roomNum' => $currentRooms]);
        }

        return true;
    }

    /**
     * إعادة غرفة محددة (للاستخدام عند تغيير رقم الغرفة)
     */
    protected function releaseSpecificRoom($roomNumber): bool
    {
        if (!$this->hotel) return false;

        $hotel = $this->hotel;
        $currentRooms = $hotel->roomNum ?? [];

        if (($key = array_search($roomNumber, $currentRooms)) !== false) {
            unset($currentRooms[$key]);
            return $hotel->update(['roomNum' => array_values($currentRooms)]);
        }

        return true;
    }
}
