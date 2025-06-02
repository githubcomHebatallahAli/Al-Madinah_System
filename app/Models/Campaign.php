<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory,TracksChangesTrait,HijriDateTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'office_id',
        'name',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

//   public function workers()
// {
//     return $this->belongsToMany(Worker::class, 'campaign_workers');
// }


public function workers()
{
    return $this->belongsToMany(Worker::class, 'campaign_workers')
                ->withPivot([ 'added_by', 'added_by_type', 'updated_by', 'updated_by_type', 'creationDate', 'creationDateHijri']);
                // ->using(CampaignWorker::class);
}


  public function office()
{
    return $this->belongsTo(Office::class);
}

  public function groups()
{
    return $this->hasMany(Group::class);
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

        protected static function booted()
    {
        static::created(function ($campaign) {
            $campaign->office->increment('campaignsCount');
        });

        static::updated(function ($campaign) {
            if ($campaign->wasChanged('office_id')) {
                Office::find($campaign->getOriginal('office_id'))->decrement('campaignsCount');
                $campaign->office->increment('campaignsCount');
            }
        });

    }

}
