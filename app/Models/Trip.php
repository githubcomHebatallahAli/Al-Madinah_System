<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
         use HasFactory;
    protected $fillable = [
        'name',
        'creationDate',
        'status',
        'branch_id',
        'pilgrimsCount',
        'status',
        'creationDate',
        'description'
    ];

        public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


}
