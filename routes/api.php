<?php

/**
 * @author: pujiermanto@gmail.com
 * @param SessionExpires at middleware
 * @param Flush Session Auto Logout
 * */

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriberController;

use App\Http\Controllers\Api\Dashboard\
{
    CategoryCampaignController, 
    RoleUserManagementController, 
    MenuManagementController, 
    SubMenuManagementController, 
    UserAccessMenuController, 
    UserManagementController, 
    CampaignManagementController, 
    BankManagementController, 
    DonationManagementController, 
    NominalManagementController
};

use App\Http\Controllers\Api\Fitur\
{
    WebFiturController, CampaignViewerController, DonationCampaignController
};


Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])->prefix('v1/fitur')->group(function () {

    // User profile
    Route::get('/user-profile', [LoginController::class, 'user_profile']);

    // User management
    Route::resource('/user-management', UserManagementController::class);
    Route::post('/update-user-with-photo/{id}', [UserManagementController::class, 'update_with_profile_picture']);

    // Change user password
    Route::post('/change-password', [WebFiturController::class, 'change_password']);

    // Edit profile user
    Route::put('/update-profile', [WebFiturController::class, 'update_user_profile']);

    // Update & Upload photo
    Route::post('/upload-photo', [WebFiturController::class, 'upload_profile_picture']);

    // Category Campaign Management
    Route::resource('/category-campaigns-management', CategoryCampaignController::class);

    // Campaign Management
    Route::resource('/campaign-management', CampaignManagementController::class);
    Route::post('/update-campaign/{slug}', [CampaignManagementController::class, 'update']);
    // Export campaign data
    Route::get('/campaign-data/download', [WebFiturController::class, 'campaign_data_download']);

    // Bank Management
    Route::resource('/bank-management',BankManagementController::class);
    Route::post('/update-bank/{id}', [BankManagementController::class, 'update']);


    // Role management
    Route::resource('/roles-management', RoleUserManagementController::class);
    // Menu Management
    Route::resource('/menu-management', MenuManagementController::class);
    Route::resource('/submenu-management', SubMenuManagementController::class);

    // Trashed data
    Route::get('/trashed', [WebFiturController::class, 'trash']);
    Route::put('/trashed/{id}', [WebFiturController::class, 'restoreTrash']);
    Route::delete('/trashed/{id}', [WebFiturController::class, 'deletePermanently']);


    // Barcode fitur
    Route::post('/barcode', [WebFiturController::class, 'barcode_fitur']);
    Route::post('/qrcode', [WebFiturController::class, 'qrcode_fitur']);

    // Trash data
    Route::get('/total-trash', [WebFiturController::class, 'totalTrash']);

    // Total Data
    Route::get('/total-data', [WebFiturController::class, 'totalData']);

    // User is online
    Route::get('/user-online', [WebFiturController::class, 'user_is_online']);

    // Any user have list menu
    Route::get('/access-menu', [UserAccessMenuController::class, 'access_menu_list']);

    // Nominal donation management
    Route::resource('/nominal-management', NominalManagementController::class);

    // Donation management
    Route::resource('/donation-management', DonationManagementController::class);
    // Donation accept
    Route::put('/donations-accept/{transaction_id}', [DonationManagementController::class, 'donation_accept']);
});

Route::middleware(['cors'])->prefix('v1/auth')->group(function () {
    Route::post('/registration', [RegisterController::class, 'register']);
    Route::get('/user-inactive/{token}', [UserActivationTokenController::class, 'activation_data']);
    Route::put('/activation/{user_id}', [RegisterController::class, 'activation']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:api');

    // User donation login
    Route::post('/user-dontaion/login', [LoginController::class, 'user_donation_login']);

    // provider oauth
    Route::get('/redirect/{provider}', [RedirectProviderController::class, 'redirectToProvider']);

    Route::get('/{provider}/callback', [RedirectProviderController::class, 'handleProviderCallback']);
});

Route::middleware('cors')->prefix('v1')->group(function () {
    
    Route::post('/subscribe', [SubscriberController::class, 'subscribe']);

    Route::get('/test', function () {
        return response()->json([
            'message' => 'test api'
        ]);
    });

    // Viewer campaign by slug
    Route::put('/campaign/{slug}', [CampaignViewerController::class, 'viewer']);

    // Donation user
    Route::post('/donations/{slug}', [DonationCampaignController::class, 'donation']);
    Route::post('/donations-payment/{slug}', [DonationCampaignController::class, 'donation_payment']);


    // unicode
    Route::get('/uniqcode', [WebFiturController::class, 'get_unique_code']);
    // List of nominal donations
    Route::get('/lists-nominal-donation', [WebFiturController::class, 'get_nominal_lists']);
});


Route::prefix('v1/web')->group(function () {
    Route::get('/context', [WebFiturController::class, 'web_data']);
});