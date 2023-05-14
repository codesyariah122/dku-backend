<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'campaigns';

    public function category_campaigns()
    {
        return $this->belongsToMany('App\Models\CategoryCampaign');
    }
}
