<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Services\DonationService;
use App\Models\Project;
use App\Models\Donation; 
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
    
    protected $donationService;
    public function __construct(DonationService $donationService)
    {
        $this->donationService = $donationService;
    }
    public function donateToProject(Request $request){
        $user = $request->user();
        if(!$user->canDonate()){
            return response()->json([
                'message'=>'Unauthorized to make a donation'
                ], 403); 
        }

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:1',
        ]);
        
        $project = Project::findorFail($request->project_id);
        if(!$project->isActive()){
            return response()->json([
                'message'=>'Cannot donate to an inactive project'
                ], 400);
        }

        $amount = $request->amount;
        try{
            $donation = $this->donationService->executeDonation($user, $project, $amount);
            return response()->json([
                'message'=>'Donation completed successfully',
                'donation_id'=>$donation->id
                ], 200);
        }catch (\Exception $e){

            return response()->json([

            'message'=>$e->getMessage()

            ],400);
        }

        
        
    }
    
    public function getMyDonations(Request $request){
        $user = $request->user();
        $donations = $user->donations()->with('project')->get();

        return response()->json([
            'message'=>'Donation history retrieved successfully',
            'donations'=>$donations
        ], 200);
    }
}