<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ContextData;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Order;
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

    public function barcode_fitur(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barcode' => 'required',
                'name' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $barcode = DNS1D::getBarcodePNG($request->barcode.' - '.$request->name, 'C39+');

            if($barcode) {
                return response()->json([
                    'success' => true,
                    'data' => "data:image/png;base64, {$barcode}"
                ]);
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function qrcode_fitur(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barcode' => 'required',
                'name' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $qr = new DNS2D;
            $qrCode = $qr->getBarcodeHTML($request->barcode ? $request->barcode : $request->invoice_number.'-'.$request->name, 'QRCODE', 4,4);

            if($qrCode) {
                return response()->json([
                    'success' => true,
                    'data' => $qrCode
                ]);
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function trash(Request $request)
    {
        try {
            $dataType = $request->query('type');
            switch($dataType):
                case 'USER_DATA':
                    $deleted = User::onlyTrashed()
                        ->with('roles')
                        ->paginate(10);
                break;

                case 'PRODUCT_DATA':
                    $deleted = Product::onlyTrashed()
                        ->with('categories')
                        ->paginate(10);
                break;

                case 'CATEGORY_DATA':
                    $deleted = Category::onlyTrashed()
                        ->with('products')
                        ->paginate(10);
                break;

                case 'CUSTOMER_DATA':
                    $deleted = Customer::onlyTrashed()
                        ->paginate(10);
                break;

                case 'SUPPLIER_DATA':
                    $deleted = Supplier::onlyTrashed()
                        ->paginate(10);
                break;

                case 'ORDER_DATA':
                    $deleted = Order::onlyTrashed()
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
            switch($dataType):
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

                case 'PRODUCT_DATA':
                    $deleted = Product::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = Product::findOrFail($id);
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                break;

                case 'CATEGORY_DATA':
                    $deleted = Category::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = Category::findOrFail($id);
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                break;

                case 'CUSTOMER_DATA':
                    $deleted = Customer::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = Customer::findOrFail($id);
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                break;

                case 'SUPPLIER_DATA':
                    $deleted = Supplier::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = Supplier::findOrFail($id);
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                break;

                case 'ORDER_DATA':
                    $deleted = Order::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = Order::findOrFail($id);
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
            switch($dataType):
                case 'USER_DATA':
                    $deleted = User::onlyTrashed()
                        ->where('id', $id)->first();
                    // $deleted->roles()->delete();
                    $deleted->forceDelete();
                break;

                case 'PRODUCT_DATA':
                    $deleted = Product::onlyTrashed()
                        ->where('id', $id)->first();
                        // $deleted->categories()->delete();
                    $deleted->forceDelete();
                break;

                case 'CATEGORY_DATA':
                    $deleted = Category::onlyTrashed()
                        ->where('id', $id)->first();
                        // $deleted->categories()->delete();
                    $deleted->forceDelete();
                break;

                case 'CUSTOMER_DATA':
                    $deleted = Customer::onlyTrashed()
                        ->where('id', $id)
                        ->first();
                    $deleted->forceDelete();
                break;

                case 'SUPPLIER_DATA':
                    $deleted = Supplier::onlyTrashed()
                        ->where('id', $id)->first();
                    $deleted->forceDelete();
                break;

                case 'ORDER_DATA':
                    $deleted = Order::onlyTrashed()
                        ->where('id', $id)->first();
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
            switch($type) {
                case 'USER_DATA':
                    $countTrash = User::onlyTrashed()
                        ->get();
                break;
                case 'PRODUCT_DATA':
                    $countTrash = Product::onlyTrashed()->get();
                break;
                case 'CATEGORY_DATA':
                    $countTrash = Category::onlyTrashed()->get();
                break;

                case 'CUSTOMER_DATA':
                    $countTrash = Customer::onlyTrashed()
                        ->get();
                break;

                case 'SUPPLIER_DATA':
                    $countTrash = Supplier::onlyTrashed()
                        ->get();
                break;

                case 'ORDER_DATA':
                    $countTrash = Order::onlyTrashed()
                        ->get();
                break;

                default:
                    $countTrash = [];
            }

            return response()
                ->json([
                    'message' => $type.' Trash',
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

            switch($type) {
                case "TOTAL_USER":
                    $totalData = User::whereNull('deleted_at')
                        ->get();
                    $totals = count($totalData);
                break;

                case 'TOTAL_PRODUCT':
                    $totalData = Product::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                    $productPercent = new ProductPercentage;
                    $Apel = $productPercent->getPercentage('Apel', $totals);
                    $Anggur = $productPercent->getPercentage('Anggur', $totals);
                    $Jeruk = $productPercent->getPercentage('Jeruk', $totals);
                    $Pear = $productPercent->getPercentage('Pear', $totals);
                    $Lengkeng = $productPercent->getPercentage('Lengkeng', $totals);
                break;

                case 'TOTAL_CATEGORY':
                    $totalData = Category::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                break;

                case 'TOTAL_CUSTOMER':
                    $totalData = Customer::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                break;

                case 'TOTAL_SUPPLIER':
                    $totalData = Supplier::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                break;

                case 'TOTAL_ORDER':
                    $totalData = Order::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                break;

                default:
                    $totalData = [];
            }

            if($type == "TOTAL_PRODUCT") {
                return response()
                ->json([
                    'message' => "Total {$type}",
                    'total' => $totals,
                    'percentage' => [
                        'Apel' => $Apel,
                        'Anggur' => $Anggur,
                        'Jeruk' => $Jeruk,
                        'Pear' => $Pear,
                        'Lengkeng' => $Lengkeng
                    ]
                ]);
            }

            return response()
            ->json([
                'message' => "Total {$type}",
                'total' => $totals
            ]);
        } catch(\Throwable $th) {
            throw $th;
        }
    }
}
