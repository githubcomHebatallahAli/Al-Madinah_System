<?php

namespace App\Models;

use App\Traits\HasCreatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignWorker extends Model
{
    use HasFactory,HasCreatorTrait;
    protected $fillable = [
        'campaign_id',
        'worker_id',
        'joined_at',
        'added_by',
        'added_by_type',
        'creationDate',
        'creationDateHijri',
        'changed_data',
    ];
}
