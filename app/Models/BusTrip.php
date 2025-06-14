<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusTrip extends Model
{
      use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'trip_id',
        'bus_id',
        'bus_driver_id',
        'travelDate',
        'travelDateHijri',
        'seatMap',
        'status',
        'changed_data',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'creationDate',
        'creationDateHijri',

        ];

             public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

        public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

       public function busDriver()
    {
        return $this->belongsTo(BusDriver::class);
    }

       public function busInvoices()
    {
        return $this->hasMany(BusInvoice::class);
    }

       public function hotelInvoices()
    {
        return $this->hasMany(HotelInvoice::class);
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
    'cancelledSeats',
];

    protected $casts = [
    'changed_data' => 'array',
    'seatMap' => 'array',
];

    public function getBookedSeatsAttribute(): int
    {
        if (!is_array($this->seatMap)) {
            return 0;
        }

        return collect($this->seatMap)
            ->filter(fn ($seat) => isset($seat['status']) && $seat['status'] === 'booked')
            ->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        if (!is_array($this->seatMap)) {
            return 0;
        }

        return collect($this->seatMap)
            ->filter(fn ($seat) => isset($seat['status']) && $seat['status'] === 'available')
            ->count();
    }

    public function getCancelledSeatsAttribute(): int
{
    if (!is_array($this->seatMap)) {
        return 0;
    }

    return collect($this->seatMap)
        ->filter(fn ($seat) => isset($seat['status']) && $seat['status'] === 'cancelled')
        ->count();
}

}
