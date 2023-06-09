<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function profiles()
    {
        return $this->belongsToMany('App\Models\Profile');
    }

    public function api_keys()
    {
        return $this->belongsToMany('App\Models\ApiKeys');
    }

    public function logins()
    {
        return $this->belongsToMany('App\Models\Login');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Roles');
    }

    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign');
    }

    public function user_activations()
    {
        return $this->belongsToMany('App\Models\UserActivation');
    }

    public function donaturs()
    {
        return $this->belongsToMany('App\Model\Donatur');
    }
}
