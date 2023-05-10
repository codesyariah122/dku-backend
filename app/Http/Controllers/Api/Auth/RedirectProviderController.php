<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use App\Helpers\UserHelpers;
use Carbon\Carbon;
use App\Models\{User, Login, Profile, Roles, UserActivation};
use App\Events\EventNotification;

class RedirectProviderController extends Controller
{
    public const PROVIDERS = ['google'];
    public const SUCCESS = 200;
    public const FORBIDDEN = 403;
    public const UNAUTHORIZED = 401;
    public const NOT_FOUND = 404;
    public const NOT_ALLOWED = 405;
    public const UNPROCESSABLE = 422;
    public const SERVER_ERROR = 500;
    public const BAD_REQUEST = 400;
    public const VALIDATION_ERROR = 252;
    private $helper;

    public function __construct()
    {
        $this->helper = new UserHelpers;
    }

    public function sendResponse($result = [], $message = NULL)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, self::SUCCESS);
    }

    /**
     * success response method.
     *
     * @param  str  $message
     * @return \Illuminate\Http\Response
     */
    public function respondWithMessage($message = NULL)
    {
        return response()->json(['success' => true, 'message' => $message], self::SUCCESS);
    }

    /**
     * error response method.
     *
     * @param  int  $code
     * @param  str  $error
     * @param  array  $errorMessages
     * @return \Illuminate\Http\Response
     */
    public function sendError($code = NULL, $error = NULL, $errorMessages = [])
    {
        $response['success'] = false;

        switch ($code) {
            case self::UNAUTHORIZED:
                $response['message'] = 'Unauthorized';
                break;
            case self::FORBIDDEN:
                $response['message'] = 'Forbidden';
                break;
            case self::NOT_FOUND:
                $response['message'] = 'Not Found.';
                break;
            case self::NOT_ALLOWED:
                $response['message'] = 'Method Not Allowed.';
                break;
            case self::BAD_REQUEST:
                $response['message'] = 'Bad Request.';
                break;
            case self::UNPROCESSABLE:
                $response['message'] = 'Unprocessable Entity.';
                break;
            case self::SERVER_ERROR:
                $response['message'] = 'Whoops, looks like something went wrong.';
                break;
            case self::VALIDATION_ERROR:
                $response['message'] = 'Validation Error.';
                break;
            default:
                $response['message'] = 'Whoops, looks like something went wrong.';
                break;
        }

        $response['message'] = $error ? $error : $response['message'];
        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
    private function respondWithToken($token)
    {
        $success['token'] =  $token;
        $success['access_type'] = 'bearer';
        $success['expires_in'] = now()->addDays(15);

        return $this->sendResponse($success, 'Login successfully.');
    }

    public function redirectToProvider($provider)
    {
        if (!in_array($provider, self::PROVIDERS)) {
            return $this->sendError(self::NOT_FOUND);
        }

        $success['provider_redirect'] = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return $this->sendResponse($success, "Provider '" . $provider . "' redirect url.");
    }


    public function handleProviderCallback($provider)
    {


        try {
            if (!in_array($provider, self::PROVIDERS)) {
                return $this->sendError(self::NOT_FOUND);
            }

            $providerUser = Socialite::driver($provider)->stateless()->user();


            if ($providerUser) {

                $user = User::where('provider_name', $provider)
                    ->where('google_id', $providerUser->getId())
                    ->where('email', $providerUser->getEmail())
                    ->first();

                // $big_data_key = env('API_KEY_BIG_DATA');
                // $api_ip_key = env('API_KEY_IP_API');

                $ip_address = $this->helper->getIpAddr();

                if ($ip_address === "172.19.0.1" || $ip_address === "127.0.0.1") {
                    $ip_address = "103.139.10.159";
                } else {
                    $ip_address = $ip_address;
                }

                $userDetect = Http::get("http://ip-api.com/json/{$ip_address}")->json();
                $current = Carbon::now()->setTimezone($userDetect['timezone']);

                if (!$user) {
                    $check_roles = Roles::whereName(json_encode(["USER"]))->get();

                    // echo $check_roles[0]['id'];
                    // die;

                    $newuser = new User;
                    $newuser->google_id = $providerUser->getId();
                    $newuser->provider_name = $provider;
                    $newuser->name = $providerUser->getName();
                    $newuser->email = $providerUser->getEmail();
                    $newuser->password = Hash::make($providerUser->getName() . '@' . $providerUser->getId());
                    $newuser->status = 'ACTIVE';
                    $newuser->is_login = 1;
                    $newuser->expires_at = now()->addRealDays(1);
                    $newuser->last_login = $current;
                    $newuser->save();

                    $token = $newuser->createToken('authToken')->accessToken;

                    if (count($check_roles) > 0) {
                        $roles = Roles::findOrFail($check_roles[0]['id']);
                    } else {
                        $roles = new Roles;
                        $roles->name = json_encode(["USER"]);
                        $roles->save();
                    }

                    $newuser->roles()->sync($roles->id);

                    $logins = new Login;
                    $logins->user_id = $newuser->id;
                    $logins->user_token_login = $token;
                    $logins->save();
                    $login_id = $logins->id;
                    $newuser->logins()->sync($login_id);

                    $userIsLogin = User::whereId($newuser->id)
                        ->with('profiles')
                        ->with('logins')
                        ->get();

                    $data_event = [
                        'notif' => "{$newuser->name}, baru saja login!",
                        'data' => $userIsLogin
                    ];

                    event(new EventNotification($data_event));

                    // saving profile table
                    $profile = new Profile;
                    $profile->username = trim(preg_replace('/\s+/', '_', strtolower($providerUser->getName())));
                    $profile->g_avatar = $providerUser->getAvatar();
                    $profile->city = $userDetect['city'];
                    $profile->province = $userDetect['regionName'];
                    $profile->country = $userDetect['country'];
                    $profile->country_flag = "https://flagsapi.com/{$userDetect['countryCode']}/shiny/64.png";
                    $profile->longitude = $userDetect['lon'];
                    $profile->latitude = $userDetect['lat'];
                    $profile->ip_address = $userDetect['query'];
                    $profile->save();
                    $profile_id = $profile->id;
                    $newuser->profiles()->sync($profile_id);

                    // saving user activation table
                    $user_activation = new UserActivation;
                    $user_activation->token = Str::random(11);
                    $user_activation->save();
                    $activation_id = $user_activation->id;
                    $newuser->user_activations()->sync($activation_id);

                    // return $this->respondWithToken($token);
                    return redirect(env('FRONTEND_APP_TEST') . '/auth/success?access_token=' . $token);
                } else {
                    $userHasRegistration = User::findOrFail($user->id);
                    $userHasRegistration->is_login = 1;
                    $userHasRegistration->expires_at = now()->addRealDays(1);
                    $userHasRegistration->last_login = $current;
                    $userHasRegistration->save();

                    $user_profile_data = User::whereId($userHasRegistration->id)
                        ->with('profiles')
                        ->get();

                    $profile_id = null;

                    foreach ($user_profile_data as $profile_data) {
                        $profile_id = $profile_data['profiles'][0]['id'];
                    }
                    // echo $profile_id;
                    // die;

                    $profileHasRegistration = Profile::findOrFail($profile_id);
                    $profileHasRegistration->city = $userDetect['city'];
                    $profileHasRegistration->province = $userDetect['regionName'];
                    $profileHasRegistration->country = $userDetect['country'];
                    $profileHasRegistration->country_flag = "https://flagsapi.com/{$userDetect['countryCode']}/shiny/64.png";
                    $profileHasRegistration->longitude = $userDetect['lon'];
                    $profileHasRegistration->latitude = $userDetect['lat'];
                    $profileHasRegistration->save();

                    $token = $user->createToken(env('apiToken'))->accessToken;

                    $checkTokenLogin = Login::whereUserId($userHasRegistration->id)->get();

                    if (count($checkTokenLogin) > 0) {
                        $checkToken = Login::findOrFail($checkTokenLogin[0]['id']);
                        $checkToken->user_token_login = $token;
                        $checkToken->save();
                        $login_id = $checkToken->id;
                    } else {
                        $logins = new Login;
                        $logins->user_id = $user->id;
                        $logins->user_token_login = $token;
                        $logins->save();
                        $login_id = $logins->id;
                    }

                    // sync pivot table
                    $userHasRegistration->logins()->sync($login_id);
                    return redirect(env('FRONTEND_APP_TEST') . '/auth/success?access_token=' . $token);
                }
            }
        } catch (\Exception $e) {
            // $haveUser = User::where('email', $providerUser->getEmail())
            //     ->get();
            // $token = $haveUser[0]->createToken(env('apiToken'))->accessToken;
            // $updateUserLogin = User::findOrFail($haveUser[0]['id']);
            // $loginUpdate = Login::whereUserId($updateUserLogin->id)->get();

            // if ($haveUser[0]['email'] == $providerUser->getEmail()) {
            //     if (count($loginUpdate) === 0) {
            //         $updateUserLogin->google_id = $providerUser->getId();
            //         $updateUserLogin->provider_name = $provider;
            //         $updateUserLogin->save();
            //         $profile = User::whereId($updateUserLogin->id)->with('profiles')->get();
            //         $updateProfile = Profile::findOrFail($profile[0]->id);
            //         $updateProfile->g_avatar = $providerUser->getAvatar();
            //         $updateProfile->save();
            //         $updateUserLogin->profiles()->sync($updateProfile->id);

            //         $checkLoginToken = Login::findOrFail($haveUser[0]['id']);
            //         var_dump($checkLoginToken);
            //         die;
            //         $newLogins = new Login;
            //         $newLogins->user_id = $haveUser[0]['id'];
            //         $newLogins->user_token_login = $token;
            //         $newLogins->save();
            //         $updateUserLogin->logins()->sync($newLogins->id);
            //         return redirect(env('FRONTEND_APP_TEST') . '/auth/success?access_token=' . $token);
            //     } else {
            //         $loginUserUpdate = Login::findOrFail($loginUpdate[0]['id']);
            //         // $loginUserUpdate->user_id = $haveUser[0]['id'];
            //         $loginUserUpdate->user_token_login = $token;
            //         $loginUserUpdate->save();
            //         $updateUserLogin->logins()->sync($loginUserUpdate->id);

            //         return redirect(env('FRONTEND_APP_TEST') . '/auth/success?access_token=' . $token);
            //     }
            // } else {
            //     return $this->sendError(self::UNAUTHORIZED, null, ['error' => $e->getMessage()]);
            // }
            return $this->sendError(self::UNAUTHORIZED, null, ['error' => $e->getMessage()]);
        }
    }
}
