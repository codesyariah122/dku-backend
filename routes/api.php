<?php

/**
 * @author: pujiermanto@gmail.com
 * @param SessionExpires at middleware
 * @param Flush Session Auto Logout
 * */

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\Api\Dashboard\{CategoryCampaignController, RoleUserManagementController, MenuManagementController, SubMenuManagementController, UserAccessMenuController, UserManagementController, CampaignManagementController};
use App\Http\Controllers\Api\Fitur\{WebFiturController, CampaignViewerController};


Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])->prefix('v1/fitur')->group(function () {

    // User profile
    Route::get('/user-profile', [LoginController::class, 'userProfile']);

    // User management
    Route::resource('/user-management', UserManagementController::class);
    Route::post('/update-user-with-photo/{id}', [UserManagementController::class, 'update_with_profile_picture']);

    // Edit profile user
    Route::put('/update-profile/{username}', [WebFiturController::class, 'update_user_profile']);

    // Upload photo
    Route::post('/upload-photo/{id}', [WebFiturController::class, 'upload_profile_picture']);

    // Category Campaign Management
    Route::resource('/category-campaigns-management', CategoryCampaignController::class);

    // Campaign Management
    Route::resource('/campaign-management', CampaignManagementController::class);
    Route::post('/update-campaign/{slug}', [CampaignManagementController::class, 'update']);

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
    Route::put('/campaign/{slug}', [CampaignViewerController::class, 'viewer']);
});


Route::prefix('v1/web')->group(function () {
    Route::get('/context', [WebFiturController::class, 'web_data']);
});