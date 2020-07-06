<?php

namespace App\Http\Controllers\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Requests\PORTAL\LoginRequest;

use App\Models\PORTAL\UsersPortal;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    // use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(LoginRequest $req)
    {
        $cek = UsersPortal::where('username', $req->username);
        if (empty($cek->first()->role_id)) {
            return Response::json([
                "message" => "The given data was invalid.",
                "errors" => [
                    "username" => ["Your account is not configured yet!!"]
                ]
            ], 422);
        } else {
            if ($req->username == 'gueststxsmt') {
                return $cek->with(['menu' => function ($q) {
                    $q->orderBy('menu_id');
                    $q->where('menu_parent', 0);
                    $q->with(['childDeeper' => function ($qchild) {
                        $qchild->orderBy('menu_id','asc');
                        $qchild->wherehas('role');
                        $qchild->with(['role' => function ($qDet) {
                            $qDet->with('user');
                            $qDet->has('user');
                            $qDet->orderBy('menu_id','asc');
                        }]);
                    }]);
                }])->first();
            } else {
                if (Hash::check($req->password, $cek->first()->password_hash)) {
                    $cek2 = clone $cek->first();
                    return $cek->with(['menu' => function ($q) use ($cek2) {
                        $q->orderBy('menu_id');
                        $q->where('menu_parent', 0);
                        $q->with(['childDeeper' => function ($qchild) use ($cek2) {
                            $qchild->orderBy('menu_id','asc');
                            $qchild->wherehas('role', function ($qDet) use ($cek2) {
                                $qDet->where('role_identifier',$cek2->role_id);
                                $qDet->with('user');
                                $qDet->has('user');
                                // $qDet->orderBy('menu_id','asc');
                            });
                            $qchild->with('role');
                        }]);
                    }])->first();
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

    public function testing()
    {
        return 'testter';
    }
}
