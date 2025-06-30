<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainInvoiceSupply extends Model
{
        use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'main_invoice_id',
        'ihram_supply_id',
        'quantity',
        'price',
        'creationDate',
        'creationDateHijri',
        'changed_data'
        ];

        public $timestamps = true;

    protected $casts = [
    'changed_data' => 'array',
];
}
