<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\FeatureHelpers;
use Auth;
use App\Models\User;
use App\Models\Profile;
use App\Models\Login;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];
    protected $helpers = [];
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */

    public function __contstruct($data)
    {
        $this->helpers = $data;
    }

    public function set_data()
    {
        $gate_data = [
            'user-management',
            'donatur',
            'campaign',
            'blog',
        ];
        self::__contstruct($gate_data);
    }

    public function boot(Request $request)
    {
        $this->registerPolicies();

        Passport::routes();

        self::set_data();

        $gates = new FeatureHelpers($this->helpers);

        $gates->GatesAccess();
    }
}
