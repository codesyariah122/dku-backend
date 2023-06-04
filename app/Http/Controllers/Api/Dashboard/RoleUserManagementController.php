<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use App\Models\{Roles, User, Profile};
use App\Events\DataManagementEvent;

class RoleUserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(function ($request, $next) {
            if (Gate::allows('roles-management')) return $next($request);
            return response()->json([
                'error' => true,
                'message' => 'Anda tidak memiliki cukup hak akses'
            ]);
        });
    }

    public function index()
    {
        try {
            $user_roles = Roles::whereNull('deleted_at')
                ->with('users')
                ->paginate(10);
            return response()->json([
                'message' => 'List user roles',
                'data' => $user_roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $roles = json_encode([$request->name]);
            $check_roles = in_array($request->name, json_decode($roles, true)) ? $request->name : null;


            $check_ready = Roles::whereName(json_encode([$check_roles]))->get();


            if (count($check_ready) > 0) {
                return response()->json([
                    'message' => "{$request->name}, its already taken!"
                ]);
            }

            $new_roles = new Roles;
            $new_roles->name = json_encode([$request->name]);
            $new_roles->save();

            $data_event = [
                'type' => 'added',
                'notif' => "{$new_roles->name}, berhasil ditambahkan!",
                'data' => $new_roles
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => 'added roles successfully',
                'data' => $new_roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            /**
             * Display a listing of the resource.
             *@author puji ermanto<pujiermanto@gmail.com>
             * @return \Illuminate\Http\Response
             */
            $delete_role = Roles::with(['users' => function ($query) {
                return $query->whereNull('deleted_at')->with('profiles')->get();
            }])
                ->whereNull('deleted_at')
                ->findOrFail($id);

            foreach ($delete_role->users as $user) {
                foreach ($user->profiles as $profile) {
                    $prepareProfile = Profile::whereNull('deleted_at')
                        ->findOrFail($profile->id);
                    $prepareProfile->delete();
                }
            }

            $delete_role->delete();

            $delete_role->users()->delete();

            $data_event = [
                'type' => 'removed',
                'notif' => "Roles {$delete_role['name']}, success move to trash!",
                'data' => $delete_role
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "User {$delete_role['name']} success move to trash",
                'data' => $delete_role
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
