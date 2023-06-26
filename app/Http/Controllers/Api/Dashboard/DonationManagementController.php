<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{Donatur, Campaign, CategoryCampaign, Viewer, Bank};
use App\Events\{EventNotification, DataManagementEvent};
use App\Http\Resources\DonationManagementCollection;

class DonationManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            if($request->campaign_title) {
                $donations = Donatur::whereNull('deleted_at')
                ->whereHas('campaigns', function($query) use($request) {
                    return $query->whereTitle($request->campaign_title)->with('category_campaigns');
                })
                ->with('banks')
                ->with('campaigns')
                ->paginate(10);
            } elseif($request->start_date) {
                $startDate = $request->start_date;
                $endDate = $request->end_date;

                $donations = Donatur::whereNull('deleted_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with('banks')
                ->with('campaigns')
                ->paginate(10);
            } else {
                $donations = Donatur::whereNull('deleted_at')
                    ->with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->paginate(10);
            }


            return new DonationManagementCollection($donations);

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
        //
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
            $delete_donation = Donatur::whereNull('deleted_at')
                ->findOrFail($id);

            $delete_donation->delete();
            
            $data_event = [
                'type' => 'removed',
                'notif' => "Donatur, {$delete_donation->name}, success move to trash, please check trash!"
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "Donatur {$delete_donation->name} success move to trash, please check trash",
                'data' => $delete_donation
            ]);

        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function donation_accept(Request $request, $id)
    {
        try{
            $validator = Validator::make($request->all(), [
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }


            $donation_check = Donatur::with('campaigns')
                ->with('category_campaigns')
                ->with('banks')
                ->findOrFail($id);

            if($donation_check) {
                $accept_donation = Donatur::findOrFail($id);
                $accept_donation->status = $request->status;
                $accept_donation->save();                


                $donation_has_update = Donatur::with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->findOrFail($accept_donation->id);

                return response()->json([
                    'success' => true,
                    'message' => "Donation has been accept",
                    'data' => $donation_has_update
                ]);
            }
        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }
}
