<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusInvoicePilgrim extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'bus_invoice_id',
        'pilgrim_id',
        'seatNumber',
        'status',
        'type',
        'position',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

      protected $casts = [
        'changed_data' => 'array',
    ];


public $timestamps = true;





}
