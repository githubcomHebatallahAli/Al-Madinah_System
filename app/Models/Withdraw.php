<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdraw extends Model
{
    use HasFactory,HijriDateTrait,TracksChangesTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'withdrawnAmount',
        'remainingAmount',
        'description',
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
}
