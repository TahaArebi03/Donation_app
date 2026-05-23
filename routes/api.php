<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DonationController;
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


    Route::post('organizations',[OrganizationController::class,'store'])->middleware('auth:sanctum');


    Route::get('admin/organizations/pending',[AdminController::class,'pendingOrganizations'])->middleware('auth:sanctum','admin');
    Route::post('admin/organizations/{id}/approve',[AdminController::class,'approveOrganization'])->middleware('auth:sanctum','admin');
    Route::post('admin/organizations/{id}/reject',[AdminController::class,'rejectOrganization'])->middleware('auth:sanctum','admin');
    Route::post('admin/users/{id}/make-admin',[AdminController::class,'makeAdmin'])->middleware('auth:sanctum','admin');


    Route::post('create-project',[ProjectController::class,'create'])->middleware('auth:sanctum');
    Route::get('projects',[ProjectController::class,'getProjects']);
    Route::get('project',[ProjectController::class,'show']);


    Route::post('donate/create',[DonationController::class,'create'])->middleware('auth:sanctum');


    Route::post('wallet/add-funds',[WalletController::class,'addFunds'])->middleware('auth:sanctum');
    Route::post('wallet/deduct-funds',[WalletController::class,'deductFunds'])->middleware('auth:sanctum');
    Route::get('wallet/balance',[WalletController::class,'getBalance'])->middleware('auth:sanctum');

?>
