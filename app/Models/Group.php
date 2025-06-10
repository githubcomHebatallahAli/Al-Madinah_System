<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory,TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
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

        public function busInvoices()
    {
        return $this->hasMany(BusInvoice::class);
    }



public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}

        protected static function booted()
    {
        static::created(function ($group) {
            $group->campaign->increment('groupsCount');
        });

        static::updated(function ($group) {
            if ($group->wasChanged('campaign_id')) {
                Campaign::find($group->getOriginal('campaign_id'))->decrement('groupsCount');
                $group->campaign->increment('groupsCount');
            }
        });

    }



    protected $casts = [
    'changed_data' => 'array',
];


}
