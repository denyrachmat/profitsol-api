<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\PORTAL\PortalEduDet;
use App\Models\User;
use App\Models\PORTAL\PortalApp;

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Portal"},
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="Username of login portal user",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password of login portal user",
     *         required=true,
     *         @OA\Schema(type="string", format="password")
     *     ),
     *     @OA\Response(response="200", description="Login portal")
     * )
     */
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
        if(($attemptUsername || $attmeptEmail) || $request->isMSLogin){
            $cekUser = User::where('username', $request->username)->first();
            Auth::loginUsingId($cekUser->id);

            // return Auth::check();
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

            $dataUsers = User::where('username', $auth->username)->first();

            $username = $auth->username;
            $getRolesGroup = User::where('username', $auth->username)->with(['roles.role.role_app_map' => function ($r) use ($username) {
                $r->with(['childRoles' => function ($q) use($username) {
                    $q->with('apps');
                    $q->whereHas('role.users_map', function ($h) use($username){
                        $h->where('u_username', $username);
                    });
                }, 'apps'])->whereNull('am_app_parent');
            }])->first();

            $success['token'] =  $auth->createToken('LaravelSanctumAuth')->plainTextToken;
            $success['username'] =  $auth->username;
            $success['user_det'] = $dataUsers->det;
            $success['edu'] = $edu;
            $success['fam'] = $dataUsers->fam;
            $success['rolesGroup'] = $getRolesGroup;
            $success['menus'] = PortalApp::where('am_app_parent', null)->with('childApps')->get();

            if (count($getRolesGroup['roles']) === 0) {
                return $this->handleError([
                    'password' => ["This user role not defined, please ask IT MIS to define it first !"]
                ]);
            }

            return $this->handleResponse($success, 'User logged-in!');
        } else {
            return $this->handleError([
                'password' => ["User or Password not match !"]
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Portal"},
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="Username of login portal user",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Email of login portal user",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password of login portal user",
     *         required=true,
     *         @OA\Schema(type="string", format="password")
     *     ),
     *     @OA\Parameter(
     *         name="confirm_password",
     *         in="query",
     *         description="Password Confirmation of login portal user",
     *         required=true,
     *         @OA\Schema(type="string", format="password")
     *     ),
     *     @OA\Response(response="200", description="Login portal")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'password_confirmation' => 'required|same:password',
        ]);

        if($validator->fails()){
            return $this->handleError($validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $user->det()->create([
            'u_username' => $request->username,
            'pud_first_name' => $request->pud_first_name,
            'pud_last_name' => $request->pud_last_name
        ]);

        $success['token'] =  $user->createToken('LaravelSanctumAuth')->plainTextToken;
        $success['username'] =  $user->username;

        return $this->handleResponse($success, 'User successfully registered!');
    }
}
