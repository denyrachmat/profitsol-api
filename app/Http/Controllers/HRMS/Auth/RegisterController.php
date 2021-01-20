<?php

namespace App\Http\Controllers\HRMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\HRMS\Auth\UserMaster;
use App\Models\HRMS\Core\Bio\UserPersonalDet;

use App\Http\Requests\HRMS\Auth\RegisterRequest;
use App\Http\Requests\HRMS\Auth\ChangePasswordRequest;
use App\Jobs\HRMS\sendDefaultLoginPass;

use App\Mail\HRMS\ActivationEmail;
use GuzzleHttp\Client;

class RegisterController extends Controller
{
    protected function create(RegisterRequest $req)
    {
        $hasil = $req->all();

        $hasil['password_hash'] = Hash::make($req->password);
        $hasil['password_sha'] = hash('sha256', $req->password);
        $hasil['status'] = 0;
        $hasil['token'] = Str::random(40);

        unset($hasil['password']);

        $usernya = UserMaster::create($hasil);

        Mail::to($req['email'])->send(new ActivationEmail($usernya));

        return $usernya;
    }

    public function verify($username, $token)
    {
        $cektoken = UserMaster::where('username', $username);

        if (!empty($cektoken->where('token', $token)->first())) {
            $cektoken->update([
                'status' => 1,
                'verified_at' => date('Y-m-d h:i:s')
            ]);

            UserPersonalDet::create([
                'username' => $username
            ]);

            return 'Email verification success, now please wait administrator for reviewing your request and configuring your account.';
        } else {
            return 'Oops, your token is mismatch please make sure your url is right !!';
        }
    }

    public function ResetPassword(ChangePasswordRequest $req)
    {
        $cek_user = UserMaster::where('username', $req->username)->first();
        $cek = Hash::check($req->current_password, $cek_user['password_hash']);
        if ($cek) {
            $cek_user->update([
                'password_hash' => Hash::make($req->password),
                'password_sha' => hash('sha256', $req->password),
            ]);
            return 'berhasil';
        } else {
            return response()->json([
                "message" => "The given data was invalid.",
                "errors" => [
                    "username" => ["Password or username is wrong!!"]
                ]
            ], 422);
        }
    }

    public function parsePassword()
    {
        $content = [];
        foreach (UserMaster::get()->toArray() as $key => $value) {
            $content[] = UserMaster::where('username', $value['username'])->update([
                'password_hash' => Hash::make($value['api_token'])
            ]);
        }

        return $content;
    }

    public function sendDefaultPass($user = '')
    {
        $userList = empty($user) ? UserMaster::get()->toArray() : UserMaster::where('username', $user)->get()->toArray();
        foreach ($userList as $key => $value) {
            $insertJob = (new sendDefaultLoginPass($value));

            dispatch($insertJob);
        }

        return 'success';
    }
}
