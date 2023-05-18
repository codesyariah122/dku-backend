<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Image;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\{User, Profile, UserRole, Roles};
use App\Events\EventNotification;
use App\Helpers\UserHelpers;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $helpers;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(function ($request, $next) {
            if (Gate::allows('user-management')) return $next($request);
            return response()->json([
                'error' => true,
                'message' => 'Anda tidak memiliki cukup hak akses'
            ]);
        });;
        $this->helpers = new UserHelpers;
    }
    public function index()
    {
        try {
            $users = User::whereNull('deleted_at')
                ->with('profiles')
                ->with('roles')
                ->orderBy('id', 'DESC')
                ->paginate(5);

            return response()->json([
                'message' => 'User data lists',
                'data' => $users
            ]);
        } catch (\Throwable $th) {
            throw $th;
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
                'name' => 'required|max:25',
                'email' => 'required|email|unique:users,email',
                'password'  => [
                    'required', Password::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols()
                ],
                'role' => 'required',
                'status' => 'required',
                'photo' => 'image|mimes:jpg,png,jpeg|max:1048'
                // 'username' => 'required|string|regex:/\w*$/|unique:profiles,username|max:10',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->role);

            if ($check_roles->checkRoles($request->user())) :
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles}, tidak di ijinkan menambah data"
                ]);
            endif;

            $role_id = $request->role;

            $check_user_role = Roles::whereId($role_id)->get();

            if (count($check_user_role) > 0) {
                $new_user = new User;
                $new_user->name = $request->name;
                $new_user->email = $request->email;
                $new_user->role = $request->role;
                $new_user->password = Hash::make($request->password);
                $new_user->status = $request->status;
                $new_user->save();
                $new_profile = new Profile;
                $new_profile->username = $request->username ? $request->username : trim(preg_replace('/\s+/', '_', strtolower($request->name)));

                if ($request->file('photo')) {
                    $image = $request->file('photo');
                    $nameImage = $image->getClientOriginalName();
                    $filename = pathinfo($nameImage, PATHINFO_FILENAME);
                    $trimName = trim(preg_replace('/\s+/', '_', strtolower($new_user->name)));

                    $extension = $request->file('photo')->getClientOriginalExtension();

                    $filenametostore = $trimName . '_' . Str::random(12) . '_' . time() . '.' . $extension;

                    $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                    $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;
                    Image::make($thumbImage)->save($thumbPath);

                    $new_profile->photo = "thumbnail_images/users/" . $filenametostore;
                } else {
                    $path = 'thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = strtoupper($new_user->name[0]);
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';

                    // store into database field photo
                    $new_profile->photo = $path . $photo;
                }


                $new_profile->save();
                $user_profile_id = $new_profile->id;
                $new_user->profiles()->sync($user_profile_id);

                $role_user = new UserRole;
                $role_user->user_id = $new_user->id;
                $role_user->roles_id = $role_id;
                $role_user->save();
                // $new_user->roles()->sync($role_user->id);

                $add_new_user = User::whereId($new_user->id)
                    ->with('profiles')
                    ->with('roles')
                    ->get();

                $data_event = [
                    'notif' => "{$add_new_user[0]->name}, successfully added!",
                    'data' => $add_new_user
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "{$add_new_user[0]->name}, successfully added!",
                    'data' => $add_new_user
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User roles is not defined!'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
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
        try {
            $user_detail = User::whereId($id)
                ->with('profiles')
                ->with('roles')
                ->get();

            if (count($user_detail) % 2 == 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'User detail data',
                    'data' => $user_detail
                ]);
            }

            return response()->json([
                'message' => 'User not found'
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
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
        try {
            $user = User::with('profiles')->findOrFail($id);

            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->roles[0]->roles);
            if ($check_roles->checkRoles($request->user())) :
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles[0]}, tidak di ijinkan mengupdate data"
                ]);
            endif;


            $update_user = User::findOrFail($user->id);
            $update_user->name = $request->name ? $request->name : $user->name;
            $update_user->email = $request->email ? $request->email : $user->email;
            $update_user->phone = $request->phone ? $request->phone : $user->phone;
            $update_user->status = $request->status ? $request->status : $user->status;
            $update_user->save();

            $update_profile = Profile::findOrFail($user->profiles[0]->id);
            $update_profile->username = trim(preg_replace('/\s+/', '_', $request->username ? $request->username : $user->name));

            if ($update_user->profiles[0]->photo !== "" && $update_user->profiles[0]->photo !== NULL) {
                $old_photo = public_path() . '/' . $update_user->profiles[0]->photo;
                unlink($old_photo);
            }

            if ($request->file('photo')) {
                $image = $request->file('photo');
                $nameImage = $image->getClientOriginalName();
                $filename = pathinfo($nameImage, PATHINFO_FILENAME);
                $trimName = trim(preg_replace('/\s+/', '_', strtolower($new_user->name)));


                $extension = $request->file('photo')->getClientOriginalExtension();

                $filenametostore = $trimName . '_' . Str::random(12) . '_' . time() . '.' . $extension;

                $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;
                Image::make($thumbImage)->save($thumbPath);

                // $file = $image->store(trim(preg_replace('/\s+/', '', trim(preg_replace('/\s+/', '_', strtolower($request->name))))) . '/thumbnail', 'public');
                $update_profile->photo = "thumbnail_images/users/" . $filenametostore;
            }

            $update_profile->about = $request->about;
            $update_profile->address = $request->address;
            $update_profile->post_code = $request->post_code;
            $update_profile->city = $request->city;
            $update_profile->district = $request->district;
            $update_profile->province = $request->province;
            $update_profile->country = $request->country;
            $update_profile->save();

            $new_user_updated = User::whereId($update_user->id)->with('profiles')->get();

            return response()->json([
                'message' => "Update user {$user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $delete_user = User::whereNull('deleted_at')->findOrFail($id);

            if ($delete_user->deleted_at !== NULL) {
                $delete_user->profiles()->delete();
            }
            $delete_user->delete();

            $data_event = [
                'notif' => "{$delete_user['name']}, success move to trash!",
                'data' => $delete_user
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "User {$delete_user['name']} success move to trash",
                'data' => $delete_user
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
