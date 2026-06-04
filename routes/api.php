<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringDonationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\MemberController;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

    Route::post('register',[UserController::class,'register']);
    Route::post('login',[UserController::class,'login']);
    Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');


    Route::post('organizations/create',[OrganizationController::class,'create'])->middleware('auth:sanctum');




    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::get('/admin/organizations/approved', [AdminController::class, 'getOrganizationsApproved']);
    Route::get('/admin/donations', [AdminController::class, 'getDonations']);
    Route::get('/admin/organizations/rejected', [AdminController::class, 'getOrganizationsRejected']);
    Route::get('/admin/organizations/pending', [AdminController::class, 'getOrganizationsPending']);
    Route::post('/admin/organizations/{id}/approve', [AdminController::class, 'approveOrganization']);
    Route::post('/admin/organizations/{id}/reject', [AdminController::class, 'rejectOrganization']);
    Route::post('/admin/users/{id}/make-admin', [AdminController::class, 'makeAdmin']);
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/member/search', [MemberController::class, 'searchUser']);
        Route::post('/member/add', [MemberController::class, 'addMember']);
        Route::get('/member/list_members', [MemberController::class, 'listMembers']);
        Route::get('/member/list_organizations_for_user', [MemberController::class, 'listOrganizationsForUser']);
        Route::post('/member/remove', [MemberController::class, 'removeMember']);
        Route::post('/member/update-role', [MemberController::class, 'updateMemberRole']); 
    });
    


    Route::post('create-project',[ProjectController::class,'create'])->middleware('auth:sanctum');
    Route::get('projects',[ProjectController::class,'getProjects']);
    Route::get('project',[ProjectController::class,'show']);


    Route::post('donate/create',[DonationController::class,'create'])->middleware('auth:sanctum');


    Route::post('wallet/add-funds',[WalletController::class,'addFunds'])->middleware('auth:sanctum');
    Route::post('wallet/deduct-funds',[WalletController::class,'deductFunds'])->middleware('auth:sanctum');
    Route::get('wallet/balance',[WalletController::class,'getBalance'])->middleware('auth:sanctum');


    Route::post('recurring-donation/create',[RecurringDonationController::class,'create'])->middleware('auth:sanctum');
?>
