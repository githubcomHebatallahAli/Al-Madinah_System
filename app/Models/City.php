<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'admin_id',
        'added_by',
        'added_by_type',
        'name',
        'creationDate',
        'creationDateHijri',
        'changed_data',
        'status',
        'branchesCount',
    ];

public function creator()
{
    return $this->morphTo(null, 'added_by_type', 'added_by');
}


        public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function admin()
{
    return $this->belongsTo(Admin::class);
}




protected $casts = [
    'changed_data' => 'array',
];

}
