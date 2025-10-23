<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

use App\Models\PORTAL\PortalEduDet;
use App\Models\User;
use App\Models\PORTAL\PortalApp;
use App\Traits\PORTAL\GencodeTraits;

class AuthController extends BaseController
{
    use GencodeTraits;
    public function __construct(HasherContract $hasher)
    {
        $this->hasher = $hasher;
    }
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
            'password' => $request->isMSLogin ? '' : 'required'
        ]);

        if ($validator->fails()) {
            return $this->handleError($validator->errors());
        }

        $attemptUsername = Auth::attempt(['username' => $request->username, 'password' => $request->password]);
        $attmeptEmail = Auth::attempt(['email' => $request->username, 'password' => $request->password]);
        if (($attemptUsername || $attmeptEmail) || $request->isMSLogin) {
            $cekUser = User::where('username', $request->username)->first();
            Auth::loginUsingId($cekUser->id, $request->has('remember') && $request->remember);

            if ($cekUser->is_ms_checking == 1 && $request->isMSLogin !== true) {
                return $this->handleError([
                    'password' => ["This user set as MS Login only, please login using Microsoft Authentication !"]
                ]);
            }

            // return Auth::check();
            $auth = Auth::user();
            $edu = PortalEduDet::where('u_username', $auth->username)
                ->get()->toArray();

            $dataUsers = User::where('username', $auth->username)->with('fam')->first();

            $username = $auth->username;

            $getRolesGroup = User::where('username', $auth->username)
                ->with([
                    'roles.role.role_app_map' => function ($query) {
                        $query->whereNull('am_app_parent')
                            ->with(['apps']); // Limited eager loading
                    }
                ])
                ->first();

            // return $getRolesGroup;

            $success['token'] = $auth->createToken('LaravelSanctumAuth')->plainTextToken;
            $success['username'] = $auth->username;
            $success['user_det'] = $dataUsers->det;
            $success['edu'] = $edu;
            $success['fam'] = $dataUsers->fam;
            $success['rolesGroup'] = $getRolesGroup;
            $success['menus'] = PortalApp::where('am_app_parent', null)->with('childApps')->get();
            $success['is_ms_checking'] = $cekUser->is_ms_checking;
            
            // For checking gencode user update FP
            $dataGencode = $this->getDataGencode(
                'UPDATE_FP', 
                ['pgm_value' => $username], 
                [
                    'ID_MENU' => 'pgm_value2|int'
                ]);
            
            $success['is_fpconf'] = [];
            foreach ($dataGencode as $key => $valueGencode) {
                $success['is_fpconf'][] = app(\App\Http\Controllers\API\PORTAL\FrontPageController::class)->getFPMenu($valueGencode['ID_MENU'])->getOriginalContent()['data'][0] ?? [];
            }

            if (count($getRolesGroup['roles']) === 0) {
                return $this->handleError([
                    'password' => ["This user role not defined, please ask IT MIS to define it first !"]
                ]);
            }

            if ($request->has('is_mobile') && $request->is_mobile === 1) {
                if ($cekUser->is_mobileacc == 0) {
                    return $this->handleError([
                        'password' => ["User not set up as mobile users, please add it on STX-I Portal !"]
                    ]);
                }
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

        if ($validator->fails()) {
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

        $success['token'] = $user->createToken('LaravelSanctumAuth')->plainTextToken;
        $success['username'] = $user->username;

        return $this->handleResponse($success, 'User successfully registered!');
    }

    public function forgot_password(Request $request)
    {
        $input = $request->only('email');
        $validator = Validator::make($input, [
            'email' => "required|email"
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $response = Password::sendResetLink($input);

        $message = $response == Password::RESET_LINK_SENT ? 'Mail send successfully' : 'Something went wrong, please contact administrator !!';

        return $this->handleResponse($response, $message);
    }

    public function change_password(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return $this->handleError('Check your old password.', $validator->errors());
        } else {
            $userid = $request->header('username');
            try {
                $user = User::where('username', $userid)->first();
                if ((Hash::check(request('old_password'), $user->password)) == false) {
                    return $this->handleError('Check your old password.', [
                        'old_password' => ['Check your old password.']
                    ]);
                } else if ((Hash::check(request('new_password'), $user->password)) == true) {
                    return $this->handleError('Please enter a password which is not similar then current password.', [
                        'old_password' => ['Please enter a password which is not similar then current password.']
                    ]);
                } else {
                    User::where('username', $userid)->update(['password' => Hash::make($input['new_password'])]);
                    return $this->handleResponse($user, 'Password updated successfully.');
                }
            } catch (\Exception $ex) {
                if (isset($ex->errorInfo[2])) {
                    $msg = $ex->errorInfo[2];
                } else {
                    $msg = $ex->getMessage();
                }
                $arr = array("status" => 400, "message" => $msg, "data" => array());
            }
        }
        return \Response::json($arr);
    }

    public function submitResetPasswordForm(Request $request, $token)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $request->email
            ])
            ->first();

        if (!$updatePassword || (!$this->hasher->check($request->token, $updatePassword->token))) {
            return $this->handleError('Invalid Token, please request forget password again !!');
        }

        $user = User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return $this->handleResponse($user, 'Your password has been changed!');
    }
}
