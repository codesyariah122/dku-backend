<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\UserHelpers;
use App\Models\{User, Profile, UserActivation, Roles};
use App\Events\EventNotification;
use App\Mail\EmailActivation;

class RegisterController extends Controller
{
    /**
     * register
     *
     * @param  mixed $request
     * @return void
     */
    private $email_domain, $initials, $username;

    public function __construct()
    {
        $this->email_domain = new UserHelpers;
        $this->initials = new UserHelpers;
        $this->username = new UserHelpers;
    }

    public function register(Request $request)
    {
        try {
            $helper = new UserHelpers;
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'email'     => 'required|email|unique:users',
                'password'  => [
                    'required', 'confirmed', Password::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols()
                ]
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = new User;
            $user->name = strip_tags($request->name);
            $user->email = strip_tags($request->email);
            $user->role = 3;
            $user->phone = $request->phone ? $helper->formatPhoneNumber($request->phone) : null;
            $user->password = Hash::make($request->password);
            $user->status = 'INACTIVE';
            $user->is_login = 0;
            $user->save();

            // saving profile user table
            $user_profile = new Profile;
            $user_profile->username = trim(preg_replace('/\s+/', '_', strtolower($user->name).time()));

            // Make Profile avatar
            $initial = $this->initials->get_initials($user->name);
            $path = 'thumbnail_images/users/';
            $fontPath = public_path('fonts/Oliciy.ttf');
            $char = $initial;
            $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
            $dest = $path . $newAvatarName;

            $createAvatar = makeAvatar($fontPath, $dest, $char);
            $photo = $createAvatar == true ? $newAvatarName : '';

            // store into database field photo
            $user_profile->photo = $path . $photo;
            $user_profile->about = $request->about ? $request->about : null;
            $user_profile->save();
            $profile_id = $user_profile->id;
            $user->profiles()->sync($profile_id);

            // saving user activation table
            $user_activation = new UserActivation;
            $user_activation->token = Str::random(11);
            $user_activation->save();
            $activation_id = $user_activation->id;
            $user->user_activations()->sync($activation_id);

            $new_user_activate = User::findOrFail($user->id);
            $new_user_activate->activation_id = $user_activation->token;
            $new_user_activate->save();

            $roles = Roles::findOrFail(intval($user->role));
            $user->roles()->sync($roles->id);

            $new_user = User::with('profiles')
                ->with('roles')
                ->whereId($user->id)
                ->get();

            $data_event = [
                'type' => 'register',
                'notif' => "{$new_user[0]->name}, berhasil mendaftar!",
                'data' => $new_user
            ];

            $details = [
                'title' => 'Kamu Telah Berhasil Registrasi Di Website Dompet Kebaikan Umat',
                'url' => "http://localhost:3000/user/activation/{$user_activation->token}",
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'token' => $user_activation->token
            ];

            $domainEmail = $this->email_domain->customerDomainEmail($user);

            Mail::to($user->email)->send(new EmailActivation($details));

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Halo {$user->name}, registrasi kamu berhasil, silahkan cek inbox <a href='https://{$domainEmail}' target='_blank' class='btn btn-link'>{$user->email}</a> untuk mengaktifkan akun kamu.",
                'data'    => $new_user,
                'activation_detail' => $details
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function activation(Request $request, $id)
    {
        try {
            if ($request->activation_id) {
                $checkUserActivation = User::findOrFail($id);
                $username = $checkUserActivation->profiles[0]->username;
                $activation_data = UserActivation::findOrFail($request->activation_id);


                if ($checkUserActivation->status === "ACTIVE") {
                    return response()->json([
                        'active' => true,
                        'message' => 'Your account has been ACTIVE !',
                        'redirect_link' => env('FRONTEND_APP') . '/profile/' . $username
                    ]);
                }

                if ($activation_data->token !== $checkUserActivation->activation_id) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Activation id not valid !!'
                    ]);
                }
                $user_activation = User::findOrFail($id);
                $user_activation->status = 'ACTIVE';
                $user_activation->save();
                $new_user_active = User::findOrFail($id);

                $data_event = [
                    'type' => 'activation',
                    'notif' => "{$user_activation->name}, berhasil diaktivasi!",
                    'data' => $user_activation
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Hallo, {$new_user_active->name}, akun kamu berhasil diaktivasi!",
                    'data' => $new_user_active
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Activation id not found !!'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }
}
