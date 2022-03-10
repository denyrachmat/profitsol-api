<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use App\Models\User;
use Validator;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $attemptUsername = Auth::attempt(['username' => $request->username, 'password' => $request->password]);
        $attmeptEmail = Auth::attempt(['email' => $request->username, 'password' => $request->password]);
        if($attemptUsername || $attmeptEmail){ 
            $auth = Auth::user(); 
            $success['token'] =  $auth->createToken('LaravelSanctumAuth')->plainTextToken; 
            $success['name'] =  $auth->name;
   
            return $this->handleResponse($success, 'User logged-in!');
        } 
        else{ 
            return $this->handleError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'email' => 'required|email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->handleError($validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('LaravelSanctumAuth')->plainTextToken;
        $success['username'] =  $user->username;
   
        return $this->handleResponse($success, 'User successfully registered!');
    }
}
