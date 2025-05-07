<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @unauthenticated
     */
    public function login(LoginRequest $request){
       $data = $request->validated();

       $user = User::where('email', $data['email'])->first();

       // checking user
       if(!$user || Hash::check($data['password'], $user->password))
       {
            return response()->json([
                'success' => false,
                'message' => 'Email or Password is wrong',
                'data' => null
            ], 422);
        }

        // create an api token 
        $token = $user->createToken('sistemkasir')->plainTextToken;
        
        return response()->json([
           'success' => true,
           'message' => 'Login successfully',
           'data' => [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
           ],
        ]);

    }
}
