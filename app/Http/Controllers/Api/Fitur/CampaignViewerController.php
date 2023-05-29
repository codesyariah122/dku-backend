<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\{Campaign, CategoryCampaign, Viewer};
use App\Events\CampaignViewerEvent;
use App\Helpers\UserHelpers;

class CampaignViewerController extends Controller
{

    private $helpers;

    public function __construct()
    {
        $this->helpers = new UserHelpers;
    }

    public function viewer(Request $request, $slug)
    {
        try {
            $ip_address = $this->helpers->getIpAddr();
            // $ip_address = '103.147.8.112';
            $campaign = Campaign::whereSlug($slug)->firstOrFail();
            $check_viewer = Viewer::whereIpAddress($ip_address)->get();

            if(count($check_viewer) > 0) {
                $campaign_update = Campaign::findOrFail($campaign->id);
                // $campaign_update->views = $campaign->views + 1;
                // $campaign_update->save();
                // $update_viewer = Viewer::whereCampaignId($campaign_update->id)
                //                 ->firstOrFail();
                // $update_viewer->campaign_title = $campaign_update->title;
                // $update_viewer->save();

                $message = "You already as a viewers of campaign";
                $campaign_with_viewer = Campaign::with('viewers')->findOrFail($campaign_update->id);
            } else {
                $update_campaign_viewer = Campaign::findOrFail($campaign->id);
                $update_campaign_viewer->views+=1;
                $update_campaign_viewer->save();
                $check_viewer = Viewer::whereIpAddress($ip_address)->get();
                if(count($check_viewer) > 0) {
                    $update_viewer_first = Viewer::whereIpAddress($ip_address)->firstOrFail();
                    $update_viewer_first->campaign_title = $update_campaign_viewer->title;
                    $update_viewer_first->save();
                    $update_campaign_viewer->viewers()->sync($update_viewer_first->id);
                } else {                    
                    $add_viewer = new Viewer;
                    $add_viewer->campaign_title = $update_campaign_viewer->title;
                    $add_viewer->user_agent = $request->server('HTTP_USER_AGENT');
                    $add_viewer->ip_address = $ip_address;
                    $add_viewer->save();
                    $update_campaign_viewer->viewers()->sync($add_viewer->id);
                }

                $message = "New viewer added for campaign";
                $campaign_with_viewer = Campaign::with('viewers')
                                        ->findOrFail($update_campaign_viewer->id);
            }

            return response()->json([
                'success' => true,
                'message' => "{$message}, {$campaign->title}",
                'data' => $campaign_with_viewer
            ]);


        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }
}
