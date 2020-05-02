<?php

namespace App\Http\Controllers\PORTAL;

use App\Http\Controllers\Controller;
use App\Models\PORTAL\UsersPortal;
// use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PORTAL\ActivationEmail;
use Illuminate\Support\Str;
use App\Http\Requests\PORTAL\ResetPassRequest;
use Illuminate\Support\Facades\Response;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    // use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(Request $req)
    {        
        $usernya = UsersPortal::create([
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
    }

    public function verify($username, $token)
    {
        $cektoken = UsersPortal::where('username',$username);

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
        return UsersPortal::get();
    }

    public function ResetPassword(ResetPassRequest $req){
        $cek_user = UsersPortal::where('username', $req->username)->first();
        $cek = Hash::check($req->current_password, $cek_user['password_hash']);
        if ($cek) {
            $cek_user->update([
                'password_hash' => Hash::make($req->password),
                'password_sha' => hash('sha256', $req->password),
            ]);
            return 'berhasil';
        } else {
            return Response::json([
                "message" => "The given data was invalid.",
                "errors" => [
                    "username" => ["Password or username is wrong!!"]
                ]
            ], 422);
        }
    }
}
