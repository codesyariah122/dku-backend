<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User, Profile, Roles};

class CashierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cashier = new User;
        $cashier->name = "digta gina";
        $cashier->email = "digta.codot@gmail.com";
        $cashier->phone = "6288222668778";
        $cashier->password = Hash::make("Bismillah_123654");
        // $cashier->roles = json_encode(["OWNER"]);
        $cashier->status = "ACTIVE";
        $cashier->is_login = 0;
        $cashier->save();
        $cashier_profile = new Profile;
        $cashier_profile->username = trim(preg_replace('/\s+/', '_', strtolower($cashier->name)));
        $cashier_profile->photo = NULL;
        $cashier_profile->about = "Distributor buah import dengan kualitas terbaik di Surabaya.";
        $cashier_profile->address = 'Jl. Tengger Raya 3A/8 RT. 02  Kel. Kandangan. Kec. Benowo';
        $cashier_profile->city = 'Surabaya';
        $cashier_profile->district = 'Benowo';
        $cashier_profile->province = 'East Java';
        $cashier_profile->post_code = '60191';
        $cashier_profile->save();
        $cashier->profiles()->sync($cashier_profile->id);
        $this->command->info("User admin created successfully");
    }
}
