<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelInvoicePilgrim extends Model
{
     use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'bus_invoice_id',
        'pilgrim_id',
        'creationDate',
        'creationDateHijri',
        'changed_data'
        ];
        public $timestamps = true;

    protected $casts = [
    'changed_data' => 'array',
];

}
