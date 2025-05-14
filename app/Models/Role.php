<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
      use HasFactory;
    protected $fillable = [
        'name',
        'status',
        'guardName',
        'changed_data'
    ];

     public function admin()
    {
        return $this->hasMany(Admin::class);
    }

     public function worker()
    {
        return $this->hasMany(Worker::class);
    }
}
