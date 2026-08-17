<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class JoinRequestController extends Controller
{
    // المتبرع: جلب طلباتي
    public function getMyJoinRequests(Request $request)
    {
        $user = $request->user();
        $requests = $user->joinRequests()->with('organization')->get();
        return response()->json(['requests' => $requests]);
    }

    // المتبرع: تقديم طلب
    public function sendJoinRequest(Request $request)
    {
        $request->validate(['organization_id' => 'required|exists:organizations,id']);
        $user = $request->user();
        $org = Organization::find($request->organization_id);

        if ($org->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'أنت عضو بالفعل'], 400);
        }
        if ($user->joinRequests()->where('organization_id', $org->id)->where('status', 'pending')->exists()) {
            return response()->json(['error' => 'لديك طلب معلق'], 400);
        }

        $request = $user->joinRequests()->create([
            'organization_id' => $org->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'تم تقديم الطلب', 'request' => $request]);
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
        $request->validate(['request_id' => 'required|exists:join_requests,id']);
        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'Unauthorized'], 403);

        $joinRequest = $org->joinRequests()->where('id', $request->request_id)->firstOrFail();
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
        $request->validate(['request_id' => 'required|exists:join_requests,id']);
        $org = $request->user()->organization;
        if (!$org) return response()->json(['error' => 'Unauthorized'], 403);

        $joinRequest = $org->joinRequests()->where('id', $request->request_id)->firstOrFail();
        $joinRequest->update(['status' => 'rejected']);

        return response()->json(['message' => 'تم رفض الطلب']);
    }

    public function cancel(Request $request)
    {
        $request->validate(['request_id' => 'required|exists:join_requests,id']);
        $user = $request->user();
        $joinRequest = $user->joinRequests()->where('id', $request->request_id)->firstOrFail();

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