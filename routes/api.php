<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WalletController;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

    Route::post('register',[UserController::class,'register']);
    Route::post('login',[UserController::class,'login']);
    Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');


    // Route::get('organizations',[OrganizationController::class,'index']);
    Route::post('organizations',[OrganizationController::class,'store'])->middleware('auth:sanctum');
    // Route::get('organizations/{organization}',[OrganizationController::class,'show']);


    Route::get('admin/organizations/pending',[AdminController::class,'pendingOrganizations'])->middleware('auth:sanctum','admin');
    Route::post('admin/organizations/{id}/approve',[AdminController::class,'approveOrganization'])->middleware('auth:sanctum','admin');
    Route::post('admin/organizations/{id}/reject',[AdminController::class,'rejectOrganization'])->middleware('auth:sanctum','admin');
    Route::post('admin/users/{id}/make-admin',[AdminController::class,'makeAdmin'])->middleware('auth:sanctum','admin');


    Route::post('create-project',[ProjectController::class,'create'])->middleware('auth:sanctum');
    Route::get('projects',[ProjectController::class,'getProjects']);
    Route::get('project/{project}',[ProjectController::class,'show']);






    Route::post('donate',[WalletController::class,'donate'])->middleware('auth:sanctum');
    
    // Route::post('wallets',[WalletController::class,'store'])->middleware('auth:sanctum');
    Route::get('wallets',[WalletController::class,'show'])->middleware('auth:sanctum');
    Route::get('admin/wallets',[WalletController::class,'getWalletUser'])->middleware('auth:sanctum','admin');

    Route::post('wallets/add-funds',[WalletController::class,'addFunds'])->middleware('auth:sanctum');
    Route::post('wallets/deduct-funds',[WalletController::class,'deductFunds'])->middleware('auth:sanctum');















// Route::get('users',[UserController::class, 'getAllUsers']);
// Route::post('users',[UserController::class,'store']);
// Route::put('users/{id}',[UserController::class,'update']);
// Route::get('users/{id}',[UserController::class,'showUser']);
// Route::delete('users/{id}',[UserController::class,'delete']);
// Route::post('profile',[ProfileController::class,'store']);
// Route::post('wallet',[WalletController::class,'store']);

// Route::get('users/{id}/profile',[UserController::class,'getUserProfile']);
// Route::get('users',[UserController::class,'getUsersWallet']);
?>
