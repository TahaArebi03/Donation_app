<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    private function getFollowedOrganizationIds(User $user)
    {
        return DB::table('user_organization_follows')
            ->where('user_id', $user->id)
            ->pluck('organization_id')
            ->toArray();
    }

    private function isOrganizationFollowedByUser(User $user, $organizationId)
    {
        return DB::table('user_organization_follows')
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->exists();
    }

    public function show(Request $request)
    {
        $user = $request->user();
        
        // التحقق من وجود المستخدم
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // جلب الجمعية التي يملكها المستخدم
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['message' => 'User does not belong to any organization'], 404);
        }

        $organization->loadCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ]);

        return response()->json([
            'id' => $organization->id,
            'name' => $organization->name,
            'description' => $organization->description,
            'owner_id' => $organization->owner_id,
            'members_count' => $organization->members_count,
            'followers_count' => $organization->followers_count,
            'projects_count' => $organization->projects_count,
            'created_at' => $organization->created_at,
            'updated_at' => $organization->updated_at,
        ]);
    }

    public function getAllOrganizations(Request $request)
    {
        $user = $request->user();
        $followedOrgIds = $user ? $this->getFollowedOrganizationIds($user) : [];
        $organizations = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->where('status', 'approved')->get();
        $organizations->each(function ($org) use ($user, $followedOrgIds) {
            // العضوية الإدارية
            $org->is_member = $user ? $org->members()->where('user_id', $user->id)->exists() : false;
            
            // المتابعة
            $org->is_followed = $user ? in_array($org->id, $followedOrgIds, true) : false;
        });
        
        return response()->json(['organizations' => $organizations]);
    }
    
    // جلب الجمعيات التي يتابعها المستخدم
    public function getFollowedOrganizations(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $followedOrgIds = $this->getFollowedOrganizationIds($user);
        $organizations = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->whereIn('id', $followedOrgIds)->get();
        return response()->json(['organizations' => $organizations]);
    }

    // متابعة جمعية
    public function followOrganization(Request $request, $organizationId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $organization = Organization::findOrFail($organizationId);

        // منع المتابعة إذا كان المدير نفسه
        if ($user->id == $organization->owner_id) {
            return response()->json(['error' => 'You cannot follow your own organization'], 400);
        }

        // التحقق من عدم التكرار
        if ($this->isOrganizationFollowedByUser($user, $organizationId)) {
            return response()->json(['error' => 'Already following'], 400);
        }

        $follow=DB::table('user_organization_follows')->insert([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'followed_at' => now(),
        ]);
        return response()->json(['message' => 'Followed successfully', 'follow' => $follow]);
    }

    // إلغاء متابعة جمعية
    public function unfollowOrganization(Request $request, $organizationId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        DB::table('user_organization_follows')
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->delete();

        return response()->json(['message' => 'Unfollowed successfully']);
    }

    public function approveVolunteer(Request $request, $organizationId, $userId)
    {
        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $organization = Organization::findOrFail($organizationId);

        // فقط المالك أو الأدمن يمكنه الموافقة
        if ($authUser->id !== $organization->owner_id && !$authUser->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $member = $organization->members()->where('user_id', $userId)->first();
        if (!$member) {
            return response()->json(['error' => 'Member request not found'], 404);
        }

        $organization->members()->updateExistingPivot($userId, [
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Volunteer approved']);
    }

    public function rejectVolunteer(Request $request, $organizationId, $userId)
    {
        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $organization = Organization::findOrFail($organizationId);

        // فقط المالك أو الأدمن يمكنه الرفض
        if ($authUser->id !== $organization->owner_id && !$authUser->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $member = $organization->members()->where('user_id', $userId)->first();
        if (!$member) {
            return response()->json(['error' => 'Member request not found'], 404);
        }

        $organization->members()->updateExistingPivot($userId, [
            'status' => 'rejected',
        ]);

        return response()->json(['message' => 'Volunteer rejected']);
    }

    public function getOrganizationDetails($organizationId)
    {
        $organization = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->with('owner')->findOrFail($organizationId);
        $user = Auth::user();

        $isFollowed = $user ? $this->isOrganizationFollowedByUser($user, $organizationId) : false;

        $volunteerStatus = 'none';
        $isMember = false;
        if ($user) {
            $member = $organization->members()->where('user_id', $user->id)->first();
            if ($member) {
                $status = $member->pivot->status;
                if ($status === 'active') {
                    $isMember = true;
                    $volunteerStatus = 'approved';
                } else {
                    $volunteerStatus = $status; // pending, rejected
                }
            }
        }

        return response()->json([
            'id' => $organization->id,
            'name' => $organization->name,
            'description' => $organization->description,
            'type' => $organization->type,
            'status' => $organization->status,
            'owner' => [
                'id' => optional($organization->owner)->id,
                'firstName' => optional($organization->owner)->firstName,
                'lastName' => optional($organization->owner)->lastName,
                'email' => optional($organization->owner)->email,
            ],
            'is_followed' => $isFollowed,
            'is_member' => $isMember,
            'volunteer_status' => $volunteerStatus,
            'has_requested_volunteer' => ($volunteerStatus == 'pending'),
        ]);
    }
}
    