<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function register(Request $request){
        $request->validate([
            'firstName'=>'required|string|max:255',
            'lastName'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|confirmed|min:6',
            'password_confirmation'=>'required',
            'role'=>'sometimes|string|in:user,organization',

            'name'=>'required_if:role,organization|string|max:255',
            'description'=>'required_if:role,organization|string|max:255',
            'type'=>'required_if:role,organization|in:active,charity',
            'status'=>'string|in:pending,approved,rejected',
            'document_path' => 'required_if:role,organization|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            
        ]);
        $user=User::create([
            'firstName'=>$request->firstName,
            'lastName'=>$request->lastName,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'role'=>$request->role??'user',
        ]);
        
       
        if($user->isOrganization()) {
            $image_path = null;

            if ($request->hasFile('document_path')) {
                $image_path = $request->file('document_path')->store('organization_documents', 'public');
            }

            $user->organization()->create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'document_path' => $image_path,
                'status' => 'pending',
            ]);
            
            return response()->json([
                'message'=>'Organization registered successfully, pending approval',
                'user'=>$user->only('id','firstName','lastName','email','role'),
                'organization'=>$user->organization->only('id','name','description','type','status')
            ],201);
        }
        // if($user->isAdmin()) {
        //     return response()->json([
        //         'message'=>'Admin registered successfully',
        //         'user'=>$user->only('id','firstName','lastName','email','role'),
        //         'admin'=>$user->admin->only('id','firstName','lastName','email','role'),
        //     ],201);
        // }
        $wallet=null;
        if($user->isUser()){
            $wallet=$user->wallet()->create([
                // هادي ماشي ضرورية لان العلاقة بين اليوزر والواليت هي علاقة واحد لواحد، يعني اليوزر كي يتخلق كيتخلق ليه واليت اوتوماتيكيا，   ولكن خليتها باش نوضح الفكرة
                // 'user_id'=>$user->id,
                'balance'=>0,
            ]);
        }
        
        return response()->json([
        'message'=>'User registered successfully',
        'user'=>$user->only('id','firstName','lastName','email','role'),
        
        'wallet'=>$wallet ? $wallet->only('id','user_id','balance') : null
        ],201);          
    }

    public function login(Request $request){
        $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);

        $user=User::where('email',$request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'message'=>'Invalid credentials'
            ],401);
        }

        

        if($user->isOrganization() && optional($user->organization)->isApproved()) {
            $token=$user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Organization logged in successfully',
                'user' => $user->only('id', 'firstName', 'lastName', 'email', 'role'),
                'organization' => $user->organization->only('id','name','description','type','status'),
                'token' => $token
            ], 200);
            
        }elseif($user->isUser()) {
            $token=$user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'User logged in successfully',
                'user' => $user->only('id', 'firstName', 'lastName', 'email', 'role'),
                'wallet' => $user->wallet->only('id', 'user_id', 'balance'),
                'token' => $token
            ], 200);
        }elseif($user->isAdmin()) {
            $token=$user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Admin logged in successfully',
                'user' => $user->only('id', 'firstName', 'lastName', 'email', 'role'),
                'admin' => $user->admin->only('id','user_id'),
                'token' => $token
            ], 200);
        }else{
            return response()->json([
                'message' => 'Organization account is pending',
                'user' => $user->only('id', 'firstName', 'lastName', 'email', 'role'),
                'organization' => optional($user->organization)->only('id', 'name', 'description', 'type', 'status')
            ], 403);
        }    
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message'=>'User logged out successfully'
        ],200);
    }



}
