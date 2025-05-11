<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'title_id',
        'store_id',
        'name',
        'idNum',
        'personPhoNum',
        'branchPhoNum',
        'salary',
        'cv',
        'status',
        'creationDate'
    ];

        public function admins()
    {
        return $this->hasMany(Admin::class);
    }

        public function title()
    {
        return $this->belongsTo(Title::class);
    }

        public function store()
    {
        return $this->belongsTo(Store::class);
    }

   public function campaigns()
{
    return $this->belongsToMany(Campaign::class, 'campaign_workers');
}
}
