<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
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

        return response()->json([
            'id' => $organization->id,
            'name' => $organization->name,
            'description' => $organization->description,
            'owner_id' => $organization->owner_id,
            'created_at' => $organization->created_at,
            'updated_at' => $organization->updated_at,
        ]);
    }
    public function getAllOrganizations(Request $request)
    {
        $user = $request->user();
        
        // جلب جميع الجمعيات المقبولة
        $organizations = Organization::where('status', 'approved')->get();
        
        // إضافة حالة العضوية لكل جمعية
        $organizations->each(function ($org) use ($user) {
            $org->is_member = $org->members()->where('user_id', $user->id)->exists();
        });
        
        return response()->json(['organizations' => $organizations]);
    }
    
    


    
}
