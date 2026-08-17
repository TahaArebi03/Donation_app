<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationJoinRequest;
use App\Models\User;
use App\Models\OrganizationMember;

use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    

    public function inviteMember(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['error' => 'User does not own any organization'], 403);
        }

        $allowedRoles = ['member', 'admin', 'finance_manager'];
        if (!in_array($request->role, $allowedRoles)) {
            return response()->json(['error' => 'دور غير صالح'], 400);
        }

        $newMember = User::findOrFail($request->user_id);

        if ($newMember->id == $organization->owner_id) {
            return response()->json(['error' => 'Cannot add the owner as a member'], 400);
        }

        $existingMember = $organization->members()->where('user_id', $newMember->id)->first();
        if ($existingMember) {
            return response()->json(['error' => 'User is already a member'], 400);
        }

        $invitation = OrganizationInvitation::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $newMember->id,
                'status' => 'pending',
            ],
            [
                'role' => $request->role,
                'status' => 'pending',
            ]
        );

        if ($invitation->wasRecentlyCreated) {
            $invitation->update(['status' => 'pending']);
        }

        return response()->json([
            'message' => 'تم إرسال الدعوة بنجاح',
            'invitation' => $invitation,
        ]);
    }
    public function removeMember(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['error' => 'User does not own any organization'], 403);
        }

        $member = User::findOrFail($request->user_id);

        if ($member->id == $organization->owner_id) {
            return response()->json(['error' => 'Cannot remove the owner'], 400);
        }

        if (!$organization->members()->where('user_id', $member->id)->exists()) {
            return response()->json(['error' => 'User is not a member'], 400);
        }

        $organization->members()->detach($member->id);

        return response()->json([
            'message' => 'Member removed successfully',
            'member' => $member
        ]);
    }

    public function updateMemberRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['error' => 'User does not own any organization'], 403);
        }

        $member = User::findOrFail($request->user_id);

        if (!$organization->members()->where('user_id', $member->id)->exists()) {
            return response()->json(['error' => 'User is not a member'], 400);
        }

        $organization->members()->updateExistingPivot($member->id, [
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'member' => $member
        ]);
    }
    public function listMembers(Request $request)
    {
        $organizationId = $request->organization_id;

        $organization = Organization::find($organizationId);

        if (!$organization) {
            return response()->json([
                'error' => 'Organization not found'
            ], 404);
        }

        $members = $organization->members()->get();

        return response()->json([
            'members' => $members
        ]);
    }
    public function listOrganizationForMember(Request $request)
{
    $user = $request->user();

    // جلب الجمعيات التي انضم إليها المستخدم وتأكد أن حالته فيها active (مقبول)
    $organizations = $user->organizations()
                          ->wherePivot('status', 'active')
                          ->get();

    return response()->json(['organizations' => $organizations]);
}
    public function searchUser(Request $request)
{
    $query = $request->input('query'); // بدلاً من 'email'
    $users = User::where('firstName', 'LIKE', "%$query%")
                ->orWhere('lastName', 'LIKE', "%$query%")
                ->orWhere('email', 'LIKE', "%$query%")
                ->limit(10)
                ->get();
    return response()->json(['users' => $users]);
}
public function getMembersForPublic($organizationId)
{
    $organization = Organization::findOrFail($organizationId);
    $members = $organization->members()->get();
    return response()->json(['members' => $members]);
}
public function getMembersForMember(Request $request, $organizationId)
{
    $user = $request->user();
    $organization = Organization::findOrFail($organizationId);

    // تحقق من أن المستخدم عضو في هذه الجمعية
    $isMember = $organization->members()->where('user_id', $user->id)->exists();
    if (!$isMember) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $members = $organization->members()->get();
    return response()->json(['members' => $members]);
}
public function volunteerRequest(Request $request, $organizationId)
{
    $user = $request->user();
    $organization = Organization::findOrFail($organizationId);

    $existingMember = $organization->members()->where('user_id', $user->id)->first();
    if ($existingMember) {
        return response()->json(['error' => 'Already a member'], 400);
    }

    $existingRequest = OrganizationJoinRequest::where('organization_id', $organization->id)
        ->where('user_id', $user->id)
        ->where('status', 'pending')
        ->first();

    if ($existingRequest) {
        return response()->json(['error' => 'Request already pending'], 400);
    }

    $requestRecord = OrganizationJoinRequest::create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    return response()->json(['message' => 'Volunteer request sent successfully', 'request' => $requestRecord]);
}
    public function removeInvitation(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:organization_invitations,id',
        ]);

        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['error' => 'User does not own any organization'], 403);
        }

        $invitation = OrganizationInvitation::findOrFail($request->invitation_id);

        if ($invitation->organization_id !== $organization->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['error' => 'Cannot remove a responded invitation'], 400);
        }

        $invitation->delete();

        return response()->json(['message' => 'Invitation removed successfully']);
    }

    public function respondToInvitation(Request $request, $invitationId)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
        ]);

        $invitation = OrganizationInvitation::findOrFail($invitationId);
        $user = $request->user();

        if ($invitation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['error' => 'Invitation already responded'], 400);
        }

        $invitation->status = $request->action === 'accept' ? 'accepted' : 'rejected';
        $invitation->responded_at = now();
        $invitation->save();

        if ($request->action === 'accept') {
            $organization = $invitation->organization;
            $organization->members()->syncWithoutDetaching([
                $user->id => [
                    'role' => $invitation->role,
                    'status' => 'approved',
                    'joined_at' => now(),
                ]
            ]);
        }

        return response()->json(['message' => 'Invitation updated successfully', 'invitation' => $invitation]);
    }

public function respondToJoinRequest(Request $request, $joinRequestId)
{
    $request->validate([
        'action' => 'required|in:accept,reject',
    ]);

    $joinRequest = OrganizationJoinRequest::findOrFail($joinRequestId);
    $user = $request->user();
    $organization = $joinRequest->organization;

    if ($organization->owner_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    if ($joinRequest->status !== 'pending') {
        return response()->json(['error' => 'Join request already responded'], 400);
    }

    $joinRequest->status = $request->action === 'accept' ? 'accepted' : 'rejected';
    $joinRequest->responded_at = now();
    $joinRequest->save();

    if ($request->action === 'accept') {
        $organization->members()->syncWithoutDetaching([
            $joinRequest->user_id => [
                'role' => 'member',
                'status' => 'approved',
                'joined_at' => now(),
            ]
        ]);
    }

    return response()->json(['message' => 'Join request updated successfully', 'request' => $joinRequest]);
}

public function listInvitations(Request $request)
{
    $user = $request->user();
    $organization = $user->organization;

    if (!$organization) {
        return response()->json(['error' => 'User does not own any organization'], 403);
    }

    // جلب جميع الدعوات المرسلة من قبل الجمعية
    $invitations = OrganizationInvitation::where('organization_id', $organization->id)
        ->with(['user:id,firstName,lastName,email'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($invitation) {
            return [
                'id' => $invitation->id,
                'user' => $invitation->user,
                'role' => $invitation->role,
                'status' => $invitation->status,
                'sent_at' => $invitation->created_at,
                'responded_at' => $invitation->responded_at,
            ];
        });

    return response()->json([
        'message' => 'تم جلب الدعوات بنجاح',
        'invitations' => $invitations,
        'total' => $invitations->count(),
    ]);
}

}