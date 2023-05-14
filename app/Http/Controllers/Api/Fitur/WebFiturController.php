<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ContextData;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use App\Models\{User, Profile, CategoryCampaign};
use App\Events\EventNotification;
use Image;

class WebFiturController extends Controller
{

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
                    $totalData = User::whereNull('deleted_at')
                        ->get();
                    $totals = count($totalData);
                    break;

                case 'CATEGORY_CAMPAIGN':
                    $totalData = CategoryCampaign::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                    break;

                default:
                    $totalData = [];
            }

            return response()
                ->json([
                    'message' => "Total {$type}",
                    'total' => $totals
                ]);
        } catch (\Throwable $th) {
            throw $th;
        }
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

                $filenametostore = $filename . '_' . time() . '.' . $extension;

                $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                $thumbPath = public_path() . '/thumbnail_images/' . $filenametostore;

                if ($user_photo !== '' && $user_photo !== NULL) {
                    $old_photo = public_path() . '/' . $user_photo;
                    unlink($old_photo);
                }

                Image::make($thumbImage)->save($thumbPath);
                // $file = $image->store(trim(preg_replace('/\s+/', '', trim(preg_replace('/\s+/', '_', strtolower($request->name))))) . '/thumbnail', 'public');
                $new_profile = Profile::findOrFail($update_user->profiles[0]->id);
                $new_profile->photo = "thumbnail_images/" . $filenametostore;
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
}
