<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDonation extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Roles');
    }
}
