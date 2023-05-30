<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\{Hash, Validator, Http};
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{User, Profile, Login};
use App\Helpers\UserHelpers;
use App\Events\{EventNotification, UserDonationLoginEvent};
use App\Mail\EmailNotificationSecurity;


class LoginController extends Controller
{
    /**
     * login
     *
     * @param  mixed $request
     * @return void
     */
    private $helper, $email_domain;

    public function __construct()
    {
        $this->email_domain = new UserHelpers;
        $this->helper = new UserHelpers;
    }

    private function forbidenIsUserLogin($isLogin)
    {
        return $isLogin ? true : false;
    }

    public function user_donation_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $check_userRole = User::whereNull('deleted_at')
            ->whereDoesntHave('roles', function($query) {
                $query->where('roles.id', [1,2])
                ->whereNull('roles.deleted_at');
            })
            ->where('email', $request->email)
            ->with('roles')
            ->get();

        if(count($check_userRole) > 0) {
            $user = User::whereNull('deleted_at')
                ->where('email', $request->email)
                ->get();
            $user_agent = $request->server('HTTP_USER_AGENT');

            $ip_client = $request->getClientIp() !== '172.22.0.1' && $request->getClientIp() !== '127.0.0.1' ? $request->getClientIp() : '103.147.8.112';
            $geo = Http::get("http://ip-api.com/json/{$ip_client}")->json();

            if (count($user) === 0) {
                return response()->json([
                    'not_found' => true,
                    'message' => 'Your email not registered !'
                ]);
            } else {

                if (!Hash::check($request->password, $user[0]->password)) :
                    return response()->json([
                        'success' => false,
                        'message' => 'Your password its wrong'
                    ]);
                else :
                    if ($user[0]->status === "INACTIVE") {
                        $user_activation = User::with('user_activations')->findOrFail($user[0]->id);
                        return response()->json([
                            'in_active' => true,
                            'message' => "{$user[0]->name}, Akun Tidak Aktiv.",
                            'data' => $user_activation
                        ]);
                    } else {
                        if ($this->forbidenIsUserLogin($user[0]->is_login)) {
                            $last_login = Carbon::parse($user[0]->last_login)->diffForHumans();
                            $details = [
                                'name' => $user[0]->name,
                                'title' => "Seseorang, baru saja mencoba mengakses akun Anda!",
                                'message' => "Seseorang mencoba mengakses akun anda melalui alamat email : {$user[0]->email}",
                                'url' => "https://dev.dompetkebaikanumat.com/user/settings/security/{$user[0]->id}",
                                'user_agent' => $user_agent
                            ];

                            Mail::to($user[0]->email)->send(new EmailNotificationSecurity($details));

                            $data_event = [
                                'notif' => "Seseorang, baru saja mencoba mengakses akun Anda!",
                                'emailForbaiden' => $user[0]->email,
                            ];

                            event(new UserDonationLoginEvent($data_event));

                            event(new EventNotification($data_event));

                            return response()->json([
                                'is_login' => true,
                                'message' => "Akun sedang digunakan {$last_login}",
                                'quote' => 'Please check the notification again!'
                            ]);
                        }

                        $token = $user[0]->createToken($user[0]->name)->accessToken;

                        $user_login = User::findOrFail($user[0]->id);
                        $user_login->is_login = 1;

                        if ($request->remember_me) {
                            $dates = Carbon::now()->addDays(7);
                            $user_login->expires_at = $dates;
                            $user_login->remember_token = Str::random(32);
                        } else {
                            $user_login->expires_at = Carbon::now()->addRealMinutes(60);
                        }

                        $user_login->last_login = Carbon::now();
                        $user_login->save();
                        $user_id = $user_login->id;

                        $user_for_profile_query = User::with('profiles')->findOrFail($user_id);
                        $user_profile_id = $user_for_profile_query->profiles[0]->id;
                        $user_profile = Profile::findOrFail($user_profile_id);
                        // var_dump($user_profile);
                        $user_profile->user_agent = $user_agent;
                        $user_profile->city = $geo['city'];
                        $user_profile->province = $geo['regionName'];
                        $user_profile->country = $geo['country'];
                        $user_profile->country_flag = "https://flagsapi.com/{$geo['countryCode']}/shiny/64.png";
                        $user_profile->longitude = $geo['lon'];
                        $user_profile->latitude = $geo['lat'];
                        $user_profile->ip_address = $ip_client;
                        $user_profile->save();

                        $logins = new Login;
                        $logins->user_id = $user_id;
                        $logins->user_token_login = $token;
                        $logins->save();
                        $login_id = $logins->id;

                        // sync pivot table
                        $user[0]->logins()->sync($login_id);

                        $userIsLogin = User::whereId($user_login->id)
                            ->with('profiles')
                            ->with('roles')
                            ->with('logins')
                            ->get();


                        $data_event = [
                            'type' => 'login',
                            'email' => $user[0]->email,
                            'role' => $user[0]->role,
                            'notif' => "{$user[0]->name}, baru saja login!",
                            'data' => $userIsLogin
                        ];

                        event(new UserDonationLoginEvent($data_event));
                        event(new EventNotification($data_event));

                        return response()->json([
                            'success' => true,
                            'message' => 'Login Success!',
                            'data'    => $userIsLogin,
                            'remember_token' => $user_login->remember_token
                        ]);
                    }
                endif;
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Admin Or author cannot access this content!'
            ], 404);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_userRole = User::whereNull('deleted_at')
                ->whereDoesntHave('roles', function($query) {
                    $query->where('roles.id', 3)
                    ->whereNull('roles.deleted_at');
                })
                ->where('email', $request->email)
                ->with('roles')
                ->get();

