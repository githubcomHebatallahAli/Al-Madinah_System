<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShipmentItem extends Model
{
    use HasFactory, TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'shipment_id',
        'item_id',
        'item_type',
        'quantity',
        'unitPrice',
        'totalPrice',
        'rentalStart',
        'rentalEnd',
        'rentalStartHijri',
        'rentalEndHijri',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'DateTimeTripHijri',
        'DateTimeTrip',
        'seatNum',
        'class',
        'roomType',
        'roomNum'
    ];

    public function shipment()
{
    return $this->belongsTo(Shipment::class);
}

public function item()
{
    return $this->morphTo();
}

protected $hidden = ['created_at', 'updated_at'];

     protected $casts = [
    'changed_data' => 'array',
    'seatNum' => 'array',
    'roomNum'=>'array'
];


//   protected static function booted()
//     {
//         static::created(function ($item) {
//             $item->shipment->updateItemsCount();
//             $item->updateRelatedFlight();
//         });

//         static::updated(function ($item) {
//             $item->shipment->updateItemsCount();
//             $item->updateRelatedFlight();
//         });
//     }

//     public function updateRelatedFlight()
//     {
//         if ($this->item_type === Flight::class) {
//             $flight = Flight::find($this->item_id);

//             if ($flight) {
//                 $updateData = [
//                     'purchesPrice' => $this->unitPrice,
//                     'profit' => isset($flight->sellingPrice) ?
//                         $flight->sellingPrice - $this->unitPrice : null
//                 ];

//                 if (!is_null($this->class)) {
//                     $updateData['class'] = $this->class;
//                 }
//                 if (!is_null($this->seatNum)) {
//                     $updateData['seatNum'] = $this->seatNum;
//                 }
//                 if (!is_null($this->DateTimeTrip)) {
//                     $updateData['DateTimeTrip'] = $this->DateTimeTrip;
//                     $updateData['DateTimeTripHijri'] = $this->DateTimeTripHijri;
//                 }
//                 $flight->update($updateData);
//             }
//         }
//     }




    protected static function booted()
    {
        static::created(function ($item) {
            $item->shipment->updateItemsCount();
            $item->updateRelatedItem();
        });

        static::updated(function ($item) {
            $item->shipment->updateItemsCount();
            $item->updateRelatedItem();
        });
    }

    public function updateRelatedItem()
    {
        $itemModel = $this->item;

        if (!$itemModel) {
            return;
        }

        $updateData = [
            'purchesPrice' => $this->unitPrice,
            'profit' => isset($itemModel->sellingPrice) ?
                $itemModel->sellingPrice - $this->unitPrice : null
        ];

        // تحديث البيانات حسب نوع العنصر
        if ($itemModel instanceof Flight) {
            if (!is_null($this->class)) {
                $updateData['class'] = $this->class;
            }
            if (!is_null($this->seatNum)) {
                $updateData['seatNum'] = $this->seatNum;
            }
            if (!is_null($this->DateTimeTrip)) {
                $updateData['DateTimeTrip'] = $this->DateTimeTrip;
                $updateData['DateTimeTripHijri'] = $this->DateTimeTripHijri;
            }
        }
        elseif ($itemModel instanceof Bus || $itemModel instanceof Hotel) {
            if (!is_null($this->rentalStart)) {
                $updateData['rentalStart'] = $this->rentalStart;
                $updateData['rentalStartHijri'] = $this->rentalStartHijri;
            }
            if (!is_null($this->rentalEnd)) {
                $updateData['rentalEnd'] = $this->rentalEnd;
                $updateData['rentalEndHijri'] = $this->rentalEndHijri;
            }


        }

        $itemModel->update($updateData);
    }
}


