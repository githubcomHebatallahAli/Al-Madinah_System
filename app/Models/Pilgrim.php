<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pilgrim extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'name',
        'phoNum',
        'idNum',
        'nationality',
        'description',
        'gender'
    ];

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

    // public function busInvoices()
    // {
    //     return $this->belongsToMany(busInvoice::class)
    //         ->withPivot(['seatNumber', 'seatPrice', 'status', 'status_reason'])
    //         ->withTimestamps();
    // }

        public function busInvoices()
{
    return $this->belongsToMany(BusInvoice::class, 'bus_invoice_pilgrims')
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


}
