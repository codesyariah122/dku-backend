<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as Faker;
use App\Models\Profile;

class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Profile::class;

    public function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function makeInitialAvatar($name)
    {
        $initial = trim(preg_replace('/\s+/', '_', strtolower($name).time()));
        $path = public_path() . '/thumbnail_images/users/';
        $fontPath = public_path('fonts/Oliciy.ttf');
        $char = $initial;
        $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
        $dest = $path . $newAvatarName;

        $createAvatar = makeAvatar($fontPath, $dest, $char);

        $photo = $createAvatar == true ? $newAvatarName : '';
                    // store into database field photo
        $save_path = 'thumbnail_images/users/';
        $new_profile->photo = $save_path . $photo;
    }

    public function definition()
    {
        $faker = Faker::create('id_ID');

        return [
            'username' => $faker->userName,
            'photo' => NULL,
            'about' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
            tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
            quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
            consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
            cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
            proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
        ];
    }
}
