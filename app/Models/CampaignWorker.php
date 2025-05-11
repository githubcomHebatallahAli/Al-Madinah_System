<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignWorker extends Model
{
        use HasFactory;
    protected $fillable = [
        'campaign_id',
        'worker_id',
        'joined_at'
    ];
}
