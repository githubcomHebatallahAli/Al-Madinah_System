<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Title extends Model
{
      use HasFactory;
    protected $fillable = [
        'branch_id',
        'name',
        'workersCount',
        'status',
        'creationDate'
    ];

        public function worker()
    {
        return $this->hasMany(Worker::class);
    }

        public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


}
