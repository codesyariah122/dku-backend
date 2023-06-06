<?php

/**
 * @author: pujiermanto@gmail.com
 * @param For Percentage Campaigns with Relationship Table
 * */

namespace App\Helpers;
use Picqer\Barcode\BarcodeGeneratorHTML;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
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
        $highestViews = Campaign::max('views');
        $records = Campaign::where('views', $highestViews)
        ->with('viewers')
        ->orderBy('created_at', 'desc')
        ->limit(1)
        ->get();
        
        $result = [
            'title' => $records[0]->title,
            'views' => $records[0]->views,
            'data' => $records
        ];

        return $result;
    }


    public function generateBarcode($data)
    {
        $generator = new BarcodeGeneratorHTML();
        $barcodeHtml = $generator->getBarcode($data, $generator::TYPE_CODE_128);

        return $barcodeHtml;
    }


    public function generateQrCode($data)
    {
        $qr = new DNS2D;
        $qrCode = $qr->getBarcodeHTML($data, 'QRCODE', 4,4);

        return $qrCode;
    }

}
