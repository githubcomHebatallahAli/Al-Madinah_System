<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
     use HasFactory;
    protected $fillable = [
        'name',
        'creationDate',
        'status',
        'branchesCount',
        'storesCount'
    ];

        public function branch()
    {
        return $this->hasMany(Branch::class);
    }
}
