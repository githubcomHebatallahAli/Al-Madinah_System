<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'campaign_id',
        'groupNum',
        'numBus',
        'status',
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

        public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

            public function admin()
{
    return $this->belongsTo(Admin::class, 'admin_id');
}


}
