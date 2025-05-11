<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
     use HasFactory;
    protected $fillable = [
        'admin_id',
        'name',
        'creationDate',
        'creationDateHijri',
        'status',
        'branchesCount',

    ];

        public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function admin()
{
    return $this->belongsTo(Admin::class, 'admin_id');
}




protected static function booted()
{
    static::created(function ($category) {
        $category->branchesCount = $category->branches()->count();
        $category->save();
    });



    static::deleted(function ($category) {
        if (method_exists($category, 'isForceDeleting') && $category->isForceDeleting()) {
            return;
        }

        if (!$category->trashed()) {
            $category->branchesCount = $category->branches()->count();
            $category->save();
        }
    });

}

public function getbranchesCountAttribute()
        {
            return $this->branches()->count();
        }

}
