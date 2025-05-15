<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerLogin extends Model
{
     protected $fillable = [
        'worker_id',
        'email',
        'password',
        'role_id',
     ];

     public function worker()
{
    return $this->belongsTo(Worker::class);
}
}
