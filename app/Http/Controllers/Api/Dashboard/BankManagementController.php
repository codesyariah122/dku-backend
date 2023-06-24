<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\{Bank};
use App\Events\{EventNotification, DataManagementEvent};
use App\Http\Resources\BankManagementCollection;
use App\Helpers\WebFeatureHelpers;

class BankManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(function ($request, $next) {
            if (Gate::allows('campaigns-management')) return $next($request);
            return response()->json([
                'error' => true,
                'message' => 'Anda tidak memiliki cukup hak akses'
            ]);
        });

        $this->feature_helpers = new WebFeatureHelpers;
    }

    public function index()
    {
        try {

            $banks = Bank::paginate(10);

            return new BankManagementCollection($banks);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
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
            $data = $request->all();

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'norek' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $new_bank = new Bank;
            $new_bank->name = $data['name'];
            $new_bank->norek = $data['norek'];
            $new_bank->owner = $data['owner'];
            $new_bank->product_code = $data['product_code'];
            $new_bank->bank_code = $data['bank_code'];
            $new_bank->status = $data['status'];
            $new_bank->type = $data['type'];

            if($request->file('image')) {
                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();

                $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                $thumbPath = public_path() . '/thumbnail_images/banks/' . $filenametostore;
                Image::make($thumbImage)->save($thumbPath);
                $new_bank->image = "thumbnail_images/banks/" . $filenametostore;
            }
            $new_bank->save();

            $data_event = [
                'type' => 'added',
                'notif' => "{$new_bank->name}, successfully added!"
            ];

            event(new DataManagementEvent($data_event));

            $saving_banks = Bank::whereId($new_bank->id)
                ->get();

            return new BankManagementCollection($saving_banks);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => "Error : ".$th->getMessage()
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
        //
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
            $update_bank = Bank::findOrFail($id);


            // var_dump($category_campaign->id); die;

            $update_bank->name = $request->name ? $request->name : $update_bank->name;
            $update_bank->norek = $request->norek ? $request->norek : $update_bank->norek;
            $update_bank->owner = $request->owner ? $request->owner : $update_bank->owner;
            $update_bank->status = $request->status ? $request->status : $update_bank->status;
            $update_bank->type = $request->type ? $request->type : $update_bank->type;

            if ($request->file('image')) {

                if ($update_bank->image !== "" && $update_bank->image !== NULL) {
                    $old_image = public_path() . '/' . $update_bank->image;
                    $file_exists = public_path() . '/' . $update_bank->image;

                    if($old_image && file_exists($file_exists)) {
                        unlink($old_image);
                    }
                }

                $image = $request->file('image');
                $extension = $request->file('image')->getClientOriginalExtension();

                $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                $thumbPath = public_path() . '/thumbnail_images/banks/' . $filenametostore;

                Image::make($thumbImage)->save($thumbPath);

                $update_bank->image = "thumbnail_images/banks/" . $filenametostore;
            } else {
                $update_bank->image = $update_bank->image;
            }

            $update_bank->save();


            $data_event = [
                'type' => 'updated',
                'notif' => "{$update_bank->name}, successfully update!"
            ];

            event(new DataManagementEvent($data_event));

            $saving_campaigns = Bank::whereId($update_bank->id)
                ->get();

            return new BankManagementCollection($saving_campaigns);

        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => "Error {$th->getMessage()}"
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $delete_bank = Bank::whereNull('deleted_at')
                ->findOrFail($id);
            
            $delete_bank->delete();
            
            $data_event = [
                'type' => 'removed',
                'notif' => "{$delete_bank->name}, success move to trash, please check trash!"
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "Bank, {$delete_bank->name} success move to trash, please check trash",
                'data' => $delete_bank
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
