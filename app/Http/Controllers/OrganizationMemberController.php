<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Models\OrganizationMember;

use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    

    public function addMember(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $user = $request->user();
        $organization = $user->organization; // استنتاج الجمعية من المستخدم

        if (!$organization) {
            return response()->json(['error' => 'User does not own any organization'], 403);
        }
        $allowedRoles = ['member', 'admin', 'finance_manager'];
        if (!in_array($request->role, $allowedRoles)) {
            return response()->json(['error' => 'دور غير صالح'], 400);
        }

        $newMember = User::findOrFail($request->user_id);

        // تحقق من أن العضو ليس موجوداً بالفعل
        if ($organization->members()->where('user_id', $newMember->id)->exists()) {
            return response()->json(['error' => 'User is already a member'], 400);
        }

        // تحقق من أن المستخدم ليس المالك نفسه
        if ($newMember->id == $organization->owner_id) {
            return response()->json(['error' => 'Cannot add the owner as a member'], 400);
        }

        // إضافة العضو
        $organization->members()->attach($newMember->id, [
            'role' => $request->role,
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        return response()->json([
    'message' => 'تمت الإضافة بنجاح',
    'member' => $newMember, // أو بيانات العضو مع دوره
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

    // تحقق من أنه ليس عضواً فعالاً بالفعل
    $existing = $organization->members()->where('user_id', $user->id)->first();
    if ($existing && $existing->pivot->status == 'active') {
        return response()->json(['error' => 'Already a member'], 400);
    }

    // إذا كان هناك طلب pending مسبقاً
    if ($existing && $existing->pivot->status == 'pending') {
        return response()->json(['error' => 'Request already pending'], 400);
    }

    // إضافة أو تحديث الطلب
    $organization->members()->syncWithoutDetaching([
        $user->id => [
            'role' => 'عضو',
            'status' => 'pending',
            'joined_at' => now(),
        ]
    ]);

    return response()->json(['message' => 'Volunteer request sent successfully']);
}

}