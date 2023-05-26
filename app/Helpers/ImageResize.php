<?php

/**
 * Register any authentication / authorization services.
 * @author puji ermanto<pujiermanto@gmail.com>
 * @return Illuminate\Support\Facades\Gate
 */

namespace App\Helpers;

use Image;

class ImageResize
{
    public function createThumbnail($path, $width, $height)
    {
        $img = Image::make($path)->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save($path);
    }
}
