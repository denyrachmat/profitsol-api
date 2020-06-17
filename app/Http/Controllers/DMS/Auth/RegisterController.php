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
use App\Models\DMS\Auth\SignatureMaster;
use App\Http\Requests\DMS\Auth\UpdateUserRequest;
use Illuminate\Http\Response;
use App\Http\Requests\DMS\Auth\ChangePasswordRequest;

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
        $cektoken = UsersMaster::where('username', $username);

        if (!empty($cektoken->where('token', $token)->first())) {
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
        return UsersMaster::with('group')->orderBy('role_id', 'asc')->get();
    }

    public function GetAllDomain()
    {
        return DomainMaster::get();
    }

    public function updateUserRole($username, $role)
    {
        return UsersMaster::where('username', $username)->update([
            'role_id' => $role
        ]);
    }

    public function updateuseractivation($username)
    {
        return UsersMaster::where('username', $username)->update([
            'status' => 1
        ]);
    }

    public function datalogin($username)
    {
        $cek = UsersMaster::where('username', $username);
        $user = $cek->first();

        $data = $cek->with(['menu' => function ($q) use ($user) {
            $q->where('menu_parent', 0);
            $q->with(['child' => function ($qchild) use ($user) {
                $qchild->orderBy('id', 'desc');
                // $qchild->wherehas('role');
                $qchild->wherehas('role', function ($qDet) use ($user) {
                    $qDet->where('role_identifier', $user->role_id);
                    $qDet->with('user');
                    $qDet->has('user');
                });
                $qchild->with(['role' => function ($qDet) use ($user) {
                    $qDet->where('role_identifier', $user->role_id);
                    $qDet->with('user');
                    $qDet->has('user');
                }]);
            }]);
            $q->orderBy('id');
        }])
            ->with('signature')
            ->first();

        return $data;
    }

    public function updateuserprofile(Request $req)
    {
        $cekusers = UsersMaster::where('username', $req->username)->first();

        if ($cekusers->email !== $req->email) {
            $cekemail = UsersMaster::where('email', $req->email)->first();

            if (empty($cekemail)) {
                SignatureMaster::updateOrCreate(
                    [
                        'username' => $req->username
                    ],
                    [
                        'image_signature' => base64_encode($req->signature)
                    ]
                );

                $signature = SignatureMaster::where('username', $req->username)->first();

                UsersMaster::where('username', $req->username)->update([
                    'email' => $req->email,
                    'first_name' => $req->fname,
                    'last_name' => $req->lname,
                    'signature_id' => $signature->id
                ]);
            } else {
                return response([
                    'errors' => [
                        'approver' => ['Email already used by other user !!']
                    ]
                ], 422);
            }
        } else {
            SignatureMaster::updateOrCreate(
                [
                    'username' => $req->username
                ],
                [
                    'image_signature' => base64_encode($req->signature)
                ]
            );

            $signature = SignatureMaster::where('username', $req->username)->first();

            UsersMaster::where('username', $req->username)->update([
                'email' => $req->email,
                'first_name' => $req->fname,
                'last_name' => $req->lname,
                'signature_id' => $signature->id
            ]);
        }

        return $this->datalogin($req->username);
    }

    public function deletesignature($username)
    {
        UsersMaster::where('username', $username)->update([
            'signature_id' => null
        ]);

        return $this->datalogin($username);
    }

    public function ResetPassword(ChangePasswordRequest $req){
        $cek_user = UsersMaster::where('username', $req->username)->first();
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
