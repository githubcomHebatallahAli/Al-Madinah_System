<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Worker extends Authenticatable  implements JWTSubject
{
    use HasFactory, Notifiable,HasCreatorTrait;
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
    ];

    public function workerLogin()
{
    return $this->hasOne(WorkerLogin::class);
}


   protected $casts = [
        'salary' => 'decimal:2',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'changed_data' => 'array',
    ];



    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // public function addedBy()
    // {
    //     return $this->belongsTo(Worker::class, 'added_by');
    // }

    public function creator()
{
    return $this->morphTo('added_by');
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

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
