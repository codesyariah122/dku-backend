<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Profile, Roles, Campaign, CategoryCampaign};

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // $this->call(AdministratorSeeder::class);
        // $this->call(ApiKeySeeder::class);
        // User::factory()->count(100)->create();
        // User::factory()
        // ->count(5)
        // ->has(Profile::factory())
        // ->create()
        // ->each(function ($user) {
        //     $user->roles()->sync(Roles::whereIn('id', [1, 2])->get());
        // });
        Campaign::factory()
        ->count(15)
        ->create()
        ->each(function ($campaign) {
            $campaign->category_campaigns()->sync(CategoryCampaign::whereIn('id', [1, 2, 3, 4])->get());
        });
    }
}
