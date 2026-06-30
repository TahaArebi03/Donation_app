<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;

use Illuminate\Http\Request;

class OrganizationUserController extends Controller
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
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Member added successfully']);
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

        return response()->json(['message' => 'Member removed successfully']);
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

        return response()->json(['message' => 'Role updated successfully']);
    }
    public function listMembers(Request $request)
    {
        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        $members = $organization->members()->get();
        return response()->json(['members' => $members]);
    }
    public function listOrganizationsForUser(Request $request)
    {
        $user = $request->user();

        // جلب جميع المنظمات التي انضم إليها المستخدم
        $organizations = $user->organizations()->get();

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
public function joinOrganization(Request $request, $organizationId)
{
    $user = $request->user();
    $organization = Organization::findOrFail($organizationId);
    
    // التحقق من أن الجمعية مقبولة
    if ($organization->status !== 'approved') {
        return response()->json(['error' => 'Organization is not available for joining'], 400);
    }
    
    // التحقق من أن المستخدم ليس مالك الجمعية
    if ($user->id == $organization->owner_id) {
        return response()->json(['error' => 'You are the owner of this organization'], 400);
    }
    
    // التحقق من أن المستخدم ليس عضواً بالفعل
    if ($organization->members()->where('user_id', $user->id)->exists()) {
        return response()->json(['error' => 'Already a member'], 400);
    }
    
    // إضافة العضو
    $organization->members()->attach($user->id, [
        'role' => 'عضو',
        'joined_at' => now(),
    ]);
    
    return response()->json(['message' => 'Joined successfully']);
}
}