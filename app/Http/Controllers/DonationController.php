<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Donation; // تأكد من استدعاء الموديل
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
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

        
        DB::beginTransaction();

        try {
           
            $userWallet = DB::table('wallets')->where('user_id', $user->id)->lockForUpdate()->first();
            $projectWallet = DB::table('wallets')->where('project_id', $project->id)->lockForUpdate()->first();


            if($userWallet->balance < $amount){
                DB::rollBack(); 
                return response()->json([
                    'message'=>'Insufficient balance in wallet'
                    ], 400);
            }

          
            if($projectWallet->balance + $amount > $project->goal_amount){
                DB::rollBack(); 
                return response()->json([
                    'message'=>'Donation exceeds project goal amount'
                    ], 400);
            }

           
            $donation = $user->donations()->create([
                'project_id' => $request->project_id,
                'amount' => $amount,
                'status'=>'pending',
            ]);

            $user->walletTransactions()->create([
                'user_id'=>$user->id,
                'project_id'=>$project->id,
                'amount'=> -$amount, 
                'type'=>'withdrawal',
                'status'=>'completed',
            ]);

            
            DB::table('wallets')->where('user_id', $user->id)->decrement('balance', $amount);
            DB::table('wallets')->where('project_id', $project->id)->increment('balance', $amount);

            
            $donation->update(['status' => 'completed']);

        
            DB::commit();

            return response()->json([
                'message' => 'Donation completed successfully',
                'donation_id' => $donation->id
            ], 200);

        } catch (\Exception $e) {
        
            DB::rollback();
            return response()->json([
                'message'=>'Failed to process donation',
                'error'=>$e->getMessage()
                ], 500);
        }
    }       
}