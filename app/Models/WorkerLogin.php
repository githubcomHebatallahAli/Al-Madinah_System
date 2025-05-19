<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkerLogin extends Model
{
     use HasFactory,HasCreatorTrait;
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
     ];

     public function creator()
{
    return $this->morphTo('added_by');
}


     public function worker()
{
    return $this->belongsTo(Worker::class);
}


}
