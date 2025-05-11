<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'office_id',
        'groupNum',
        'status',
        'creationDate'
    ];

        public function office()
    {
        return $this->belongsTo(Office::class);
    }

        public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
