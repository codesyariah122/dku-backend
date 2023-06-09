<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Image;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\{Campaign, CategoryCampaign, User};
use App\Events\{EventNotification, DataManagementEvent};
use App\Http\Resources\CampaignManagementCollection;
use App\Helpers\WebFeatureHelpers;

class CampaignManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $feature_helpers;

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


    public function index(Request $request)
    {
        try {

            if($request->title) {
                $campaigns = Campaign::whereNull('deleted_at')
                    ->orderBy('id', 'DESC')
                    ->with('users')
                    ->with('category_campaigns')
                    ->where('title', 'like', '%' . $request->title . '%')
                    ->paginate(10);
            } elseif($request->category_campaign) {
                $campaigns = Campaign::whereNull('deleted_at')
                    ->orderBy('id', 'DESC')
                    ->with('users')
                    ->with('category_campaigns')
                    ->whereHas('category_campaigns', function ($query) use ($request) {
                        $query->where('category_campaigns.id', $request->category_campaign);
                    })
                    ->paginate(10);
            } elseif($request->start_date) {
                $startDate = $request->start_date;
                $endDate = $request->end_date;
                // var_dump($startDate); die;
                $campaigns = Campaign::whereNull('deleted_at')
                    ->orderBy('id', 'DESC')
                    ->with('users')
                    ->with('category_campaigns')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->paginate(10);
            }else {
                $campaigns = Campaign::whereNull('deleted_at')
                    ->orderBy('id', 'DESC')
                    ->with('users')
                    ->with('category_campaigns')
                    ->paginate(10);
            }

            return new CampaignManagementCollection($campaigns);

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
                'title' => 'required',
                'description' => 'required',
                'donation_target' => 'required',
                'is_headline' => 'required',
                'without_limit' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $req = [
                'title' => $request->title,
                'description' => htmlspecialchars($request->description),
                'donation_target' => $request->donation_target,
                'is_headline' => $request->is_headline,
                'without_limit' => $request->without_limit,
                'category_campaign' => $request->category_campaign
            ];

            $check_already_campaign = Campaign::whereTitle($req['title'])->get();

            if (count($check_already_campaign) > 0) {
                return response()->json([
                    'message' => "{$req['title']}, is already been taken!!"
                ]);
            }

            $new_campaign = new Campaign;
            $new_campaign->title = $req['title'];
            $new_campaign->slug = $request->slug ? $request->slug : Str::slug(strtolower($req['title']));
            $new_campaign->description = $req['description'];
            $new_campaign->donation_target = $req['donation_target'];
            $new_campaign->is_headline = $req['is_headline'];

            if ($request->file('banner')) {
                $image = $request->file('banner');
                $file = $image->store(trim(preg_replace('/\s+/', '', '/images/campaigns')), 'public');
                $new_campaign->banner = $file;
            }

            $new_campaign->publish = $request->publish ? $request->publish : 'Y';
           
            $new_campaign->end_campaign = Carbon::createFromTimestamp($request->end_campaign)->toDateTimeString();

            // $new_campaign->barcode = $this->feature_helpers->generateBarcode($request->slug ? $request->slug : Str::slug(strtolower($req['title'])));
            $new_campaign->created_by = $request->user()->name;
            $new_campaign->author = $request->user()->name;
            $new_campaign->author_email = $request->user()->email;
            $new_campaign->without_limit = $req['without_limit'];
            $new_campaign->save();

            $category_campaign = CategoryCampaign::findOrFail($req['category_campaign']);
            $campaign_user = User::findOrFail($request->user()->id);

            $new_campaign->category_campaigns()->sync($category_campaign->id);
            $new_campaign->users()->sync($campaign_user->id);

            $campaign_barcode = Campaign::findOrFail($new_campaign->id);
            $campaigin_link = env('FRONTEND_APP')."/campaign/".$new_campaign->slug;
            $campaign_barcode->barcode = $this->feature_helpers->generateQrCode($campaigin_link);
            $campaign_barcode->save();

            $data_event = [
                'type' => 'added',
                'notif' => "{$new_campaign->title}, successfully added!"
            ];

            event(new DataManagementEvent($data_event));

            $saving_campaigns = Campaign::with('users')
                ->with('category_campaigns')
                ->whereId($new_campaign->id)
                ->get();

            return new CampaignManagementCollection($saving_campaigns);
            
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
    public function show($slug)
    {
        try {
            $campaign_detail = Campaign::with('category_campaigns')
                ->with('users')
                ->whereSlug($slug)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => "Detail Campaign {$campaign_detail->title}.",
                'data' => $campaign_detail
            ]);
        } catch(\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
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
    public function update(Request $request, $slug)
    {
        try {
            $campaign_data = Campaign::with('category_campaigns')
                ->whereSlug($slug)
                ->firstOrFail();

            $category_campaign = CategoryCampaign::findOrFail($campaign_data->category_campaigns[0]->id);

            $update_campaign = Campaign::with('category_campaigns')
                ->findOrFail($campaign_data->id);


            // var_dump($category_campaign->id); die;

            $update_campaign->title = $request->title ? $request->title : $update_campaign->title;
            $update_campaign->slug = $request->slug ? $request->slug : $update_campaign->slug;
            $update_campaign->description = $request->description ? $request->description : $update_campaign->description;
            $update_campaign->donation_target = $request->donation_target ? $request->donation_target : $update_campaign->donation_target;
            $update_campaign->is_headline = $request->is_headline ? $request->is_headline : $update_campaign->is_headline;
            $update_campaign->publish = $request->publish ? $request->publish : $update_campaign->publish;
            $update_campaign->end_campaign = $request->end_campaign ? Carbon::createFromTimestamp($request->end_campaign)->toDateTimeString() : $update_campaign->end_campaign;

            if ($request->file('banner')) {
                // Deleted old storage
                $file_path = $update_campaign->banner;
                if (Storage::exists($file_path)) {
                    Storage::disk('public')->delete($file_path);
                }

                $image = $request->file('banner');
                $file = $image->store(trim(preg_replace('/\s+/', '', '/images/campaigns')), 'public');
                $update_campaign->banner = $file;
            } else {
                $update_campaign->banner = $update_campaign->banner;
            }

            $update_campaign->save();

            $update_campaign->category_campaigns()->sync($category_campaign->id);


            $data_event = [
                'type' => 'updated',
                'notif' => "{$update_campaign->title}, successfully update!"
            ];

            event(new DataManagementEvent($data_event));

            $saving_campaigns = Campaign::with('users')
                ->with('category_campaigns')
                ->whereId($update_campaign->id)
                ->get();

            return new CampaignManagementCollection($saving_campaigns);

            // return response()->json([
            //     'success' => true,
            //     'message' => "{$update_campaign->title}, successfully update!",
            //     'data' => $saving_campaigns
            // ]);

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
            $delete_campaign = Campaign::whereNull('deleted_at')
                ->findOrFail($id);
            
            $delete_campaign->delete();
            
            $data_event = [
                'type' => 'removed',
                'notif' => "{$delete_campaign->title}, success move to trash, please check trash!"
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "Campaign {$delete_campaign->title} success move to trash, please check trash",
                'data' => $delete_campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
