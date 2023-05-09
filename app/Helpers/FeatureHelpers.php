<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
                $role = json_decode($roles[0]->roles[0]->roles);

                return count(array_intersect(["ADMIN", "OWNER"], $role)) ? true :  false;
            });
        endforeach;
    }
}
