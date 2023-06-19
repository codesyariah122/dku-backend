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
     * @author Puji Ermanto <pujiermanto@gmail.com>
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
        //     $user->roles()->sync(Roles::whereIn('id', [1])->get());
        // });
        Campaign::factory()
        ->count(5)
        ->create()
        ->each(function($campaign) {
            $users = User::whereRole(random_int(1, 2))->get();
            $campaign->category_campaigns()->sync(CategoryCampaign::whereId(random_int(1,8))->get());
            $campaign->users()->sync($users[0]->id);
        });
    }
}
