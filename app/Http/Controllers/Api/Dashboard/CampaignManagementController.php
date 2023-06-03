<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\{Campaign, CategoryCampaign, User};
use App\Events\{EventNotification, DataManagementEvent};

class CampaignManagementController extends Controller
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
    }

    public function index()
    {
        try {
            $campaigns = Campaign::whereNull('deleted_at')
                ->orderBy('id', 'DESC')
                ->paginate(10);
            
            return response()->json([
                'message' => 'List data campaigns',
                'data' => $campaigns
            ]);
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
            // var_dump($check_already_campaign);
            // die;
            if (count($check_already_campaign) > 0) {
                return response()->json([
                    'message' => "{$req['title']}, is already been taken!!"
                ]);
            }

            $new_campaign = new Campaign;
            $new_campaign->title = $req['title'];
            $new_campaign->slug = Str::slug(strtolower($req['title']));
            $new_campaign->description = $req['description'];
            $new_campaign->donation_target = $req['donation_target'];
            $new_campaign->is_headline = $req['is_headline'];

            if ($request->file('banner')) {
                $image = $request->file('banner');
                $file = $image->store(trim(preg_replace('/\s+/', '', '/images/campaigns')), 'public');
                $new_campaign->banner = $file;
            }
            $new_campaign->publish = 'Y';
            $new_campaign->author = $request->user()->name;
            $new_campaign->author_email = $request->user()->email;
            $new_campaign->without_limit = $req['without_limit'];
            $new_campaign->save();

            $category_campaign = CategoryCampaign::findOrFail($req['category_campaign']);
            $campaign_user = User::findOrFail($request->user()->id);

            $new_campaign->category_campaigns()->sync($category_campaign->id);
            $new_campaign->users()->sync($campaign_user->id);

            $campaign_barcode = Campaign::findOrFail($new_campaign->id);
            $campaign_barcode->barcode = $new_campaign->id > 9 ? "CAMPAIGN-0{$new_campaign->id}" : "CAMPAIGN-00{$new_campaign->id}";
            $campaign_barcode->save();

            $data_event = [
                'type' => 'added',
                'notif' => "{$new_campaign->title}, berhasil ditambahkan!",
                'data' => $new_campaign
            ];

            event(new DataManagementEvent($data_event));

            $saving_campaigns = Campaign::with('category_campaigns')
                ->with('users')
                ->findOrFail($new_campaign->id);

            return response()->json([
                'message' => 'added new campaign successfully',
                'data' => $saving_campaigns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
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
            $delete_category = Campaign::findOrFail($id);
            $delete_category->delete();
            $data_event = [
                'type' => 'removed',
                'notif' => "{$delete_category->name}, success move to trash, please check trash!",
                'data' => $delete_category
            ];

            event(new DataManagementEvent($data_event));

            return response()->json([
                'success' => true,
                'message' => "Category Campaign {$delete_category->name} success move to trash, please check trash",
                'data' => $delete_category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
