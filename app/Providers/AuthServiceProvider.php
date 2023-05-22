<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\FeatureHelpers;
use Auth;
use App\Models\{User, Profile, Login, Menu};

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
     * @author puji ermanto<pujiermanto@gmail.com>
     * @return void
     */

    public function __contstruct($data)
    {
        $this->helpers = $data;
    }

    /**
     * @author puji ermanto<pujiermanto@gmail.com
     * @return authentication
     * @todo
     * - Grab database for roles permissions
     * - Permissions access list from database
     */
    public function set_data()
    {
        $gate_data = [
            'user-management',
            'category-campaigns-management',
            'campaign-management',
            'roles-management',
            'menu-management',
            'submenu-management',
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
