<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;
use App\Models\Donation;
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

        // $user->makeAdmin();
        $user->update([
            'role'=>'admin'
        ]);
        $user->admin()->create([
            // 'user_id'=>$user->id,  --- IGNORE ---
        ]);
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
     public function getUsers()
    {
        $users = User::where('role','user')->get(['id','firstName','lastName','email','role']);
        return response()->json([
            'message'=>'Users retrieved successfully',
            'users'=>$users
        ],200);
    }

    public function getDonations()
    {
        $donations = Donation::with('user:id,firstName,lastName,email')->get(['id','amount','project_id','user_id']);
        return response()->json([
            'message'=>'Donations retrieved successfully',
            'donations'=>$donations
        ],200);
    }
    public function getOrganizationsApproved()
    {
        $organizations = Organization::with('user:id,firstName,lastName,email')->where('status', 'approved')->get(['id','name','description','type','status','user_id']);
        return response()->json([
            'message'=>'Approved organizations retrieved successfully',
            'organizations'=>$organizations
        ],200);
    }
    public function getOrganizationsRejected(){
        $organizations = Organization::where('status','rejected')->get(['id','name','description','type','status']);
        return response()->json([
            'message'=>'Rejected organizations retrieved successfully',
            'organizations'=>$organizations
        ],200);
    }
    public function getOrganizationsPending(){
        $organizations = Organization::where('status','pending')->get(['id','name','description','type','status']);
        return response()->json([
            'message'=>'Pending organizations retrieved successfully',
            'organizations'=>$organizations
        ],200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message'=>'Logged out successfully'
        ],200);
    }
    
}
