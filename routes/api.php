<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RecurringDonationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ======================================================================
// 1. مسارات عامة (بدون مصادقة) - للزوار والمشاهدة
// ======================================================================

// --- المصادقة ---
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);

// --- المشاريع (للجميع) ---
Route::get('projects/all', [ProjectController::class, 'getAllProjects']);
Route::get('project/{id}/show', [ProjectController::class, 'show']); // عرض تفاصيل مشروع
Route::get('projects/{id}/organization', [ProjectController::class, 'getProjectsForOrganization']);

// --- الجمعيات (للجميع) ---
Route::get('/organizations/all', [OrganizationController::class, 'getAllOrganizations']);
Route::get('/organizations/{organization}/details', [OrganizationController::class, 'getOrganizationDetails']); // تفاصيل جمعية

// --- أعضاء الجمعية (للجميع) ---
Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'getMembersForPublic']); // عرض أعضاء جمعية (بدون مصادقة)


// ======================================================================
// 2. مسارات محمية (تتطلب مصادقة)
// ======================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- معلومات المستخدم والمصادقة ---
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('/test-auth', function (Request $request) { return response()->json(['user' => $request->user()]); });

    Route::post('/organizations/{id}/volunteer-request', [OrganizationMemberController::class, 'volunteerRequest'])
        ->middleware('auth:sanctum');
    Route::post('/invitations/{invitation}/respond', [OrganizationMemberController::class, 'respondToInvitation']);
    Route::post('/join-requests/{joinRequest}/respond', [OrganizationMemberController::class, 'respondToJoinRequest']);

    // --- معلومات الجمعية (للمدير فقط) ---
    Route::get('/organization/show', [OrganizationController::class, 'show']);

    // --- جلب المستخدمين (لإدارة الأعضاء) ---
    Route::get('/users', [UserController::class, 'getUsers']);

    // --- إدارة الأعضاء (للمدير فقط) ---
    Route::post('/member/search', [OrganizationMemberController::class, 'searchUser']);
    Route::post('/member/add', [OrganizationMemberController::class, 'addMember']);
    Route::get('/member/list_members', [OrganizationMemberController::class, 'listMembers']);
    Route::get('/member/list_organizations_for_member', [OrganizationMemberController::class, 'listOrganizationForMember']);
    Route::post('/member/remove', [OrganizationMemberController::class, 'removeMember']);
    Route::post('/member/update-role', [OrganizationMemberController::class, 'updateMemberRole']);
    Route::post('/member/invite', [OrganizationMemberController::class, 'inviteMember']);
    Route::get('/member/invitations', [OrganizationMemberController::class, 'listInvitations']);
    Route::post('/member/remove-invitation', [OrganizationMemberController::class, 'removeInvitation']);
    // --- إنشاء مشاريع (للمدير فقط) ---
    Route::post('project/create', [ProjectController::class, 'create']);
    Route::put('project/{id}/update', [ProjectController::class, 'update']);
    Route::delete('project/{id}/delete', [ProjectController::class, 'delete']);
    

    // --- المتابعات (للمستخدم العادي) ---
    Route::get('/user/followed-organizations', [OrganizationController::class, 'getFollowedOrganizations']);
    Route::post('/organizations/{id}/follow', [OrganizationController::class, 'followOrganization']);
    Route::post('/organizations/{id}/unfollow', [OrganizationController::class, 'unfollowOrganization']);

    // --- الانضمام لجمعية (طلب عضوية) ---
    Route::post('/organizations/{id}/join', [OrganizationMemberController::class, 'joinOrganization']);

    // --- التبرعات ---
    Route::post('donate/create', [DonationController::class, 'create']);
    Route::post('wallet/add-funds', [WalletController::class, 'addFunds']);
    Route::post('wallet/deduct-funds', [WalletController::class, 'deductFunds']);
    Route::get('wallet/balance', [WalletController::class, 'getBalance']);
    Route::post('recurring-donation/create', [RecurringDonationController::class, 'create']);

    // ======================================================================
    // 3. مسارات الأدمن
    // ======================================================================
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
        Route::get('/admin/organizations/approved', [AdminController::class, 'getOrganizationsApproved']);
        Route::get('/admin/organizations/rejected', [AdminController::class, 'getOrganizationsRejected']);
        Route::get('/admin/organizations/pending', [AdminController::class, 'getOrganizationsPending']);
        Route::get('/admin/donations', [AdminController::class, 'getDonations']);
        Route::post('/admin/organization/{id}/approve', [AdminController::class, 'approveOrganization']);
        Route::post('/admin/organization/{id}/reject', [AdminController::class, 'rejectOrganization']);
        Route::post('/admin/organization/{id}/pending', [AdminController::class, 'pendingOrganization']);
        Route::get('/admin/organization/{id}/details', [AdminController::class, 'getOrganizationDetails']);
        Route::post('/admin/organization/{id}/delete', [AdminController::class, 'deleteOrganization']);
        Route::get('/admin/users/{id}/details', [AdminController::class, 'getUserDetails']);
        Route::post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser']);
        Route::post('/admin/users/{id}/make-admin', [AdminController::class, 'makeAdmin']);
        Route::post('/admin/logout', [AdminController::class, 'logout']);
    });
});