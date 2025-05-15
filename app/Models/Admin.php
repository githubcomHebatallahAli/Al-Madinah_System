<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable  implements JWTSubject
{
    use HasFactory, Notifiable,HasCreatorTrait;


    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role_id',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'added_by',
        'added_by_type'
    ];

        public function role()
    {
        return $this->belongsTo(Role::class);
    }

    //   public function addedBy()
    // {
    //     return $this->belongsTo(Admin::class, 'added_by');
    // }


        public function cities()
    {
        return $this->hasMany(City::class);
    }

        public function branches()
    {
        return $this->hasMany(Branch::class);
    }

        public function offices()
    {
        return $this->hasMany(Office::class);
    }

        public function stores()
    {
        return $this->hasMany(Store::class);
    }

        public function trips()
    {
        return $this->hasMany(Trip::class);
    }

        public function titles()
    {
        return $this->hasMany(Title::class);
    }

        public function groups()
    {
        return $this->hasMany(Group::class);
    }

        public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }



        protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $casts = [
        'password' => 'hashed',
        'changed_data' => 'array',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getMorphClass()
    {
        return 'App\Models\Admin';
    }

}
