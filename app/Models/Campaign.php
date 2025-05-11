<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'group_id',
        'name',
        'status',
        'creationDate'
    ];

  public function workers()
{
    return $this->belongsToMany(Worker::class, 'campaign_workers');
}


}
