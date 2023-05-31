<?php

/**
 * Register any authentication / authorization services.
 * @author puji ermanto<pujiermanto@gmail.com>
 * @return Illuminate\Support\Facades\Gate
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Gate;
use App\Models\{User, Roles};

class FeatureHelpers
{
    protected $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function GatesAccess()
    {
        foreach ($this->data as $data) :
            Gate::define($data, function ($user) {
                $user_id = $user->id;
                $roles = User::whereId($user_id)->with('roles')->get();
                $role = json_decode($roles[0]->roles[0]->name);

                return count(array_intersect(["ADMIN", "AUTHOR"], $role)) > 0 ? true :  false;
            });
        endforeach;
    }
}
