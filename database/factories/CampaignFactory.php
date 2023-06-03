<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use App\Models\{User, Campaign};

class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Campaign::class;

    public function definition()
    {
        $user = User::findOrFail(3);

        $faker = Faker::create('id_ID');
        $title = $this->faker->sentence(5);
        $content = $faker->realText(200, 2); 
        $htmlContent = "<p>" . str_replace("\n", "</p><p>", $content) . "</p>";
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => htmlspecialchars($htmlContent),
            'donation_target' => 150000000,
            'is_headline' => 'N',
            'banner' => NULL,
            'publish' => 'Y',
            'created_by' => $user->name,
            'author' => $user->name,
            'author_email' => $user->email,
            'without_limit' => 'N'
        ];
    }
}
