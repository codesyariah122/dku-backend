<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{Donatur, Campaign, CategoryCampaign, Viewer, Bank};
use App\Events\{EventNotification, DataManagementEvent};
use App\Helpers\UserHelpers;

class DonationCampaignController extends Controller
{
    private $helpers;

    public function __construct()
    {
        $this->helpers = new UserHelpers;
    }

    public function donation(Request $request, $slug)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email',
                'donation_amount' => 'required',
                'bank_id' => 'required',
                'methode' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $request_data = $request->all();

            $check_already_donation = Donatur::whereEmail($request_data['email'])->get();


            if($check_already_donation){
                $donation_hold = Donatur::whereEmail($request_data['email'])->get();

                if(count($donation_hold) === 0) {
                    $campaign_target = Campaign::with('category_campaigns')
                    ->with('users')
                    ->whereSlug($slug)
                    ->firstOrFail();
                    $bank_target = Bank::findOrFail($request_data['bank_id']);
                    $uniqueCode = mt_rand(50, 99);
                    $new_donation = new Donatur;
                    $new_donation->name = $request_data['name'];
                    $new_donation->email = $request_data['email'];
                    $new_donation->donation_amount = $request_data['donation_amount'] + $uniqueCode;
                    $new_donation->anonim = $request_data['anonim'];
                    $new_donation->status = 'PENDING';
                    $new_donation->unique_code = $uniqueCode;
                    $new_donation->methode = $request_data['methode'];
                    $new_donation->fundraiser = $request_data['user_id'] ? 'Y' : 'N';
                    $new_donation->campaign_id = $campaign_target->id;
                    $new_donation->category_campaign_id = $campaign_target->category_campaigns[0]->id;
                    $new_donation->bank_id = $request_data['bank_id'];
                    $new_donation->expires_at = Carbon::now()->addDay()->setTime(23, 0, 0)->format('Y-m-d H:i:s');

                    $new_donation->save();

                    $donatur = Donatur::findOrFail($new_donation->id);
                    $new_donation->campaigns()->sync($donatur->campaign_id);
                    $new_donation->category_campaigns()->sync($donatur->category_campaign_id);
                    $new_donation->banks()->sync($donatur->bank_id);

                    $saving_donations = Donatur::with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->findOrFail($new_donation->id);


                    $data_event = [
                        'type' => 'added',
                        'notif' => "Successfulle donation campaign : {$saving_donations->campaigns[0]->title}!"
                    ];

                    event(new DataManagementEvent($data_event));
                    $nominal = number_format($saving_donations->donation_amount, 0, ',',  '.');

                    return response()->json([
                        'success' => true,
                        'message' => "Lanjutkan donasi dengan transfer ke rekening: {$saving_donations->banks[0]->norek} , jumlah donasi : Rp. {$nominal}, sesuaikan nominal hingga 3 digit terakhir, selanjutnya upload bukti transfer!",
                        'data' => $saving_donations
                    ]);

                } else {
                    $donation_ready_hold = Donatur::with('campaigns')
                        ->with('category_campaigns')
                        ->with('banks')
                        ->whereEmail($request_data['email'])
                        ->where('status', 'PAID')
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

    public function donation_payment(Request $request, $slug)
    {
        try {
            $donation_check = Campaign::with('donaturs.banks')
                ->with('category_campaigns')
                ->whereSlug($slug)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'image' => 'required',
                'email' => 'required',
                'donation_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $request_data = $request->all();

            if($donation_check->donaturs[0]->email === $request_data['email'] && $donation_check->donaturs[0]->status === "PENDING" && $donation_check->donaturs[0]->image === NULL) {
                $check_donation = Donatur::with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->findOrFail($request_data['donation_id']);

                $update_donation = Donatur::findOrFail($check_donation->id);
                $update_donation->transaction_id = Str::random(12);
                $update_donation->expires_at = NULL;

                if($request_data['image']) {
                    $image = $request->file('image');
                    $file = $image->store(trim(preg_replace('/\s+/', '', '/images/donaturs')), 'public');
                    $update_donation->image = $file;
                }

                $update_donation->save();

                $data_event = [
                    'type' => 'added',
                    'notif' => "Your donation has been process!"
                ];

                event(new DataManagementEvent($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Donasi Anda untuk campaign {$check_donation->campaigns[0]->title}, sedang di proses oleh Admin kami.",
                    'data' => $check_donation
                ]);
            }

            return response()->json([
                'process' => true,
                'message' => "Admin kami sedang memproses status donasi Anda, mohon ditunggu!!",
                'data' => $donation_check
            ]);


        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

}
