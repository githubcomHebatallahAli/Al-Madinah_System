<?php

namespace App\Models;

use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class WorkerLogin extends Authenticatable  implements JWTSubject
{
     use HasFactory, Notifiable,HijriDateTrait,TracksChangesTrait;
     protected $fillable = [
        'worker_id',
        'email',
        'password',
        'role_id',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',
        'status'
     ];

public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}



     public function worker()
{
    return $this->belongsTo(Worker::class);
}
     public function role()
    {
        return $this->belongsTo(Role::class);
    }

        public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    protected $hidden = [
        'password',
        'remember_token',
    ];

       protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'changed_data' => 'array',
    ];

}
