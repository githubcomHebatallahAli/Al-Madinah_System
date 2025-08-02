<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Close extends Model
{
    use HasFactory;

    protected $fillable = ['is_active'];
}