            if(count($check_userRole) > 0) {
                $user = User::whereNull('deleted_at')
                ->where('email', $request->email)
                ->get();
                $user_agent = $request->server('HTTP_USER_AGENT');

                $ip_client = $request->getClientIp() !== '172.22.0.1' && $request->getClientIp() !== '127.0.0.1' ? $request->getClientIp() : '103.147.8.112';
                $geo = Http::get("http://ip-api.com/json/{$ip_client}")->json();

                if (count($user) === 0) {
                    return response()->json([
                        'not_found' => true,
                        'message' => 'Your email not registered !'
                    ]);
                } else {

                    if (!Hash::check($request->password, $user[0]->password)) :
                        return response()->json([
                            'success' => false,
                            'message' => 'Your password its wrong'
                        ]);
                    else :
                        if ($user[0]->status === "INACTIVE") {
                            $user_activation = User::with('user_activations')->findOrFail($user[0]->id);
                            return response()->json([
                                'in_active' => true,
                                'message' => "{$user[0]->name}, Akun Tidak Aktiv.",
                                'data' => $user_activation
                            ]);
                        } else {
                        // var_dump($user[0]->email);
                        // die;
                            if ($this->forbidenIsUserLogin($user[0]->is_login)) {
                                $last_login = Carbon::parse($user[0]->last_login)->diffForHumans();
                                $details = [
                                    'name' => $user[0]->name,
                                    'title' => "Seseorang, baru saja mencoba mengakses akun Anda!",
                                    'message' => "Seseorang mencoba mengakses akun anda melalui alamat email : {$user[0]->email}",
                                    'url' => "https://dev.dompetkebaikanumat.com/user/settings/security/{$user[0]->id}",
                                    'user_agent' => $user_agent
                                ];

                                Mail::to($user[0]->email)->send(new EmailNotificationSecurity($details));

                                $data_event = [
                                    'notif' => "Seseorang, baru saja mencoba mengakses akun Anda!",
                                    'emailForbaiden' => $user[0]->email,
                                ];

                                event(new EventNotification($data_event));

                                return response()->json([
                                    'is_login' => true,
                                    'message' => "Akun sedang digunakan {$last_login}",
                                    'quote' => 'Please check the notification again!'
                                ]);
                            }

                            $token = $user[0]->createToken($user[0]->name)->accessToken;

                            $user_login = User::findOrFail($user[0]->id);
                            $user_login->is_login = 1;

                            if ($request->remember_me) {
                                $dates = Carbon::now()->addDays(7);
                                $user_login->expires_at = $dates;
                                $user_login->remember_token = Str::random(32);
                            } else {
                                $user_login->expires_at = Carbon::now()->addRealMinutes(60);
                            }

                            $user_login->last_login = Carbon::now();
                            $user_login->save();
                            $user_id = $user_login->id;

                            $user_for_profile_query = User::with('profiles')->findOrFail($user_id);
                            $user_profile_id = $user_for_profile_query->profiles[0]->id;
                            $user_profile = Profile::findOrFail($user_profile_id);
                        // var_dump($user_profile);
                            $user_profile->user_agent = $user_agent;
                            $user_profile->city = $geo['city'];
                            $user_profile->province = $geo['regionName'];
                            $user_profile->country = $geo['country'];
                            $user_profile->country_flag = "https://flagsapi.com/{$geo['countryCode']}/shiny/64.png";
                            $user_profile->longitude = $geo['lon'];
                            $user_profile->latitude = $geo['lat'];
                            $user_profile->ip_address = $ip_client;
                            $user_profile->save();

                            $logins = new Login;
                            $logins->user_id = $user_id;
                            $logins->user_token_login = $token;
                            $logins->save();
                            $login_id = $logins->id;

                        // sync pivot table
                            $user[0]->logins()->sync($login_id);

                            $userIsLogin = User::whereId($user_login->id)
                            ->with('profiles')
                            ->with('roles')
                            ->with('logins')
                            ->get();


                            $data_event = [
                                'type' => 'login',
                                'email' => $user[0]->email,
                                'role' => $user[0]->role,
                                'notif' => "{$user[0]->name}, baru saja login!",
                                'data' => $userIsLogin
                            ];

                            event(new EventNotification($data_event));

                            return response()->json([
                                'success' => true,
                                'message' => 'Login Success!',
                                'data'    => $userIsLogin,
                                'remember_token' => $user_login->remember_token
                            ]);
                        }
                    endif;
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'User cannot access dashboard!'
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'messge' => $th->getMessage()
            ]);
        }
    }

    /**
     * logout
     *
     * @param  mixed $request
     * @return void
     */
    public function logout(Request $request)
    {
        try {
            $user = User::findOrFail($request->user()->id);
            $user->is_login = 0;
            $user->expires_at = null;
            $user->remember_token = null;
            $user->save();

            $removeToken = $request->user()->tokens()->delete();
            $delete_login = Login::whereUserId($user->id);
            $delete_login->delete();

            $data_event = [
                'type' => 'logout',
                'notif' => "{$user->name}, telah keluar!",
                'data' => $user
            ];

            event(new EventNotification($data_event));

            if ($removeToken) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logout Success!',
                    'data' => $user
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function userProfile(Request $request)
    {
        try {
            $user = $request->user();
            $user_login = User::whereEmail($user->email)
                ->with('profiles')
                ->with('roles')
                ->with('logins')
                ->get();
            if (count($user_login) > 0) {
                return response()->json([
                    'message' => 'User data is login',
                    'data' => $user_login
                ], 200);
            } else {
                return response()->json([
                    'not_login' => true,
                    'message' => 'Anauthenticated'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'valid' => auth()->check()
            ]);
        }
    }
}
