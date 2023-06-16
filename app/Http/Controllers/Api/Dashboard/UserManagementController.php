<?php

namespace App\Http\Controllers\Api\Dashboard;

use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\UserManagementCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\{User, Profile, UserRole, Roles};
use App\Events\{DataManagementEvent, UpdateProfileEvent};
use App\Helpers\UserHelpers;

class UserManagementController extends Controller
{

    private $helpers, $initials, $username;

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
        $this->initials = new UserHelpers;
        $this->username = new UserHelpers;
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
                ->paginate(10);
            }

            return new UserManagementCollection($users);
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

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // 'name' => [
                //     'required',
                //     'max:25',
                //     'string',
                //     Rule::unique('users')
                // ],
                'name' => 'required|max:25|string',
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
                'photo' => 'image|mimes:jpg,png,jpeg|max:1048',
                // 'username' => [
                //     'required',
                //     'string',
                //     'max:255',
                //     Rule::unique('profiles')->ignore($username),
                // ],
                'username' => 'unique:profiles,username|max:10',
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
                $new_user->role = intval($request->role);
                $new_user->phone = $this->helpers->formatPhoneNumber($request->phone);
                $new_user->password = Hash::make($request->password);
                $new_user->status = $request->status;
                $new_user->save();
                $new_profile = new Profile;
                $new_profile->username = $this->username->get_username($new_user->name);

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
                    $initial = $this->initials->get_initials($new_user->name);
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
                // $new_user->roles()->sync([$role_user->id]);



                $users = User::whereId($new_user->id)
                ->with('profiles')
                ->with('roles')
                ->get();

                $data_event = [
                    'type' => 'added',
                    'notif' => "{$users[0]->name}, successfully added!"
                ];

                event(new DataManagementEvent($data_event));

                return new UserManagementCollection($users);

            } else {
                return new UserManagementCollection([]);
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
    public function show($username)
    {
        try {
            $user_detail = Profile::where('username', $username)
                ->with(['users' => function($user) {
                    return $user->with('roles');
                }])
                ->firstOrFail();

            if ($user_detail) {
                return response()->json([
                    'success' => true,
                    'message' => 'User detail data',
                    'data' => $user_detail
                ]);
            } else {                
                return response()->json([
                    'message' => 'User not found'
                ]);
            }
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
            $username = $request->username !== NULL ? $request->username : $this->username->get_username($request->name);

            $existingProfile = Profile::whereUsername($username)
                ->whereNull('deleted_at')
                ->first();
            $userExistingProfile = User::with('profiles')
                ->findOrFail($id);


            if ($existingProfile !== NULL && $userExistingProfile->profiles[0]->username !== $username) {
                return response()->json([
                    'error' => true,
                    'message' => 'Duplicate entry: ' . $username,
                    'data' => $existingProfile
                ], 409);
            }

        
            $user = User::with('profiles')->findOrFail($id);

            $update_user = User::findOrFail($user->id);
            $update_user->name = $request->name ? $request->name : $user->name;
            $update_user->email = $request->email ? $request->email : $user->email;
            $update_user->phone = $request->phone ? $this->helpers->formatPhoneNumber($request->phone) : $user->phone;
            $update_user->password = $request->password ? Hash::make($request->password) : $user->password;
            $update_user->status = $request->status ? $request->status : $user->status;
            $update_user->save();


            $update_profile = Profile::findOrFail($user->profiles[0]->id);
            $update_profile->username = $username;
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
                $exist_photo = env('APP_URL') . '/' . $user_photo;

                if ($user_image_path && $check_photo_db === $exist_photo) {
                    // $old_photo = public_path() . '/' . $update_user->profiles[0]->photo;
                    // unlink($old_photo);

                    // $initial = $this->initials->get_initials($request->name);
                    // $path = public_path() . '/thumbnail_images/users/';
                    // $fontPath = public_path('fonts/Oliciy.ttf');
                    // $char = $initial;
                    // $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    // $dest = $path . $newAvatarName;

                    // $createAvatar = makeAvatar($fontPath, $dest, $char);
                    // $photo = $createAvatar == true ? $newAvatarName : '';
                    // $save_path = 'thumbnail_images/users/';
                    // $update_profile->photo = $save_path . $photo;
                    $update_profile->photo = $update_user->profiles[0]->photo;
                } else {
                    $initial = $this->initials->get_initials($request->name);
                    $path = public_path() . '/thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = $initial;
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';
                    // $update_profile->photo = $update_user->profiles[0]->photo;
                    $save_path = 'thumbnail_images/users/';
                    $update_profile->photo = $save_path . $photo;
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
                'notif' => "{$new_user_updated[0]->name}, {$type_data_update} successfully update!"
            ];

            event(new DataManagementEvent($data_event));

            // return response()->json([
            //     'message' => "Update user {$user->name}, berhasil",
            //     'data' => $new_user_updated
            // ]);

            return new UserManagementCollection($new_user_updated);

        } catch (\Throwable $th) {
            response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function update(Request $request, $username)
    {
        try {
            $profile_byusername = Profile::with('users')
                ->whereUsername($username)
                ->firstOrFail();
            $profile_id = $profile_byusername->id;
            $user_id = $profile_byusername->users[0]->id;

            $update_user = User::findOrFail($user_id);

            $update_user->name = $request->name ? $request->name : $update_user->name;
            $update_user->email = $request->email ? $request->email : $update_user->email;
            $update_user->role = $request->role ? $request->role : $update_user->role;
            $update_user->password = $request->password ? Hash::make($request->password) : $update_user->password;
            $update_user->status = $request->status ? $request->status : $update_user->status;
            $update_user->save();

            $type_data_update = 'data';

            $data_event = [
                'type' => 'updated',
                'notif' => "{$update_user->name}, {$type_data_update} successfully update!"
            ];

            $update_user_success = User::with('profiles')
                ->findOrFail($update_user->id);

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "Update user {$update_user->name}, berhasil",
                'data' => $update_user_success
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
            $delete_user = User::with('profiles')
            ->whereNull('deleted_at')
            ->findOrFail($id);

            $delete_user->profiles()->delete();
            $delete_user->delete();

            $data_event = [
                'type' => 'removed',
                'notif' => "{$delete_user['name']}, success move to trash!",
                'data' => $delete_user
            ];

            event(new DataManagementEvent($data_event));


            $user_deleted = User::withTrashed()
                ->with('profiles', function($profile) {
                    $profile->withTrashed();
                })
                ->with('roles')
                ->whereId($id)
                ->get();

            return new UserManagementCollection($user_deleted);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
