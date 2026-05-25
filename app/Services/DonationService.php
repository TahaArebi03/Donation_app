<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DonationService
{
    public function executeDonation(
        User $user,
        Project $project,
        $amount
    )
    {

        DB::beginTransaction();

        try {

            $userWallet = DB::table('wallets')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $projectWallet = DB::table('wallets')
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->first();

            if($userWallet->balance < $amount){

                throw new \Exception(
                    'Insufficient balance in wallet'
                );
            }

            $remaining =
                $project->goal_amount - $projectWallet->balance;

            if($amount > $remaining){

                throw new \Exception(
                    'Donation exceeds project goal amount'
                );
            }

            // donation
            $donation = $user->donations()->create([

                'project_id'=>$project->id,

                'amount'=>$amount,

                'status'=>'completed',
            ]);

            // user transaction
            $user->wallet->walletTransactions()->create([

                'wallet_id'=>$user->wallet->id,

                'project_id'=>$project->id,

                'amount'=>-$amount,

                'type'=>'withdrawal',

                'status'=>'completed',
            ]);

            // project transaction
            $project->wallet->walletTransactions()->create([

                'wallet_id'=>$project->wallet->id,

                'project_id'=>$project->id,

                'amount'=>$amount,

                'type'=>'deposit',

                'status'=>'completed',
            ]);

            // transfer money
            DB::table('wallets')
                ->where('user_id', $user->id)
                ->decrement('balance', $amount);

            DB::table('wallets')
                ->where('project_id', $project->id)
                ->increment('balance', $amount);

            DB::commit();

            return $donation;

        } catch (\Exception $e){

            DB::rollBack();

            throw $e;
        }
    }
}