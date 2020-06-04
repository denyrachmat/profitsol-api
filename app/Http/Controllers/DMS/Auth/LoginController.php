<?php

namespace App\Http\Controllers\DMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Auth\UsersMaster;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\DMS\Auth\LoginRequest;
use Illuminate\Support\Carbon;

class LoginController extends Controller
{
    public function login(LoginRequest $req)
    {
        $cek = UsersMaster::where('username', $req->username);

        if (empty($cek->first()->role_id)) {
            return Response::json([
                "message" => "The given data was invalid.",
                "errors" => [
                    "username" => ["Your account is not configured yet!!"]
                ]
            ], 422);
        } else {
            if (Hash::check($req->password, $cek->first()->password_hash)) {
                $user = $cek->first();
                $tokenResult = $user->createToken('Personal Access Token');
                $token = $tokenResult->token;

                $data = $cek->with(['menu' => function ($q) use ($user) {
                    $q->where('menu_parent', 0);
                    $q->with(['child' => function ($qchild) use ($user) {
                        $qchild->orderBy('id','desc');
                        // $qchild->wherehas('role');
                        $qchild->wherehas('role', function ($qDet) use ($user) {
                            $qDet->where('role_identifier',$user->role_id);
                            $qDet->with('user');
                            $qDet->has('user');
                        });
                        $qchild->with(['role' => function ($qDet) use ($user) {
                            $qDet->where('role_identifier',$user->role_id);
                            $qDet->with('user');
                            $qDet->has('user');
                        }]);
                    }]);
                    $q->orderBy('id');
                }])->first();

                if ($req->remember_me) {
                    $token->expires_at = Carbon::now()->addWeeks(1);
                    $token->save();
                }

                return response()->json([
                    'access_token' => $tokenResult->accessToken,
                    'token_type' => 'Bearer',
                    'expires_at' => Carbon::parse(
                        $tokenResult->token->expires_at
                    )->toDateTimeString(),
                    'data' => $data
                ]);
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
}
