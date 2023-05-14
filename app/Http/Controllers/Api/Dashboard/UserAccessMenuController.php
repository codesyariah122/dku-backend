<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use App\Models\UserAccessMenu;
use App\Models\User;
use App\Models\Menu;
use App\Models\SubMenu;

/**
 * @author puji ermanto <puji.ermanto@gmail.com>
 * @return App\Http\Controller
 */

class UserAccessMenuController extends Controller
{
    public function access_menu_list(Request $request)
    {
        try {
            $user = $request->user();
            $user_logins = User::whereId($user->id)
                ->with('roles')
                ->get();

            $user_roles = null;

            foreach ($user_logins as $user) :
                foreach ($user->roles as $role) :
                    $user_roles = $role->id;
                endforeach;
            endforeach;

            $menus = Menu::whereJsonContains('roles', $user_roles)
                ->with('sub_menus')
                ->get();

            // $sub_menus = SubMenu::whereJsonContains('roles', $user_roles)
            //     ->with('menus')
            //     ->get();

            return response()->json([
                'message' => 'List menu of users',
                'users' => $user_logins,
                'menus' => $menus,
                // 'sub_menus' => $sub_menus
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
