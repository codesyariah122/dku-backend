<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\ContextData;
use App\Models\{Campaign, User, Profile, CategoryCampaign};
use App\Events\EventNotification;
use App\Helpers\UserHelpers;
use Image;

class WebFiturController extends Controller
{
    private $helpers;

    public function __construct()
    {
        $this->helpers = new UserHelpers;
    }

    public function web_data()
    {
        try {
            $my_context = new ContextData;
            $ownerInfo = $my_context->getInfoData('COD(O.t)');
            return response()->json([
                'message' => 'Owner data info',
                'data' => $ownerInfo
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function trash(Request $request)
    {
        try {
            $dataType = $request->query('type');
            switch ($dataType):
                case 'USER_DATA':
                    $deleted = User::onlyTrashed()
                        ->with('profiles')
                        ->with('roles')
                        ->paginate(10);
                    break;

                case 'CATEGORY_CAMPAIGN_DATA':
                    $deleted = CategoryCampaign::onlyTrashed()
                        ->paginate(10);
                    break;

                case 'CAMPAIGN_DATA':
                    $deleted = Campaign::onlyTrashed()
                        ->paginate(10);
                    break;

                default:
                    $deleted = [];
                    break;
            endswitch;

            return response()->json([
                'message' => 'Deleted data on trashed!',
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function restoreTrash(Request $request, $id)
    {
        try {
            $dataType = $request->query('type');
            switch ($dataType):
                case 'USER_DATA':
                    $restored_user = User::withTrashed()
                        ->where('id', $id);
                    $restored_user->restore();
                    $restored = User::findOrFail($id);

                    break;

                case 'CATEGORY_CAMPAIGN_DATA':
                    $restored_category_campaign = CategoryCampaign::onlyTrashed()
                        ->where('id', $id);
                    $restored_category_campaign->restore();
                    $restored = CategoryCampaign::findOrFail($id);

                    break;
                case 'CAMPAIGN_DATA':
                    $restored_campaign = Campaign::onlyTrashed()
                        ->where('id', $id);
                    $restored_campaign->restore();
                    $restored = Campaign::findOrFail($id);

                    break;

                default:
                    $restored = [];
            endswitch;

            $data_event = [
                'notif' => "{$restored->name}, has been restored!",
                'data' => $restored
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'message' => 'Restored data on trashed Success!',
                'data' => $restored
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deletePermanently(Request $request, $id)
    {
        try {
            $dataType = $request->query('type');
            switch ($dataType):
                case 'USER_DATA':
                    $deleted = User::onlyTrashed()
                        ->with('profiles')
                        ->where('id', $id)
                        ->first();

                    if ($deleted->profiles[0]->photo !== "" && $deleted->profiles[0]->photo !== NULL) {
                        $old_photo = public_path() . '/' . $deleted->profiles[0]->photo;
                        unlink($old_photo);
                    }

                    $deleted->profiles()->delete();
                    $deleted->forceDelete();

                    break;

                case 'CATEGORY_CAMPAIGN_DATA':
                    $deleted = CategoryCampaign::onlyTrashed()
                        ->where('id', $id)->first();
                    // $deleted->categories()->delete();
                    $deleted->forceDelete();

                    break;

                case 'CAMPAIGN_DATA':
                    $deleted = Campaign::onlyTrashed()
                        ->where('id', $id)->first();
                    // $deleted->categories()->delete();
                    if ($deleted->banner !== "" && $deleted->banner !== NULL) {
                        $old_photo = public_path() . '/' . $deleted->banner;
                        unlink($old_photo);
                    }
                    $deleted->forceDelete();

                    break;

                default:
                    $deleted = [];
            endswitch;

            $data_event = [
                'notif' => "Data has been deleted!",
                'data' => $deleted
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'message' => 'Deleted data on trashed Success!',
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function totalTrash(Request $request)
    {
        try {
            $type = $request->query('type');
            switch ($type) {
                case 'USER_DATA':
                    $countTrash = User::onlyTrashed()
                        ->get();
                    break;
                case 'CATEGORY_CAMPAIGN_DATA':
                    $countTrash = CategoryCampaign::onlyTrashed()->get();
                    break;
                default:
                    $countTrash = [];
            }

            return response()
                ->json([
                    'message' => $type . ' Trash',
                    'data' => count($countTrash)
                ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalData(Request $request)
    {
        try {
            $type = $request->query('type');

            switch ($type) {
                case "TOTAL_USER":
                    $msg_title = 'User Data';
                    $totalData = User::whereNull('deleted_at')
                        ->get();
                    $totals = count($totalData);
                    break;

                case 'CATEGORY_CAMPAIGN':
                    $msg_title = 'Category Campaign Data';
                    $totalData = CategoryCampaign::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                    break;
                case "TOTAL_CAMPAIGN":
                    $msg_title = 'Campaign Data';
                    $totalData = Campaign::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                    break;

                default:
                    $totalData = [];
            }

            return response()
                ->json([
                    'message' => "Total {$msg_title}",
                    'total' => $totals
                ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function user_is_online(Request $request)
    {
        try {
            $user_is_online = User::whereIsLogin(1)->get();
            return response()->json([
                'message' => 'User is online',
                'data' => count($user_is_online)
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function upload_profile_picture(Request $request, $id)
    {
        try {
            $update_user = User::with('profiles')->findOrFail($id);
            $user_photo = $update_user->profiles[0]->photo;
            $image = $request->file('photo');

            if ($image !== '' && $image !== NULL) {
                $nameImage = $image->getClientOriginalName();
                $filename = pathinfo($nameImage, PATHINFO_FILENAME);

                $extension = $request->file('photo')->getClientOriginalExtension();

                $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;

                if ($user_photo !== '' && $user_photo !== NULL) {
                    $old_photo = public_path() . '/' . $user_photo;
                    unlink($old_photo);
                }

                Image::make($thumbImage)->save($thumbPath);
                $new_profile = Profile::findOrFail($update_user->profiles[0]->id);
                $new_profile->photo = "thumbnail_images/users/" . $filenametostore;
                $new_profile->save();

                $profile_has_update = Profile::with('users')->findOrFail($update_user->profiles[0]->id);

                $data_event = [
                    'notif' => "{$update_user->name} photo, has been updated!",
                    'data' => $profile_has_update
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'message' => 'Profile photo has been updated',
                    'data' => $profile_has_update
                ]);
            } else {
                return response()->json([
                    'message' => 'please choose files!!'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    public function update_user_profile(Request $request, $id)
    {
        try {

            $update_user = User::findOrFail($id);
            $update_user->name = $request->name ? $request->name : $update_user->name;
            $update_user->email = $request->email ? $request->email : $update_user->email;
            $update_user->phone = $request->phone ? $request->phone : $update_user->phone;
            $update_user->status = $request->status ? $request->status : $update_user->status;
            $update_user->save();

            $user_profiles = User::with('profiles')->findOrFail($update_user->id);

            $update_profile = Profile::findOrFail($user_profiles->profiles[0]->id);
            $update_profile->username = $request->name ? trim(preg_replace('/\s+/', '_', $request->name)) : $user_profiles->profiles[0]->username;

            if ($request->name) {

                $user_image_path = url($update_user->profiles[0]->photo);
                $check_photo_db = env('APP_URL') . '/' . $update_user->profiles[0]->photo;

                if ($user_image_path !== $check_photo_db) {
                    $old_photo = public_path() . '/' . $update_user->profiles[0]->photo;
                    unlink($old_photo);

                    $initial = $this->initials($update_user->name);
                    $path = 'thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = $initial;
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';

                    // store into database field photo
                    $update_profile->photo = $path . $photo;
                } else {
                    $update_profile->photo = $update_user->profiles[0]->photo;
                }
            } else {
                $user_update = User::findOrFail($id);
                $path = 'thumbnail_images/users/';
                $fontPath = public_path('fonts/Oliciy.ttf');
                $char = strtoupper($user_update->name[0]);
                $newAvatarName = rand(12, 34353) . time() . '.png';
                $dest = $path . $newAvatarName;

                $createAvatar = makeAvatar($fontPath, $dest, $char);
                $photo = $createAvatar == true ? $newAvatarName : '';

                // store into database field photo
                $update_profile->photo = $path . $photo;
            }


            $update_profile->about = $request->about ? $request->about : $user_profiles->profiles[0]->about;
            $update_profile->address = $request->address ? $request->address : $user_profiles->profiles[0]->address;
            $update_profile->post_code = $request->post_code ? $request->post_code : $user_profiles->profiles[0]->post_code;
            $update_profile->city = $request->city ? $request->city : $user_profiles->profiles[0]->city;
            $update_profile->district = $request->district ? $request->district : $user_profiles->profiles[0]->district;
            $update_profile->province = $request->province ? $request->province : $user_profiles->profiles[0]->province;
            $update_profile->country = $request->country ? $request->country : $user_profiles->profiles[0]->country;
            $update_profile->save();

            $new_user_updated = User::whereId($update_user->id)->with('profiles')->get();

            return response()->json([
                'message' => "Update user {$update_user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }
}
