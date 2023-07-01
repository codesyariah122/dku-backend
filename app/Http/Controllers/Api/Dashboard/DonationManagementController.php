<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{Donatur, Campaign, CategoryCampaign, Viewer, Bank, Nominal};
use App\Events\{EventNotification, DataManagementEvent};
use App\Http\Resources\DonationManagementCollection;
use App\Helpers\{UserHelpers, WebFeatureHelpers};

class DonationManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $helpers, $webfitur;

    public function __construct()
    {
        $this->helpers = new UserHelpers;
        $this->webfitur = new WebFeatureHelpers;
    }

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
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email',
                'nominal_donation' => 'required',
                'bank_id' => 'required',
                'methode' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $request_data = $request->all();

            $check_already_donation = Donatur::whereEmail($request_data['email'])->get();


            if($check_already_donation) {
                $donation_hold = Donatur::whereEmail($request_data['email'])->get();
            // var_dump(count($donation_hold)); die;

                if(count($donation_hold) === 0) {
                    $campaign_target = Campaign::with('category_campaigns')
                    ->with('users')
                    ->where('id', $request_data['campaign_id'])
                    ->firstOrFail();

                    $nominal_donation = Nominal::findOrFail($request_data['nominal_donation']);

                    $notes_data = json_encode([
                        'name' => $request_data['name'],
                        'email' => $request_data['email'],
                        'nominal_donation' => $nominal_donation->nominal,
                        'anonim' => $request_data['anonim'],
                        'methode' => $request_data['methode'],
                        'created' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    $bank_target = Bank::findOrFail($request_data['bank_id']);
                    
                    $new_donation = new Donatur;
                    $new_donation->name = $request_data['name'];
                    $new_donation->email = $request_data['email'];

                    $new_donation->anonim = $request_data['anonim'] ? $request_data['anonim'] : 'N';
                    $new_donation->status = $request_data['status'] ? $request_data['status'] : 'PENDING';
                    $new_donation->unique_code = $this->webfitur->get_unicode();
                    $new_donation->methode = $request_data['methode'];
                    $new_donation->fundraiser = $request_data['user_id'] !== NULL ? 'Y' : 'N';
                    $new_donation->note = $notes_data;
                    $new_donation->message = $request_data['message'];
                    $new_donation->campaign_id = $campaign_target->id;
                    $new_donation->category_campaign_id = $campaign_target->category_campaigns[0]->id;
                    $new_donation->bank_id = $request_data['bank_id'];
                    $new_donation->expires_at = Carbon::now()->addDay()->setTime(23, 0, 0)->format('Y-m-d H:i:s');

                    $new_donation->save();

                    $donatur = Donatur::findOrFail($new_donation->id);
                    
                    $donation_amount = Donatur::findOrFail($new_donation->id);
                    $donatur->donation_amount = $nominal_donation->nominal + $donatur->unique_code;
                    $donatur->save();

                    $new_donation->campaigns()->sync($donatur->campaign_id);
                    $new_donation->category_campaigns()->sync($donatur->category_campaign_id);
                    $new_donation->banks()->sync($donatur->bank_id);
                    $new_donation->nominals()->sync($request_data['nominal_donation']);

                    $saving_donations = Donatur::with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->with('nominals')
                    ->findOrFail($new_donation->id);


                    $data_event = [
                        'type' => 'process-donation',
                        'notif' => "Successfully donation campaign : {$saving_donations->campaigns[0]->title}!",
                        "msg_donation" => "{$new_donation->name}, telah berdonasi untuk campaign : {$saving_donations->campaigns[0]->title}"
                    ];

                    event(new DataManagementEvent($data_event));
                    $nominal = number_format($saving_donations->donation_amount, 0, ',',  '.');

                    return response()->json([
                        'success' => true,
                        'message' => "Lanjutkan donasi dengan transfer ke rekening: {$saving_donations->banks[0]->norek} , jumlah donasi : Rp. {$nominal}, sesuaikan nominal hingga 3 digit terakhir, selanjutnya upload bukti transfer!",
                        'data' => $saving_donations
                    ]);

                } else {
                    // var_dump("Kadie atuh"); die;
                    $donation_ready_hold = Donatur::with('campaigns')
                        ->with('category_campaigns')
                        ->with('banks')
                        ->whereEmail($request_data['email'])
                        ->where('status', 'PENDING')
                        ->firstOrFail();
                    $nominal = number_format($donation_ready_hold->donation_amount, 0, ',',  '.');

                    return response()->json([
                        'error' => true,
                        'message' => "Silahkan selesaikan terlebih dahulu donasi anda sebelumnya, dengan melakukan transfer ke rekening : {$donation_ready_hold->banks[0]->norek}, dengan nominal : Rp. {$nominal}, sebelum {$donation_ready_hold->expires_at}!",
                        'data' => $donation_ready_hold
                    ]);
                }

            }

        } catch (\Throwable $th) {
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

    public function donation_accept(Request $request, $transaction_id)
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
                ->where('transaction_id', $transaction_id)
                ->firstOrFail();

            if($donation_check->transaction_id) {
                $accept_donation = Donatur::where('transaction_id', $transaction_id)
                    ->firstOrFail();
                $accept_donation->status = $request->status;
                $accept_donation->save();

                $donation_has_update = Donatur::with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->findOrFail($accept_donation->id);

                $donation_campaign = Campaign::with('category_campaigns')
                    ->findOrFail($donation_has_update->campaigns[0]->id);

                $donation_campaign->total_trf = $donation_campaign->total_trf + $accept_donation->donation_amount;
                $donation_campaign->save();

                $data_event = [
                    'type' => 'accept-donation',
                    'notif' => "Donatur, {$accept_donation->name}, has been successfully transaction!",
                    'msg_donation' => "Donator, {$accept_donation->name}, telah menyelesaikan transaksi donasi"
                ];

                event(new DataManagementEvent($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Donation has been accept",
                    'data' => $donation_has_update
                ]);
            } else {
                var_dump("Error"); die;
            }
        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }
}
