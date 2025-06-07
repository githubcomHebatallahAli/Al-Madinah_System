<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bus extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'service_id',
        'busNum',
        'busModel',
        'plateNum',
        'seatNum',
        'seatMap',
        'quantity',
        'sellingPrice',
        'purchesPrice',
        'profit',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

            public function service()
    {
        return $this->belongsTo(Service::class);
    }

        public function drivers()
    {
        return $this->hasMany(BusDriver::class);
    }

    public function shipmentItems()
{
    return $this->morphMany(ShipmentItem::class, 'item');
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
    'seatMap' => 'array'
];


public function availableSeats()
{
    if (empty($this->seatMap)) {
        $this->generateDefaultSeatMap();
    }

    return array_filter($this->seatMap, function ($seat) {
        return isset($seat['status']) && $seat['status'] === 'available';
    });
}


public function generateDefaultSeatMap($seatsPerRow = null)
{
    // إذا لم يتم تحديد مقاعد لكل صف، نحسب توزيعاً معقولاً
    if ($seatsPerRow === null) {
        $seatsPerRow = $this->calculateOptimalSeatsPerRow();
    }

    $rows = ceil($this->seatNum / $seatsPerRow);
    $seatMap = [];
    $seatCounter = 1;

    for ($row = 1; $row <= $rows; $row++) {
        for ($col = 1; $col <= $seatsPerRow; $col++) {
            // تخطي المقاعد الزائدة إذا كان العدد الإجمالي ليس مضاعفاً مثاليًا
            if ($seatCounter > $this->seatNum) {
                break;
            }

            $seatNumber = $this->generateSeatNumber($row, $col);
            $seatType = $this->determineSeatType($row, $col, $rows, $seatsPerRow);

            $seatMap[] = [
                'seatNumber' => $seatNumber,
                'type' => $seatType,
                'status' => 'available',
                'row' => $row,
                'column' => $col,
                'position' => $this->determineSeatPosition($col, $seatsPerRow)
            ];

            $seatCounter++;
        }
    }

    $this->seatMap = $seatMap;
    $this->save();
}

/**
 * حساب العدد الأمثل للمقاعد في كل صف
 */
protected function calculateOptimalSeatsPerRow()
{
    if ($this->seatNum <= 20) {
        return 3;
    } elseif ($this->seatNum <= 40) {
        return 4;
    } else {
        return 5;
    }
}

/**
 * توليد رقم المقعد بناء على الصف والعمود
 */
protected function generateSeatNumber($row, $col)
{
    $rowLetter = chr(64 + $row); // A, B, C, etc.

    // إذا كان عدد المقاعد في الصف > 26 نستخدم نظاماً مختلفاً
    if ($row > 26) {
        $rowLetter = 'R' . ($row - 26); // R1, R2, etc.
    }

    return $rowLetter . $col;
}

/**
 * تحديد نوع المقعد
 */
protected function determineSeatType($row, $col, $totalRows, $seatsPerRow)
{
    // المقاعد الأمامية VIP
    if ($row <= 2) {
        return 'vip';
    }

    // المقاعد المجاورة للنوافذ
    if ($col == 1 || $col == $seatsPerRow) {
        return 'window';
    }

    // المقاعد القريبة من الممرات
    if ($col == ceil($seatsPerRow / 2) || $col == ceil($seatsPerRow / 2) + 1) {
        return 'aisle';
    }

    return 'regular';
}

/**
 * تحديد موقع المقعد (يسار، وسط، يمين)
 */
protected function determineSeatPosition($col, $seatsPerRow)
{
    if ($seatsPerRow <= 3) {
        return $col == 1 ? 'left' : ($col == 2 ? 'center' : 'right');
    }

    if ($col <= floor($seatsPerRow / 3)) {
        return 'left';
    } elseif ($col > ceil(2 * $seatsPerRow / 3)) {
        return 'right';
    } else {
        return 'center';
    }
}


    public function busInvoices()
    {
        return $this->hasMany(BusInvoice::class);
    }

    public function getTotalBookedSeatsAttribute()
    {
        return $this->busInvoices()->sum('bookedSeats');
    }




}
