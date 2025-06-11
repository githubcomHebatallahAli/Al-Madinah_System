<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusDriver extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'bus_id',
        'name',
        'idNum',
        'phoNum',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

               public function busTrips()
    {
        return $this->hasMany(BusTrip::class);
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
];
}
