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
        $arr = [
            'OQOVc6',
            'nzwHOT',
            'QChqOQ',
            'JWFCOr',
            'QVKkNJ',
            'UeGjGK',
            'wL0pxx',
            'SElX9a',
            'NdH2Ak',
            'Zt8pcC',
            '6dqC0A',
            'A9FeD9',
            'Jqn7HV',
            'nICOqJ',
            'SHIF40',
            'Kx3Iur',
            '172KqG',
            'UGVviw',
            '92OYm6',
            'gWsM45',
            'vzpCtH',
            'pIHIYC',
            '08V71X',
            'yP6rNi',
            'Jg7Nzg',
            'Aqwqgo',
            'pAqkpj',
            'P7qaLe',
            'Cn7cvj',
            'd2fDN6',
            'tKWkM8',
            'O5Tael',
            'xaD8gi',
            'yceUhR',
            'enejvg',
            'b0hrLC',
            'zPNcgh',
            'FntvG7',
            'PEbNec',
            'TUOpTg',
            '6XMalE',
            'DlyPTH',
            '2QP4Mh',
            'fy4dst',
            'hIC1Pi',
            'WxBYlK',
            'MPevNx',
            'y0KaRm',
            'LLIFl2',
            'DtjBKo',
            'ynUPwW',
            'rXl3UP',
            '5IvMdi',
            'gx66lg',
            'HphSn3',
            'OovXW6',
            'YPuRTO',
            'UHsKJl',
            '8hwuQE',
            'Q4jlHY',
            'EsAK69',
            '0yo0mr',
            'tuNJLf',
            'AFsmUN',
            '18GXez',
            'noUH1d',
            'c2cGx5',
            'tbz8UG',
            '8CaM9C',
            'tOrsuw',
            'JN441k',
            'etkukW',
            'ylQpt1',
            'frACKY',
            'OedOjY',
            'Kd1sF2',
            '6S8Kbf',
            'tHgqli',
            'b6Ug7W',
            '0kaWYB',
            'wTn5gt',
            'FdOnA9',
            'zybU4R',
            'H5gG2g',
            'o4p96T',
            'Rg4rDZ',
            'yj96id',
            'wwcUEw',
            'T7Jy3w',
            'QK81a7',
            'Vd2dAi',
            '3O49yN',
            'xFNkev',
            'KuabgH',
        ];

        $content = [];
        foreach ($arr as $key => $value) {
            $content[] = Hash::make($value);
        }

        return $content;
    }

    public function sendDefaultPass()
    {
        foreach (UserMaster::where('username', 'deny')->get()->toArray() as $key => $value) {
            $insertJob = (new sendDefaultLoginPass($value));

            dispatch($insertJob);
        }

        return 'success';
    }
}
