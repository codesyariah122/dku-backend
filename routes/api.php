<?php
/**
 * @author: pujiermanto@gmail.com
 * @param SessionExpires at middleware
 * @param Flush Session Auto Logout
 * */

 namespace App\Http\Controllers\Api\Auth;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\Api\Dashboard\{RolesManagement, UserManagement, UserRoleManageController, MenuManagement, SubMenuManagement, UserAccessMenuController, ProductManagement, CategoryManagement, CustomerManagement, OrderManagement, SupplierManagement};

Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])->prefix('v1/fitur')->group(function () {
    Route::get('/user-login', [LoginController::class, 'userIsLogin']);

    // User management
    Route::resource('/user-management', UserManagement::class);

    // Trashed data
    Route::get('/trashed', [WebFiturController::class,
        'trash']);
    Route::put('/trashed/{id}', [WebFiturController::class, 'restoreTrash']);
    Route::delete('/trashed/{id}', [WebFiturController::class, 'deletePermanently']);

    // Role management
    Route::resource('/role-management', RolesManagement::class);

    // Barcode fitur
    Route::post('/barcode', [WebFiturController::class, 'barcode_fitur']);
    Route::post('/qrcode', [WebFiturController::class, 'qrcode_fitur']);

    // Trash data
    Route::get('/total-trash', [WebFiturController::class, 'totalTrash']);

    // Total Data
    Route::get('/total-data', [WebFiturController::class, 'totalData']);
});

Route::prefix('v1/auth')->group(function () {
    Route::post('/registration', [RegisterController::class, 'register']);
    Route::get('/user-inactive/{token}', [UserActivationTokenController::class, 'activation_data']);
    Route::put('/activation/{user_id}', [RegisterController::class, 'activation']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:api');

    // provider oauth
    Route::get('/redirect/{provider}', [RedirectProviderController::class, 'redirectToProvider']);

    Route::get('/{provider}/callback', [RedirectProviderController::class, 'handleProviderCallback']);
});

Route::prefix('v1')->group(function () {
    Route::get('/test', function () {
        return response()->json([
            'message' => 'test api'
        ]);
    });
});


Route::prefix('v1/web')->group(function () {
    Route::get('/context', [WebFiturController::class, 'web_data']);
});


// Fitur no authentication content
Route::post('/subscribe', [SubscriberController::class, 'subscribe']);
