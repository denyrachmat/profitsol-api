<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Http\Request;
// use Illuminate\Foundation\Auth\User;
use App\Models\User;
use App\Models\PORTAL\PortalUserDet;

use App\Http\Requests\PORTAL\usersControllerUpdateRequest;
use App\Traits\PORTAL\GencodeTraits;

class UsersController extends BaseController
{
    use GencodeTraits;
    /**
     * @OA\Get(
     *     path="/api/portal/users",
     *     tags={"Portal"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Get list of all users")
     * )
     */
    public function index()
    {
        $data = User::with('det')->orderBy('email')->get()->toArray();

        return $this->handleResponse(array_map(function ($item) {
            $dataGencode = $this->getDataGencode(
                'UPDATE_FP', 
                ['pgm_value' => $item['username']], 
                [
                    'ID_MENU' => 'pgm_value2|int',
                    'view_option' => 'pgm_value3|string'
                ]);
            
            $dataFP = [];
            foreach ($dataGencode as $key => $valueGencode) {
                $dataFP[] = app(\App\Http\Controllers\API\PORTAL\FrontPageController::class)->getFPMenu($valueGencode['ID_MENU'])->getOriginalContent()['data'][0] ?? [];
            }

            $hasil = array_merge($item, $item['det'], [
                'is_fpconf' => $dataFP
            ]);
            
            unset($hasil['det']);

            return $hasil;
        }, $data), 'Data fetched !');
    }

    public function userActiveOnly($username = '')
    {
        $q = User::with(['det' => function($f) {
            $f->where('pud_is_active', 1);
        }])->whereHas('det', function($f) {
            $f->where('pud_is_active', 1);
        })->orderBy('email');

        if ($username != '') {
            $q->where('username', $username);
        }

        $data = $q->get()->toArray();

        return $this->handleResponse(array_map(function ($item) {
            $hasil = array_merge($item, $item['det']);
            unset($hasil['det']);

            return $hasil;
        }, $data), 'Data fetched !');
    }

    public function _flattened($array)
    {
        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $result[] = array_filter($item, function ($array) {
                    return !is_array($array);
                });
                $result = array_merge($result, $this->_flattened($item));
            }
        }
        return array_filter($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $users = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'email_verified_at' => $request->email_verified_at
        ]);

        $users->det()->create([
            'u_username' => $request->username,
            'pud_first_name' => $request->pud_first_name,
            'pud_last_name' => $request->pud_last_name
        ]);

        return $this->handleResponse([
            $users
        ], 'Create user Success !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(usersControllerUpdateRequest $request, $id)
    {
        $hasilUser = User::where('username', $id)->update([
            'email' => $request->email,
            'email_verified_at' => $request->email_verified_at
        ]);

        $hasilUserDet = PortalUserDet::where('u_username', $id)->update([
            'pud_first_name' => $request->pud_first_name,
            'pud_last_name' => $request->pud_last_name
        ]);

        return $this->handleResponse([
            $hasilUser,
            $hasilUserDet
        ], 'Update Success !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return User::where('username', base64_decode($id))->delete();
    }
}
