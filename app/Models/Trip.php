<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
      use HasFactory;
    protected $fillable = [
        'admin_id',
        'branch_id',
        'name',
        'workersCount',
        'status',
        'creationDate'
    ];

        public function workers()
    {
        return $this->hasMany(Worker::class);
    }

        public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


}
