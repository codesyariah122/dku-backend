<?php

/**
 * @author: pujiermanto@gmail.com
 * @param For Percentage Products with Relationship Table
 * */

namespace App\Helpers;

use App\Models\{User, Campaign};

class WebFeatureHelpers
{
    public function get_total_user($role)
    {
        switch ($role):
            case 'ADMIN':
                $total = User::whereNull('deleted_at')
                    ->whereRole(1)
                    ->get();
                return count($total);
                break;
            case 'AUTHOR':
                $total = User::whereNull('deleted_at')
                    ->whereRole(2)
                    ->get();
                return count($total);
                break;

            case 'USER':
                $total = User::whereNull('deleted_at')
                    ->whereRole(3)
                    ->get();
                return count($total);
                break;
            default:
                return 0;
        endswitch;
    }

    public function user_online()
    {
        $user_is_online = User::whereIsLogin(1)
            ->get();
        return count($user_is_online);
    }

    public function publish_campaign()
    {
        $publish_campaigns = Campaign::wherePublish(1)->get();
        return count($publish_campaigns);
    }

    public function most_viewed_campaign()
    {
        $result = [];
        $max_views = Campaign::max('views');
        $data_views = Campaign::whereViews($max_views)->get();
        foreach ($data_views as $view) {
            $result = [
                'title' => $view->title,
                'views' => $view->views,
            ];
        }
        return $result;
    }
}
