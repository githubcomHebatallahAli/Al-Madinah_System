<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'added_by',
        'added_by_type',
        'office_id',
        'name',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

  public function workers()
{
    return $this->belongsToMany(Worker::class, 'campaign_workers');
}

  public function office()
{
    return $this->belongsTo(Office::class);
}

  public function groups()
{
    return $this->hasMany(Group::class);
}

//         public function admin()
// {
//     return $this->belongsTo(Admin::class, 'admin_id');
// }

public function creator()
{
    return $this->morphTo('added_by');
}



    protected $casts = [
    'changed_data' => 'array',
];


}
