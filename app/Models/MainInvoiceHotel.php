<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainInvoiceHotel extends Model
{
        use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'main_invoice_id',
        'hotel_id',
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
        'numBed',
        'numRoom',
        'hotelSubtotal',
        // 'creationDate',
        // 'creationDateHijri',
        // 'changed_data'
        ];

              protected $casts = [
        'changed_data' => 'array',
    ];
}
