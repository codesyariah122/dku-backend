<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryCampaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'category_campaigns';

    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign');
    }
}
