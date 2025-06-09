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
        'company_id',
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
    

        public function company()
    {
        return $this->belongsTo(Company::class);
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
    $seatsPerRow = min(4, $seatsPerRow ?? $this->calculateOptimalSeatsPerRow());
    $minLastRowSeats = 5;

    $regularRows = floor(($this->seatNum - $minLastRowSeats) / $seatsPerRow);
    $remainingSeats = $this->seatNum - ($regularRows * $seatsPerRow);


    if ($remainingSeats > 0 && $remainingSeats < $minLastRowSeats) {
        $regularRows--;
        $remainingSeats = $this->seatNum - ($regularRows * $seatsPerRow);
    }

    $totalRows = $regularRows + ($remainingSeats > 0 ? 1 : 0);
    $seatMap = [];
    $seatCounter = 1;

    for ($row = 1; $row <= $totalRows; $row++) {
        $currentRowSeats = ($row == $totalRows) ? max($remainingSeats, $minLastRowSeats) : $seatsPerRow;

        for ($col = 1; $col <= $currentRowSeats; $col++) {
            if ($seatCounter > $this->seatNum) {
                break;
            }

            $seatNumber = $this->generateSeatNumber($row, $col);
            $seatType = $this->determineSeatType($row, $col, $totalRows, $currentRowSeats);

            $seatMap[] = [
                'seatNumber' => $seatNumber,
                'type' => $seatType,
                'status' => 'available',
                'row' => $row,
                'column' => $col,
                'position' => $this->determineSeatPosition($col, $currentRowSeats)
            ];

            $seatCounter++;
        }
    }

    $this->seatMap = $seatMap;
    $this->save();
}


protected function calculateOptimalSeatsPerRow()
{
    $maxRegularSeats = 4;
    $minLastRowSeats = 5;

    if ($this->seatNum <= 12) {
        return min(3, $maxRegularSeats);
    } elseif ($this->seatNum <= 24) {
        return $maxRegularSeats;
    } else {
        $regularRows = floor(($this->seatNum - $minLastRowSeats) / $maxRegularSeats);
        $remainingSeats = $this->seatNum - ($regularRows * $maxRegularSeats);


        if ($remainingSeats > 0 && $remainingSeats < $minLastRowSeats) {
            $regularRows--;
            $remainingSeats = $this->seatNum - ($regularRows * $maxRegularSeats);
        }

        return $maxRegularSeats;
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
    $seatsPerRow = min(4, $seatsPerRow);


    if ($row == $totalRows) {
        return 'rearCouch';
    }

    if ($col == 1 || $col == $seatsPerRow) {
        return 'window';
    }


    return 'aisle';
}

protected function determineSeatPosition($col, $seatsPerRow)
{
    $seatsPerRow = min(4, $seatsPerRow);

    if ($seatsPerRow == 1) {
        return 'center';
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
