<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donatur extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign');
    }

    public function category_campaigns()
    {
        return $this->belongsToMany('App\Models\CategoryCampaign');
    }

    public function banks()
    {
        return $this->belongsToMany('App\Models\Bank');
    }

    public function nominals()
    {
        return $this->belongsToMany('App\Models\Nominal');
    }

}
