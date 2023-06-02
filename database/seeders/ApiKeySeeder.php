<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ApiKeys;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereName('super admin')->firstOrFail();
        $token = new ApiKeys;
        $token->user_id = $user->id;
        $token->token = Str::random(32);
        $token->save();
        $this->command->info("Token has been created");
    }
}
