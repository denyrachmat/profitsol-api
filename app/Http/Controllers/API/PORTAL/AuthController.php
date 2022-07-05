<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\PORTAL\PortalEduDet;
use App\Models\User;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return $this->handleError($validator->errors());       
        }

        $attemptUsername = Auth::attempt(['username' => $request->username, 'password' => $request->password]);
        $attmeptEmail = Auth::attempt(['email' => $request->username, 'password' => $request->password]);
        if($attemptUsername || $attmeptEmail){ 
            $auth = Auth::user(); 
            $edu = PortalEduDet::select(
                DB::raw('pusd_level as sch_type'),
                DB::raw('pusd_sch_name as sch_name'),
                DB::raw('pusd_sch_majors as sch_major'),
                DB::raw('pusd_sch_minors as sch_minor'),
                DB::raw('pusd_sch_end as sch_grade_years'),
                DB::raw('pusd_grade as sch_grade'),
            )->where('u_username', $auth->username)
            ->get()->toArray();
            
            $success['token'] =  $auth->createToken('LaravelSanctumAuth')->plainTextToken;
            $success['username'] =  $auth->username;
            $success['user_det'] = User::where('username', $auth->username)->first()->det;
            $success['edu'] = $edu;
            $success['fam'] = User::where('username', $auth->username)->first()->fam;
   
            return $this->handleResponse($success, 'User logged-in!');
        } 
        else{ 
            return $this->handleError([
                'password' => ["User or Password wrong !"]
            ]);
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
