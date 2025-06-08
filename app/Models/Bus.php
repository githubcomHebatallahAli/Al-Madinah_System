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

    if ($seatsPerRow === null) {
        $seatsPerRow = $this->calculateOptimalSeatsPerRow();
    }

    $rows = ceil($this->seatNum / $seatsPerRow);
    $seatMap = [];
    $seatCounter = 1;

    for ($row = 1; $row <= $rows; $row++) {
        for ($col = 1; $col <= $seatsPerRow; $col++) {

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


protected function generateSeatNumber($row, $col)
{
    $rowLetter = chr(64 + $row);

    if ($row > 26) {
        $rowLetter = 'R' . ($row - 26);
    }

    return $rowLetter . $col;
}


protected function determineSeatType($row, $col, $totalRows, $seatsPerRow)
{

    if ($row == $totalRows) {
        return 'rearCouch';
    }

    $seatsPerRow = min($seatsPerRow, 4);


    if ($col == 1 || $col == $seatsPerRow) {
        return 'window';
    }

    if ($col == 2 || $col == 3) {
        return 'aisle';
    }

    return 'aisle';
}

protected function determineSeatPosition($col, $seatsPerRow)
{
    $seatsPerRow = max(2, min($seatsPerRow, 4));

    if ($seatsPerRow == 3) {
        return ($col == 1) ? 'left' : (($col == 2) ? 'center' : 'right');
    }

    return ($col <= $seatsPerRow / 2) ? 'left' : 'right';
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
