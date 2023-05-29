<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Viewer extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $table = 'viewers';

    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign');
    }
}
