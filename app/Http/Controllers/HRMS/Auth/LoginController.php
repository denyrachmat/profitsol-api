<?php

namespace App\Http\Controllers\HRMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

use App\Models\HRMS\Auth\UserMaster;

use App\Http\Requests\HRMS\Auth\LoginRequest;

class LoginController extends Controller
{
    public function login(LoginRequest $req)
    {
        $cek = UserMaster::where('username', $req->username);

        // return 'uess';

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

                $data = $cek
                    ->with(['roleMaster.mappingMenu' => function ($q) {
                        $q->where('parent_menu_id', 0);
                        $q->with('menu');
                        $q->with(['childRoleMenu' => function ($qdet) {
                            $qdet->with('menu');
                        }]);
                        $q->orderBy('menu_id');
                    }])
                    ->first();
                    
                    // return $data;

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
                return response()->json([
                    "message" => "The given data was invalid.",
                    "errors" => [
                        "username" => ["Password or username is wrong!!"]
                    ]
                ], 422);
            }
        }
    }
}
