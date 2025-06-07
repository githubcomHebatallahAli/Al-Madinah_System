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
        'seatNumber',
        'seatPrice',
        'status',
        'statusReason',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];
      protected $casts = [
        'seatPrice' => 'decimal:2',
        'changed_data' => 'array',
    ];


        public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}




}
