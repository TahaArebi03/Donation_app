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
 

    
    
    function getBalance(Request $request){
        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        if(!$wallet){
            return response()->json([
                'message'=>'Wallet not found'
            ],404);
        }
        return response()->json([
            'balance'=>$wallet->balance
        ]);
    }
    function addFunds(Request $request){
        $request->validate([
            'amount'=>'required|numeric|min:1'
        ]);

        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        if(!$wallet){
            return response()->json([
                'message'=>'Wallet not found'
            ],404);
        }
        $wallet->increment('balance', $request->amount);

        return response()->json([
            'message'=>'Funds added successfully',
            'balance'=>$wallet->balance
        ]);
    }

    
    function deductFunds(Request $request){
        $request->validate([
            'amount'=>'required|numeric|min:1'
        ]);

        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        if(!$wallet){
            return response()->json([
                'message'=>'Wallet not found'
            ],404);
        }
        if($wallet->balance < $request->amount){
            return response()->json([
                'message'=>'Insufficient balance'
            ],400);
        }
        $wallet->decrement('balance', $request->amount);

        return response()->json([
            'message'=>'Funds deducted successfully',
            'balance'=>$wallet->balance
        ]);
    }
    
}
