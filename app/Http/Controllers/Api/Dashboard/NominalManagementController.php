<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{Nominal};
use App\Events\{EventNotification, DataManagementEvent};
use App\Http\Resources\NominalManagementCollection;
use App\Helpers\WebFeatureHelpers;

class NominalManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $nominals = Nominal::paginate(10);
            return new NominalManagementCollection($nominals);
        } catch (\Throwable $th) {
            return response()->json([
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nominal' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $nominal_add = new Nominal;
            $nominal_add->nominal = $request->nominal;
            $nominal_add->save();

            $data_event = [
                'type' => 'added',
                'notif' => "{$nominal_add->nominal}, successfully added!"
            ];

            event(new DataManagementEvent($data_event));

            $saving_nominals = Nominal::all();

            return new NominalManagementCollection($saving_nominals);

        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
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
        try {
            $delete_nominal = Nominal::whereNull('deleted_at')
                ->findOrFail($id);
            
            $delete_nominal->delete();
            
            $data_event = [
                'type' => 'removed',
                'notif' => "Nominal {$delete_nominal->nominal}, success move to trash, please check trash!"
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "Nominal, {$delete_nominal->nominal} success move to trash, please check trash",
                'data' => $delete_nominal
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }
}
