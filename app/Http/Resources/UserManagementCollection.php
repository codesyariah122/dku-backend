<?php

namespace App\Http\Resources;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserManagementCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $users = collect($this->collection);
        $profiles = $users->map(function($user) {
            return $user->profiles->map(function($profile){
                return $profile->username;
            });
        });
        $roles = $users->map(function($user) {
            return $user->roles;
        });

        return [
            'success' => true,
            'message' => 'User data lists',
            'data' => $users,
            'profiles' => $profiles,
            'roles' => $roles
        ];
    }

    public function withResponse($request, $response)
    {
        if ($this->collection->isEmpty()) {
            $response->setStatusCode(404);
        }
    }
}
