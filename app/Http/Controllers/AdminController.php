<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;

class AdminController extends Controller
{
    public function makeAdmin($id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            return response()->json([
                'message'=>'User is already an admin'
            ],400);
        }

        $user->makeAdmin();

        return response()->json([
            'message'=>'User promoted to admin successfully',
            'user'=>$user->only('id','firstName','lastName','email','role')
        ],200);
    }
    public function approveOrganization(Request $request, $id){
        $organization = Organization::findOrFail($id);
        $organization->update(['status'=>'approved']);
        return response()->json([
            'message'=>'Organization approved successfully',
            'organization'=>$organization->only('id','name','description','type','status')
        ],200);
    }
    public function rejectOrganization(Request $request, $id){
        $organization = Organization::findOrFail($id);
        $organization->update(['status'=>'rejected']);
        return response()->json([
            'message'=>'Organization rejected successfully',
            'organization'=>$organization->only('id','name','description','type','status')
        ],200);
    }
    public function pendingOrganizations(){
        $organizations = Organization::where('status','pending')->get(['id','name','description','type','status']);
        return response()->json([
            'message'=>'Pending organizations retrieved successfully',
            'organizations'=>$organizations
        ],200);
    }
}
