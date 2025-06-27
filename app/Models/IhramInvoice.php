<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IhramInvoice extends Model
{
       use HasFactory, TracksChangesTrait,HijriDateTrait;
        protected $fillable = [
        'bus_invoice_id',
        'payment_method_type_id',
        'main_pilgrim_id',
        'ihramSuppliesCount',
        'subtotal',
        'discount',
        'tax',
        'paidAmount',
        'total',
        'description',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        ];

        public function mainPilgrim()
{
    return $this->belongsTo(Pilgrim::class, 'main_pilgrim_id');
}

      public function busInvoice()
    {
        return $this->belongsTo(BusInvoice::class);
    }

        public function paymentMethodType()
    {
        return $this->belongsTo(PaymentMethodType::class);
    }



    public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}

    public function pilgrims()
{
    return $this->belongsToMany(Pilgrim::class, 'ihram_invoice_pilgrims')
        ->withPivot([
            'creationDate',
            'creationDateHijri',
            'changed_data',
        ]);
}

public function ihramSupplies()
{
    return $this->belongsToMany(IhramSupply::class, 'ihram_invoice_supplies')
                ->withPivot('quantity', 'price', 'total','creationDate',
            'creationDateHijri', 'changed_data');
}

    protected $casts = [
    'changed_data' => 'array',
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'tax' => 'decimal:2',
    'total' => 'decimal:2',
    'paidAmount' => 'decimal:2',

];

protected $attributes = [
    'invoiceStatus' => 'pending',
];


    protected static function booted()
{

    static::created(function ($ihramInvoice) {
        $ihramInvoice->load('ihramSupplies');
        $ihramInvoice->updateIhramSuppliesCount();
    });


}

public function calculateTotalPrice()
{
    $total = 0;

    foreach ($this->ihramSupplies as $ihramSupply) {
        $total += $ihramSupply->pivot->total;
    }

    return $total;
}

public function calculateTotals(): void
{
    // $this->loadMissing('ihramSupplies');
    $this->subtotal = $this->calculateTotalPrice();
    $discount = $this->discount ?? 0;
    $taxRate = $this->tax ?? 0;

    $taxAmount = ($this->subtotal - $discount) * ($taxRate / 100);
    $this->total = $this->subtotal - $discount + $taxAmount;

    // $this->save();
}


public function updateIhramSuppliesCount()
{
    $this->ihramSuppliesCount = $this->ihramSupplies()->count();
    $this->save();
}



public function getIhramSuppliesCountAttribute()
{
    return $this->attributes['ihramSuppliesCount'] ?? 0;
}

}
