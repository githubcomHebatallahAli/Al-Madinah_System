<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'company_id',
        'name',
        'communication',
        'description',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function company()
    {
        return $this->belongsTo(Company::class);
    }

        public function shipments()
    {
        return $this->hasMany(Shipment::class);
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
