<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationUserController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RecurringDonationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ===== مسارات عامة (بدون مصادقة) =====
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);

// ===== مسارات تحتاج مصادقة (auth:sanctum) =====
Route::middleware('auth:sanctum')->group(function () {

    // اختبار المصادقة
    Route::get('/test-auth', function (Request $request) {
        return response()->json(['user' => $request->user()]);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('logout', [UserController::class, 'logout']);

    // ===== جلب المستخدمين (لإضافتهم) =====
    Route::get('/users', [UserController::class, 'getUsers']);

    // ===== معلومات الجمعية =====
    Route::get('/organization/show', [OrganizationController::class, 'show']);

    // ===== إدارة الأعضاء (OrganizationUserController) =====
    Route::post('/member/search', [OrganizationUserController::class, 'searchUser']);
    Route::post('/member/add', [OrganizationUserController::class, 'addMember']);
    Route::get('/member/list_members', [OrganizationUserController::class, 'listMembers']);
    Route::get('/member/list_organizations_for_user', [OrganizationUserController::class, 'listOrganizationsForUser']);
    Route::post('/member/remove', [OrganizationUserController::class, 'removeMember']);
    Route::post('/member/update-role', [OrganizationUserController::class, 'updateMemberRole']);

    // ===== المشاريع =====
    Route::post('project/create', [ProjectController::class, 'create']);
    Route::get('projects/all', [ProjectController::class, 'getProjectsForUser']);
    Route::get('projects/organizations', [ProjectController::class, 'getProjectsForOrganization']);
    Route::get('project/show', [ProjectController::class, 'show']);

    // مشاريع جمعية معينة (للأعضاء)
    Route::get('/organizations/{organization}/projects', [ProjectController::class, 'getProjectsForMember']);
    // جلب الأعضاء لجمعية معينة
    Route::get('/organizations/{organization}/members', [OrganizationUserController::class, 'getMembersForMember']);
    // جلب جميع الجمعيات المقبولة (مع حالة العضوية)
    Route::get('/organizations/all', [OrganizationController::class, 'getAllOrganizations']);

    // الانضمام لجمعية
    Route::post('/organizations/{id}/join', [OrganizationUserController::class, 'joinOrganization']);
    // ===== التبرعات والمحفظة =====
    Route::post('donate/create', [DonationController::class, 'create']);
    Route::post('wallet/add-funds', [WalletController::class, 'addFunds']);
    Route::post('wallet/deduct-funds', [WalletController::class, 'deductFunds']);
    Route::get('wallet/balance', [WalletController::class, 'getBalance']);
    Route::post('recurring-donation/create', [RecurringDonationController::class, 'create']);

    // ===== مسارات الأدمن (مع middleware إضافي) =====
    Route::middleware('admin')->group(function () {
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
});