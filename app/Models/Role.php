<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'name',
        'status',
        'guardName',
        'changed_data',
        'creationDate',
        'creationDateHijri',
        'added_by',
        'added_by_type'


    ];

    public function creator()
{
    return $this->morphTo('added_by');
}


     public function admin()
    {
        return $this->hasMany(Admin::class);
    }

     public function worker()
    {
        return $this->hasMany(Worker::class);
    }

    protected $casts = [
    'changed_data' => 'array',
];

}
