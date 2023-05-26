<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Profile;

class HomeController extends Controller
{
    public function index()
    {
        $owner = Profile::whereId(1)->with('users')->get();
        $context = [
            'seo' => [
                'title' => 'DKU WEB',
                'canonical' => 'https://dompetkebaikanumat.com',
            ],
            'web' => [
                'header' => 'Dompet Kebaikan Umat',
                'about' => 'Dompet Kebaikan Umat merupakan sebuah lembaga filantropi yang bergerak dalam penghimpunan dan pengelolaan dana sosial untuk membantu masyarakat kategori mustahik menjadi masyarakat sejahtera melalui pemberdayaan umat (Empowering Program) dan kemanusiaan. Pemberdayaan bergulir melalui pengelolaan dana infak, sedekah dan wakaf serta dana sosial lainnya yang terkelola secara modern dan Amanah. Bersama Anda #SahabatKU raih amalsholeh dengan jembatan kebaikan dalam menuntaskan permasalahan sosial seperti kesehatan, pendidikan, tanggap bencana, dakwah dan pemberdayaan..',
                'asset' => env('APP_URL')
            ],
            'user' => count($owner) > 0 ? $owner : null
        ];
        // return response()->json([
        //     env('APP_NAME'),
        //     $context['web']['about'],
        //     $context['user'][0]['address']
        // ]);
        return view('home.index');
    }
}
