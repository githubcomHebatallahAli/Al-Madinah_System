<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory;
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




    // public function role()
    // {
    //     return $this->belongsTo(Role::class);
    // }

    // public function addedBy()
    // {
    //     return $this->belongsTo(Worker::class, 'added_by');
    // }

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
        return $this->belongsToMany(Campaign::class, 'campaign_workers');
    }


    public function branch()
    {
        return $this->hasOneThrough(Branch::class, Title::class, 'id', 'id', 'title_id', 'branch_id');
    }

    protected static function booted()
    {
        static::created(function ($worker) {
            if ($worker->title && $worker->title->branch) {
                $worker->title->branch->increment('workersCount');
            }
        });

        static::updated(function ($worker) {
            if ($worker->wasChanged('title_id')) {
                if ($worker->title && $worker->title->branch) {
                    $worker->title->branch->increment('workersCount');
                }

                $oldTitle = Title::find($worker->getOriginal('title_id'));
                if ($oldTitle && $oldTitle->branch) {
                    $oldTitle->branch->decrement('workersCount');
                }
            }
        });
    }


}
