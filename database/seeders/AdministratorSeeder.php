<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\{User, Profile, Roles};

class AdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function run()
    {
        $administrator = new User;
        $administrator->name = "super admin";
        $administrator->email = "dompetkebaikanumat@gmail.com";
        $administrator->phone = "6281770683568";
        // $administrator->roles = json_encode(["ADMIN"]);
        $administrator->password = Hash::make("Bismillah_123654");
        // $administrator->roles = json_encode(["OWNER"]);
        $administrator->status = "ACTIVE";
        $administrator->is_login = 0;
        $administrator->save();
        $administrator_profile = new Profile;
        $administrator_profile->username = trim(preg_replace('/\s+/', '_', strtolower($administrator->name)));
        $initial = $this->initials($administrator->name);
        $path = public_path().'/thumbnail_images/users/';
        $fontPath = public_path('fonts/Oliciy.ttf');
        $char = $initial;
        $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
        $dest = $path . $newAvatarName;

        $createAvatar = makeAvatar($fontPath, $dest, $char);
        $photo = $createAvatar == true ? $newAvatarName : '';
        $path_toSave = 'thumbnail_images/users/';
        // store into database field photo
        $administrator_profile->photo = $path_toSave . $photo;
        $administrator_profile->about = "Dompet Kebaikan Umat merupakan sebuah lembaga filantropi yang bergerak dalam penghimpunan dan pengelolaan dana sosial untuk membantu masyarakat kategori mustahik menjadi masyarakat sejahtera melalui pemberdayaan umat (Empowering Program) dan kemanusiaan. Pemberdayaan bergulir melalui pengelolaan dana infak, sedekah dan wakaf serta dana sosial lainnya yang terkelola secara modern dan Amanah. Bersama Anda #SahabatKU raih amalsholeh dengan jembatan kebaikan dalam menuntaskan permasalahan sosial seperti kesehatan, pendidikan, tanggap bencana, dakwah dan pemberdayaan..";
        $administrator_profile->address = 'Komplek Bandung Indah Raya C-5 No.20, RT.004/RW001, Mekarjaya, Kec. Rancasari, Kota Bandung, Jawa Barat 40292';
        $administrator_profile->city = 'Bandung';
        $administrator_profile->district = 'Rancasari';
        $administrator_profile->province = 'Jawa Barat';
        $administrator_profile->post_code = '40292';
        $administrator_profile->save();
        $administrator->profiles()->sync($administrator_profile->id);
        $roles = new Roles;
        $roles->name = json_encode(["ADMIN"]);
        $roles->save();
        $administrator->roles()->sync($roles->id);
        $update_user_role = User::findOrFail($administrator->id);
        $update_user_role->role = $roles->id;
        $update_user_role->save();
        $this->command->info("User admin created successfully");
    }
}
