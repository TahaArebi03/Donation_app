<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{   
    /**
     * دالة مساعدة مخصصة لجلب منظمة المستخدم الحالي بأمان
     */
    private function getAuthOrganization() {
        $user = Auth::user();
        
        // استخدام اسم العلاقة  كما هو معرف في الموديل 
        $organization = $user->organization; 

        if (!$organization) {
           
            abort(404, 'لم يتم العثور على منظمة مرتبطة بهذا الحساب.');
        }

        return $organization;
    }
    
    /**
     *  البحث عن المستخدمين باستخدام الإيميل
     */
    public function searchUser(Request $request) {
        $request->validate([
            'query' => 'required|string|min:1'
        ]);

        $query = $request->input('query');
        
        // نبحث عن المستخدمين الذين يملكون إيميل يشبه النص المدخل، وبشرط أن يكون حسابهم مستخدم عادي (user) وليس منظمة أو أدمن
        $users = User::where('role', 'user')
                     ->where('email', 'like', "%$query%")
                     ->select('id', 'firstName', 'lastName', 'email') // نرجع الحقول المهمة فقط للأمان
                     ->get();

        return response()->json(['users' => $users], 200);
    }
    
    /**
     * إضافة عضو جديد للجمعية
     */
    public function addMember(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_org' => 'required|in:متطوع,مسعف,منسق',
        ]);

        $organization = $this->getAuthOrganization();
        
        
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        // التحقق من أن المستخدم لا ينتمي إلى أكثر من 3 جمعيات (باستخدام العلاقة المعرفة في الموديل )
        if ($user->volunteerOrganizations()->count() >= 3) {
            return response()->json(['message' => 'المستخدم ينتمي بالفغل إلى الحد الأقصى من الجمعيات (3 جمعيات)'], 400);
        }

        // التحقق من أن المستخدم ليس عضوًا بالفعل في هذه الجمعية
        if ($organization->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json(['message' => 'المستخدم هو بالفعل عضو في هذه الجمعية'], 400);
        }

        // إضافة العضو للجدول الوسيط
        $organization->members()->attach($request->user_id, ['role_in_org' => $request->role_in_org]);

        return response()->json(['message' => 'تم إضافة العضو بنجاح إلى المنظمة'], 201);
    }

    /**
     * عرض قائمة أعضاء المنظمة
     */
    public function listMembers($organizationId) {
        $organization = Organization::findOrFail($organizationId);
        
        // جلب الأعضاء مع البيانات الإضافية من الجدول الوسيط 
        $members = $organization->members()->withPivot('role_in_org')->get();

        return response()->json(['members' => $members], 200);
    }
    
    /**
     * عرض المنظمات التي يتطوع فيها مستخدم معين
     */
    public function listOrganizationsForUser($userId) {
        $user = User::findOrFail($userId);
        
        // اسم العلاقة في الموديل   volunteerOrganizations
        $organizations = $user->volunteerOrganizations()->withPivot('role_in_org')->get();

        return response()->json(['organizations' => $organizations], 200);
    }

    /**
     * إزالة عضو من المنظمة
     */
    public function removeMember(Request $request, $organizationId) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $organization = Organization::findOrFail($organizationId);
        $organization->members()->detach($request->user_id);

        return response()->json(['message' => 'تم حذف العضو من المنظمة بنجاح'], 200);
    }
   
    /**
     * تحديث دور العضو داخل المنظمة
     */
    public function updateMemberRole(Request $request, $organizationId) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_org' => 'required|in:متطوع,مسعف,منسق',
        ]);

        $organization = Organization::findOrFail($organizationId);
        $organization->members()->updateExistingPivot($request->user_id, ['role_in_org' => $request->role_in_org]);

        return response()->json(['message' => 'تم تحديث دور العضو بنجاح'], 200);
    }
}