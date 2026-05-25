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
    public function create(Request $request){
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
    
    public function history(Request $request){
        $user = $request->user();
        $donations = $user->donations()->with('project')->get();

        return response()->json([
            'message'=>'Donation history retrieved successfully',
            'donations'=>$donations
        ], 200);
    }
}

// DB::beginTransaction();

//         try {
           
//             $userWallet = DB::table('wallets')->where('user_id', $user->id)->lockForUpdate()->first();
//             $projectWallet = DB::table('wallets')->where('project_id', $project->id)->lockForUpdate()->first();


//             if($userWallet->balance < $amount){
//                 DB::rollBack(); 
//                 return response()->json([
//                     'message'=>'Insufficient balance in wallet'
//                     ], 400);
//             }

          
//             if($projectWallet->balance + $amount > $project->goal_amount){
//                 DB::rollBack(); 
//                 return response()->json([
//                     'message'=>'Donation exceeds project goal amount'
//                     ], 400);
//             }

           
//             $donation = $user->donations()->create([
//                 'project_id' => $request->project_id,
//                 'amount' => $amount,
//                 'status'=>'pending',
//             ]);

//             $user->wallet->walletTransactions()->create([
//                 'wallet_id'=>$user->wallet->id,
//                 'project_id'=>$project->id,
//                 'amount'=> -$amount, 
//                 'type'=>'withdrawal',
//                 'status'=>'completed',
//             ]);

//             $project->wallet->walletTransactions()->create([
//                 'wallet_id'=>$project->wallet->id,
//                 'project_id'=>$project->id,
//                 'amount'=> $amount, 
//                 'type'=>'deposit',
//                 'status'=>'completed',
//             ]);

            
//             DB::table('wallets')->where('user_id', $user->id)->decrement('balance', $amount);
//             DB::table('wallets')->where('project_id', $project->id)->increment('balance', $amount);
            
//             $donation->update(['status' => 'completed']);

        
//             DB::commit();

//             return response()->json([
//                 'message' => 'Donation completed successfully',
//                 'donation_id' => $donation->id
//             ], 200);

//         } catch (\Exception $e) {
        
//             DB::rollback();
//             return response()->json([
//                 'message'=>'Failed to process donation',
//                 'error'=>$e->getMessage()
//                 ], 500);
//         }