<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Office extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'branch_id',
        'name',
        'address',
        'phoNum1',
        'phoNum2',
        'creationDate',
        "status"

    ];

         public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
