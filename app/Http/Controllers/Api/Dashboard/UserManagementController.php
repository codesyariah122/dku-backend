<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\{User, Profile, UserRole, Roles};
use App\Events\{DataManagementEvent, UpdateProfileEvent};
use App\Helpers\UserHelpers;

class UserManagementController extends Controller
{
    private $helpers;

    /**
     * Display a listing of the resource.
     * @author: Puji Ermanto <puuji.ermanto@gmail.com>
     * @return \Illuminate\Http\Response
     */

    public function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

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

    public function index(Request $request)
    {
        try {
            $user_type = $request->query('role');

            if ($user_type == 'USER') {
                $users = User::whereNull('deleted_at')
                    ->with('profiles')
                    ->with('roles')
                    ->with('logins')
                    ->whereRole(3)
                    ->orderBy('id', 'DESC')
                    ->paginate(10);
            } else {
                $users = User::whereNull('deleted_at')
                    ->with('profiles')
                    ->with('roles')
                    ->with('logins')
                    ->whereIn('role', [1, 2])
                    ->orderBy('id', 'DESC')
                    ->paginate(5);
            }

            return response()->json([
                'message' => 'User data lists',
                'data' => $users
            ]);
        } catch (\Throwable $th) {
            response()->json([
                'error' => true,
                'message' => $th->getMessage()
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

                    $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                    $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                    $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;
                    Image::make($thumbImage)->save($thumbPath);

                    $new_profile->photo = "thumbnail_images/users/" . $filenametostore;
                } else {
                    $initial = $this->initials($new_user->name);
                    $path = public_path() . '/thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = $initial;
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';
                    // store into database field photo
                    $save_path = 'thumbnail_images/users/';
                    $new_profile->photo = $save_path . $photo;
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
                    'type' => 'added',
                    'notif' => "{$add_new_user[0]->name}, successfully added!",
                    'data' => $add_new_user
                ];

                event(new DataManagementEvent($data_event));

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
            response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
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

    public function update_with_profile_picture(Request $request, $id)
    {
        try {

            if ($request->name === NULL && $request->file('photo') === NULL) {
                return response()->json([
                    'error' => true,
                    'message' => 'Request body cannot be empty'
                ]);
            }

            $user = User::with('profiles')->findOrFail($id);

            $update_user = User::findOrFail($user->id);
            $update_user->name = $request->name ? $request->name : $user->name;

            $update_user->email = $request->email ? $request->email : $user->email;
            $update_user->phone = $request->phone ? $request->phone : $user->phone;
            $update_user->status = $request->status ? $request->status : $user->status;
            $update_user->save();

            $update_profile = Profile::findOrFail($user->profiles[0]->id);
            $update_profile->username = $request->name ? trim(preg_replace('/\s+/', '_', strtolower($request->name))) : $update_profile->username;
            $user_photo = $update_user->profiles[0]->photo;

            if ($request->name !== "" && $request->file('photo') !== NULL) {
                $image = $request->file('photo');

                if ($image !== '' || $image !== NULL) {
                    $extension = $request->file('photo')->getClientOriginalExtension();

                    $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                    $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                    $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;

                    if ($user_photo !== '' && $user_photo !== NULL) {
                        $old_photo = public_path() . '/' . $user_photo;
                        unlink($old_photo);
                    }

                    Image::make($thumbImage)->save($thumbPath);
                    $update_profile->photo = "thumbnail_images/users/" . $filenametostore;
                }
            } else if ($request->name !== "") {
                $user_image_path = file_exists(public_path($update_user->profiles[0]->photo));
                $check_photo_db = env('APP_URL') . '/' . $update_user->profiles[0]->photo;


                if ($user_image_path) {
                    $old_photo = public_path() . '/' . $update_user->profiles[0]->photo;
                    unlink($old_photo);

                    $initial = $this->initials($request->name);
                    $path = 'thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = $initial;
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';

                    $update_profile->photo = $path . $photo;
                } else {
                    $initial = $this->initials($request->name);
                    $path = 'thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = $initial;
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';
                    // $update_profile->photo = $update_user->profiles[0]->photo;
                    $update_profile->photo = $path . $photo;
                }
            } else {
                $image = $request->file('photo');

                if ($image !== '' && $image !== NULL) {
                    $extension = $request->file('photo')->getClientOriginalExtension();

                    $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                    $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                    $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;

                    if ($user_photo !== '' && $user_photo !== NULL) {
                        $old_photo = public_path() . '/' . $user_photo;
                        unlink($old_photo);
                    }

                    Image::make($thumbImage)->save($thumbPath);
                    $update_profile->photo = "thumbnail_images/users/" . $filenametostore;
                }
            }

            $update_profile->about = $request->about ? $request->about : $update_profile->about;
            $update_profile->address = $request->address ? $request->address : $update_profile->about;
            $update_profile->user_agent = $request->user_agent ? $request->user_agent : $update_profile->user_agent;
            $update_profile->post_code = $request->post_code ? $request->post_code : $update_profile->post_code;
            $update_profile->city = $request->city ? $request->city : $update_profile->city;
            $update_profile->district = $request->district ? $request->district : $update_profile->district;
            $update_profile->province = $request->province ? $request->province : $update_profile->province;
            $update_profile->country = $request->country ? $request->country : $update_profile->country;
            $update_profile->save();

            $new_user_updated = User::whereId($update_user->id)->with('profiles')->get();
            $type_data_update = $update_profile->photo !== $new_user_updated[0]->profiles[0]->photo ? 'photo' : 'data';

            $data_event = [
                'type' => 'updated',
                'notif' => "{$new_user_updated[0]->name}, {$type_data_update} successfully update!",
                'data' => $new_user_updated
            ];

            event(new UpdateProfileEvent($data_event));

            return response()->json([
                'message' => "Update user {$user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::with('profiles')->findOrFail($id);

            $update_user = User::findOrFail($user->id);
            $update_user->name = $request->name ? $request->name : $user->name;
            $update_user->email = $request->email ? $request->email : $user->email;
            $update_user->phone = $request->phone ? $request->phone : $user->phone;
            $update_user->status = $request->status ? $request->status : $user->status;
            $update_user->save();

            $update_profile = Profile::findOrFail($user->profiles[0]->id);
            $update_profile->username = $request->name ? trim(preg_replace('/\s+/', '_', strtolower($request->name))) : $update_profile->username;
            $update_profile->about = $request->about ? $request->about : $update_profile->about;
            $update_profile->address = $request->address ? $request->address : $update_profile->about;
            $update_profile->user_agent = $request->user_agent ? $request->user_agent : $update_profile->user_agent;
            $update_profile->post_code = $request->post_code ? $request->post_code : $update_profile->post_code;
            $update_profile->city = $request->city ? $request->city : $update_profile->city;
            $update_profile->district = $request->district ? $request->district : $update_profile->district;
            $update_profile->province = $request->province ? $request->province : $update_profile->province;
            $update_profile->country = $request->country ? $request->country : $update_profile->country;
            $update_profile->save();

            $new_user_updated = User::whereId($update_user->id)->with('profiles')->get();
            $type_data_update = 'data';

            $data_event = [
                'type' => 'updated',
                'notif' => "{$new_user_updated[0]->name}, {$type_data_update} successfully update!",
                'data' => $new_user_updated
            ];

            event(new UpdateProfileEvent($data_event));

            return response()->json([
                'message' => "Update user {$user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
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
                'type' => 'removed',
                'notif' => "{$delete_user['name']}, success move to trash!",
                'data' => $delete_user
            ];

            event(new DataManagementEvent($data_event));

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
