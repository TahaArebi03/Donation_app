<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletRequest;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function validate(Request $request){
        $wallet=Wallet::create([
            'user_id'=>$request->user_id,
            'balance'=>$request->balance,
        ]);

        return response()->json([
            'message'=>'Wallet created successfully',
            'wallet'=>$wallet->only('id','user_id','balance')
        ],201);
    }
 

    
    
    // public function addFunds(Request $request){
    //     $request->validate([
    //         'amount'=>'required|numeric|min:0.01',
    //     ]);

    //     $wallet = $request->user()->wallet;
    //     $wallet->balance += $request->amount;
    //     $wallet->save();

    //     return response()->json($wallet);
    // }
    // public function deductFunds(Request $request){
    //     $request->validate([
    //         'amount'=>'required|numeric|min:0.01',
    //     ]);

    //     $wallet = $request->user()->wallet;
    //     if($wallet->balance < $request->amount){
    //         return response()->json(['message'=>'Insufficient funds'],400);
    //     }
    //     $wallet->balance -= $request->amount;
    //     $wallet->save();

    //     return response()->json($wallet);
    // }
    
}
