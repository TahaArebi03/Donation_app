<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    // المتبرع: جلب دعواتي
    public function getMyInvitations(Request $request)
{
    $user = $request->user();
    $invitations = $user->invitations()
                       ->with('organization')
                       ->get();
    return response()->json(['invitations' => $invitations]);
}

    // المدير: جلب الدعوات التي أرسلتها جمعيتي
    public function getSentInvitationsForOrganization(Request $request)
{
    $user = $request->user();
    
    // تحقق من أن المستخدم لديه جمعية
    if (!$user->organization) {
        return response()->json(['error' => 'ليس لديك جمعية لإدارتها'], 403);
    }

    $org = $user->organization;
    $invitations = $org->invitations()->with('user')->get();

    return response()->json(['invitations' => $invitations]);
}

    // المدير: إرسال دعوة
    public function sendInvitation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'ليس لديك جمعية'], 403);

        $invitee = User::find($request->user_id);
        if ($org->members()->where('user_id', $invitee->id)->exists()) {
            return response()->json(['error' => 'هذا المستخدم عضو بالفعل'], 400);
        }

        // منع تكرار الدعوات المعلقة
        if ($org->invitations()->where('user_id', $invitee->id)->where('status', 'pending')->exists()) {
            return response()->json(['error' => 'لديه دعوة معلقة بالفعل'], 400);
        }

        $invitation = $org->invitations()->create([
            'user_id' => $invitee->id,
            'role' => $request->role,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'تم إرسال الدعوة', 'invitation' => $invitation]);
    }

    // المتبرع: قبول دعوة
    public function accept(Request $request)
    {
        $request->validate(['invitation_id' => 'required|exists:organization_invitations,id']);
        $user = $request->user();
        $invitation = OrganizationInvitation::where('user_id', $user->id)
                        ->where('id', $request->invitation_id)->firstOrFail();

        $invitation->update(['status' => 'accepted']);

        // إضافة المستخدم كعضو
        $invitation->organization->members()->attach($user->id, [
            'role' => $invitation->role,
            'status' => 'approved',
            'joined_at' => now(),
        ]);
        //  تحديث أي طلب انضمام معلق لنفس المستخدم ونفس الجمعية
        $invitation->organization->joinRequests()
        ->where('user_id', $user->id)
        ->where('status', 'pending')
        ->update(['status' => 'approved']);

        return response()->json(['message' => 'تم قبول الدعوة']);
    }

    // المتبرع: رفض دعوة
    public function reject(Request $request)
    {
        $request->validate(['invitation_id' => 'required|exists:organization_invitations,id']);
        $user = $request->user();
        $invitation = OrganizationInvitation::where('user_id', $user->id)
                        ->where('id', $request->invitation_id)->firstOrFail();

        $invitation->update(['status' => 'rejected']);

        return response()->json(['message' => 'تم رفض الدعوة']);
    }

    // المدير: إلغاء دعوة (قبل الرد)
    public function cancelInvitation(Request $request, $id)
    {
        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'ليس لديك جمعية'], 403);

        $invitation = $org->invitations()->where('id', $id)->where('status', 'pending')->firstOrFail();
        $invitation->delete();

        return response()->json(['message' => 'تم إلغاء الدعوة']);
    }
}