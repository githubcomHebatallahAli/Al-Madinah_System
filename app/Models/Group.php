<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'campaign_id',
        'groupNum',
        'numBus',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

//             public function admin()
// {
//     return $this->belongsTo(Admin::class, 'admin_id');
// }

public function creator()
{
    return $this->morphTo('added_by');
}



    protected $casts = [
    'changed_data' => 'array',
];


}
