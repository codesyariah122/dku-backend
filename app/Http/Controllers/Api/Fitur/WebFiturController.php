<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ContextData;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use App\Models\{User, CategoryCampaign};
use App\Events\EventNotification;
use App\Helpers\ProductPercentage;

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
                    $deleted = User::withTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = User::findOrFail($id);

                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                    break;

                case 'CATEGORY_CAMPAIGN_DATA':
                    $deleted = CategoryCampaign::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = CategoryCampaign::findOrFail($id);
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                    break;

                default:
                    $restored = [];
            endswitch;

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
                        ->where('id', $id)->first();
                    // $deleted->roles()->delete();
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
                'notif' => "Data has been restored!",
                'data' => $deleted
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'message' => 'Deleted data on trashed Success!',
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            throw $th;
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
}
