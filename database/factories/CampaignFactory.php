<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\{User, Campaign};
use App\Helpers\WebFeatureHelpers;

class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */

    protected $model = Campaign::class;
    protected $feature_helpers = '';
    
    public function definition()
    {
        $user = User::findOrFail(2);

        $faker = Faker::create();
        $title = $faker->sentence();
        $content = $faker->realText(200, 2);
        $htmlContent = "<p>".str_replace("\n", "</p><p>", $content). "</p>";

        // Mendapatkan timestamp saat ini
        $currentTimestamp = Carbon::now()->timestamp;

        // Menambahkan satu bulan ke timestamp saat ini
        $nextMonthTimestamp = Carbon::createFromTimestamp($currentTimestamp)->addMonth()->timestamp;

        // Membuat objek Carbon dari timestamp 1 bulan ke depan
        $nextMonthDate = Carbon::createFromTimestamp($nextMonthTimestamp);

        // Mengatur tanggalnya menjadi 1
        $nextMonthDate->day = 1;


        $campaigin_link = env('FRONTEND_APP')."/campaign/".Str::slug($title);

        $feature_helpers = new WebFeatureHelpers;

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => htmlspecialchars($htmlContent),
            'donation_target' => 150000000,
            'is_headline' => 'N',
            'banner' => NULL,
            'publish' => 'Y',
            'end_campaign' => $nextMonthDate->format('Y-m-d'),
            'barcode' => $feature_helpers->generateQrCode($campaigin_link),
            'created_by' => $user->name,
            'author' => $user->name,
            'author_email' => $user->email,
            'without_limit' => 'N'
        ];
    }
}
