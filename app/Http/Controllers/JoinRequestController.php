<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JoinRequestController extends Controller
{
    // المتبرع: جلب طلباتي
    public function getMyJoinRequests(Request $request)
{
    $user = $request->user();
    $requests = $user->joinRequests()
                    ->with(['organization', 'user']) 
                    ->get();
    Log::info('Join requests with organization:', $requests->toArray());
    return response()->json(['requests' => $requests]);
}

    // المتبرع: تقديم طلب
public function sendJoinRequest(Request $request)
{
    $request->validate([
        'organization_id' => 'required|exists:organizations,id',
    ]);

    $user = $request->user();
    $org = Organization::find($request->organization_id);

    // تحقق من أنه ليس عضواً بالفعل
    if ($org->members()->where('user_id', $user->id)->exists()) {
        return response()->json(['error' => 'أنت عضو بالفعل'], 400);
    }

    // تحقق من عدم وجود طلب معلق
    if ($user->joinRequests()->where('organization_id', $org->id)->where('status', 'pending')->exists()) {
        return response()->json(['error' => 'لديك طلب معلق بالفعل'], 400);
    }

    // إنشاء الطلب
    $joinRequest = $user->joinRequests()->create([
        'organization_id' => $org->id,
        'status' => 'pending',
    ]);

    return response()->json([
        'message' => 'تم تقديم الطلب بنجاح',
        'request' => $joinRequest,
    ], 201);
}

    // المدير: جلب الطلبات المعلقة لجمعيته
    public function pendingRequests(Request $request)
    {
        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'ليس لديك جمعية'], 403);

        $requests = $org->joinRequests()->where('status', 'pending')->with('user')->get();
        return response()->json(['requests' => $requests]);
    }

    // المدير: قبول طلب
    public function approve(Request $request)
    {
        $request->validate(['request_id' => 'required|exists:organization_join_requests,id']);
        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'Unauthorized'], 403);

        $joinRequest = $org->joinRequests()->where('id', $request->request_id)->firstOrFail();
        // 🔥 إذا كانت هناك دعوة معلقة لنفس المستخدم ونفس الجمعية، نلغيها
        $org->invitations()
        ->where('user_id', $joinRequest->user_id)
        ->where('status', 'pending')
        ->delete();
        $joinRequest->update(['status' => 'approved']);

        // إضافة المستخدم كعضو
        $org->members()->attach($joinRequest->user_id, [
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'تم قبول الطلب']);
    }

    // المدير: رفض طلب
    public function reject(Request $request)
    {
        $request->validate(['request_id' => 'required|exists:organization_join_requests,id']);
        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'Unauthorized'], 403);

        $joinRequest = $org->joinRequests()->where('id', $request->request_id)->firstOrFail();
        $joinRequest->update(['status' => 'rejected']);

        return response()->json(['message' => 'تم رفض الطلب']);
    }

    public function cancelRequest(Request $request)
{
    $request->validate(['request_id' => 'required|exists:organization_join_requests,id']);

    $user = $request->user();
    $joinRequest = $user->joinRequests()->where('id', $request->request_id)->first();

    if (!$joinRequest) {
        return response()->json(['error' => 'الطلب غير موجود أو لا يخصك'], 404);
    }

    if ($joinRequest->status !== 'pending') {
        return response()->json(['error' => 'لا يمكن إلغاء طلب تم الرد عليه'], 400);
    }

    $joinRequest->delete();
    return response()->json(['message' => 'تم إلغاء الطلب']);
}
    
    // حالة الطلب
    public function getRequestStatus(Request $request, $organizationId)
    {
        $user = $request->user();
        $joinRequest = $user->joinRequests()->where('organization_id', $organizationId)->first();

        if (!$joinRequest) {
            return response()->json(['status' => 'none']);
        }

        return response()->json(['status' => $joinRequest->status]);
    }
}