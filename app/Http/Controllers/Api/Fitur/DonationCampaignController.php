<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{Donatur, Campaign, CategoryCampaign, Viewer, Bank, Nominal};
use App\Events\{EventNotification, DataManagementEvent};
use App\Helpers\{UserHelpers, WebFeatureHelpers};

class DonationCampaignController extends Controller
{
    private $helpers, $webfitur;

    public function __construct()
    {
        $this->helpers = new UserHelpers;
        $this->webfitur = new WebFeatureHelpers;
    }

    public function donation(Request $request, $slug)
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


            if($check_already_donation){
                $donation_hold = Donatur::whereEmail($request_data['email'])->get();

                if(count($donation_hold) === 0) {
                    $campaign_target = Campaign::with('category_campaigns')
                    ->with('users')
                    ->whereSlug($slug)
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

                    $new_donation->anonim = $request_data['anonim'];
                    $new_donation->status = 'PENDING';
                    $new_donation->unique_code = $this->webfitur->get_unicode();
                    $new_donation->methode = $request_data['methode'];
                    $new_donation->fundraiser = $request_data['user_id'] ? 'Y' : 'N';
                    $new_donation->note = $notes_data;
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
                ->with('donaturs', function($query) use ($request) {
                    $query->where('email', $request->email);
                })
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'image' => 'required',
                'email' => 'required|email',
                'donation_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $request_data = $request->all();

            if(count($donation_check->donaturs) > 0):
                if($donation_check->donaturs[0]->status === "PENDING" && $donation_check->donaturs[0]->image === NULL) {
                    $check_donation = Donatur::with('campaigns')
                    ->with('category_campaigns')
                    ->with('banks')
                    ->findOrFail($request_data['donation_id']);

                    if($check_donation->email === $request_data['email']) {
                        $update_donation = Donatur::findOrFail($check_donation->id);
                        $update_donation->transaction_id = Str::random(36);
                        $update_donation->status = 'HOLD';
                        $update_donation->expires_at = NULL;

                        if($request_data['image']) {
                            $image = $request->file('image');
                            $file = $image->store(trim(preg_replace('/\s+/', '', '/images/donaturs')), 'public');
                            $update_donation->image = $file;
                        }

                        $update_donation->save();

                        $data_event = [
                            'type' => 'pay-donation',
                            'notif' => "Terima kasih, donasi Anda akan segera di proses oleh Admin kami.",
                            'msg_donation' => "{$update_donation->name}, telah memproses pembayaran donasi!"
                        ];

                        event(new DataManagementEvent($data_event));


                        $donation_payment = Donatur::with('campaigns')
                            ->with('category_campaigns')
                            ->with('banks')
                            ->findOrFail($update_donation->id);

                        return response()->json([
                            'success' => true,
                            'message' => "Donasi Anda untuk campaign {$check_donation->campaigns[0]->title}, sedang di proses oleh Admin kami.",
                            'data' => $donation_payment
                        ]);   
                    } else {
                        return response()->json([
                        'not_relevant' => true,
                        'message' => "Data anda tidak sesuai dengan donasi manapun!"
                    ]);
                    }

                } else {
                    return response()->json([
                        'is_process' => true,
                        'message' => "Transaksi anda untuk campaign , {$donation_check->title}, sedang di proses Admin kami!"
                    ]);
                }
            else:
                return response()->json([
                    'not_found' => true,
                    'message' => "Data donatur yang anda cari tidak ditemukan!!"
                ]);
            endif;


        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

}
