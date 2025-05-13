<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory;
     const storageFolder= 'Workers';
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
        'creationDate',
        'creationDateHijri',
        'changed_data'
    ];

     public function admin()
{
    return $this->belongsTo(Admin::class, 'admin_id');
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




    public function branch()
    {
        return $this->belongsTo(Branch::class, 'title_id');
    }

    protected static function booted()
    {
        static::created(function ($worker) {
            $worker->title->branch->increment('workersCount');
        });

        static::updated(function ($worker) {
            if ($worker->wasChanged('title_id')) {
                $worker->title->branch->increment('workersCount');
                Title::find($worker->getOriginal('title_id'))->branch->decrement('workersCount');
            }
        });

    }
}
