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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
