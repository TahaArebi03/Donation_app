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
    public function pendingOrganization(Request $request , $id){
        
        $organization = Organization::findOrFail($id);
        $organization->update(['status'=>'pending']);
        return response()->json([
            'message'=>'Organization is pending',
            'organization'=>$organization->only('id','name','description','type','status')
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
        $organizations = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->with('owner:id,firstName,lastName,email')->where('status', 'approved')->get(['id','name','description','type','status','owner_id']);
        return response()->json([
            'message'=>'Approved organizations retrieved successfully',
            'organizations'=>$organizations
        ],200);
    }
    public function getOrganizationsRejected(){
        $organizations = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->where('status','rejected')->get(['id','name','description','type','status']);
        return response()->json([
            'message'=>'Rejected organizations retrieved successfully',
            'organizations'=>$organizations
        ],200);
    }
    public function getOrganizationsPending(){
        $organizations = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->where('status','pending')->get(['id','name','description','type','status']);
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
    public function getOrganizationDetails($id)
    {
        // name, description, type, status, owner (id, firstName, lastName, email)
        
        $organization = Organization::withCount([
            'approvedMembers as members_count',
            'followers as followers_count',
            'projects as projects_count',
        ])->with('owner:id,firstName,lastName,email')->get(['id','name','description','type','status','owner_id'])->findOrFail($id);
        return response()->json([
            'message'=>'Organization details retrieved successfully',
            'organization'=>$organization
        ],200);
    }
    // deleteOrganization
    public function deleteOrganization($id)
    {
        $organization = Organization::findOrFail($id);
        $organization->delete();
        return response()->json([
            'message'=>'Organization deleted successfully'
        ],200);
    }
    // getUserDetails
    public function getUserDetails($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'message'=>'User details retrieved successfully',
            'user'=>$user->only('id','firstName','lastName','email','role')
        ],200);
    }
    // deleteUser
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            'message'=>'User deleted successfully'
        ],200);
    
    }
    
}
