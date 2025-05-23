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
        'changed_data',
        'creationDate',
        'creationDateHijri',
        'added_by',
        'added_by_type',
        'updated_by',
        'updated_by_type',


    ];

public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}

public function updater()
{
    return $this->morphTo(null, 'updated_by_type', 'updated_by');
}

     public function admin()
    {
        return $this->hasMany(Admin::class);
    }

     public function worker()
    {
        return $this->hasMany(WorkerLogin::class);
    }

    protected $casts = [
    'changed_data' => 'array',
];

}
