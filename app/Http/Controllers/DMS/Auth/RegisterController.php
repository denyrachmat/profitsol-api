<?php

namespace App\Http\Controllers\DMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Auth\UsersMaster;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\DMS\ActivationEmail;
use App\Http\Requests\DMS\Auth\RegisterRequest;
use App\Models\DMS\Auth\DomainMaster;

class RegisterController extends Controller
{
    protected function create(RegisterRequest $req)
    {        
        $usernya = UsersMaster::create([
            'username' => $req['username'],
            'first_name' => $req['fname'],
            'last_name' => $req['lname'],
            'email' => $req['email'],
            'password_hash' => Hash::make($req['password']),
            'password_sha' => hash('sha256', $req['password']),
            'role_id' => null,
            'token' => Str::random(40),
            'status' => 0
        ]);
        
        Mail::to($req['email'])->send(new ActivationEmail($usernya));

        return 'success';
    }

    public function verify($username, $token)
    {
        $cektoken = UsersMaster::where('username',$username);

        if (!empty($cektoken->where('token',$token)->first())) {
            $cektoken->update([
                'status' => 1
            ]);

            return 'Email verification success, now please wait administrator for reviewing your request and configuring your account.';
        } else {
            return 'Oops, your token is mismatch please make sure your url is right !!';
        }
    }

    public function GetAllUser()
    {
        return UsersMaster::with('group')->orderBy('role_id','asc')->get();
    }

    public function GetAllDomain()
    {
        return DomainMaster::get();
    }

    public function updateUserRole($username, $role)
    {
        return UsersMaster::where('username',$username)->update([
            'role_id' => $role
        ]);
    }

    public function updateuseractivation($username)
    {
        return UsersMaster::where('username',$username)->update([
            'status' => 1
        ]);
    }
}
