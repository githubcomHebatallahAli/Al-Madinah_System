<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory,TracksChangesTrait,HijriDateTrait;
    const storageFolder= 'Workers';
    protected $fillable = [
        'title_id',
        'store_id',
        'name',
        'idNum',
        'personPhoNum',
        'branchPhoNum',
        'salary',
        'cv',
        'status',
        'dashboardAccess',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
    ];



    public function workerLogin()
{
    return $this->hasOne(WorkerLogin::class);
}


   protected $casts = [
        'salary' => 'decimal:2',
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


    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }


    public function campaigns()
{
    return $this->belongsToMany(Campaign::class, 'campaign_workers')
                ->withPivot([ 'added_by', 'added_by_type', 'updated_by', 'updated_by_type', 'creationDate', 'creationDateHijri']);
}



    public function branch()
    {
        return $this->hasOneThrough(Branch::class, Title::class, 'id', 'id', 'title_id', 'branch_id');
    }




    protected static function booted()
{
    static::created(function (Worker $worker) {
        if ($worker->title) {
            $worker->title->increment('workersCount');
            if ($worker->title->branch) {
                $worker->title->branch->increment('workersCount');
            }
        }

        if ($worker->store) {
            $worker->store->increment('workersCount');
        }
    });

    static::updated(function (Worker $worker) {
        if ($worker->wasChanged('title_id')) {

            $oldTitle = Title::find($worker->getOriginal('title_id'));
            if ($oldTitle) {
                $oldTitle->decrement('workersCount');
                if ($oldTitle->branch) {
                    $oldTitle->branch->decrement('workersCount');
                }
            }

            if ($worker->title) {
                $worker->title->increment('workersCount');
                if ($worker->title->branch) {
                    $worker->title->branch->increment('workersCount');
                }
            }
        }

        if ($worker->wasChanged('store_id')) {
            $oldStore = Store::find($worker->getOriginal('store_id'));
            if ($oldStore) {
                $oldStore->decrement('workersCount');
            }

            if ($worker->store) {
                $worker->store->increment('workersCount');
            }
        }
    });
}

}
