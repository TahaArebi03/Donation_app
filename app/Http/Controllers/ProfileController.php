<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileRequest;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show($id){
        $profile=Profile::where('user_id',$id);
        
    }
    public function store(StoreProfileRequest $request){
        $profile=Profile::create($request->validated());
        // $profile['user_id']=$id;
        return response()->json([
            'message'=>'create profile succssfuly',
            'profile'=>$profile,
        ]);
    }
}
