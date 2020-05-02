<?php

namespace App\Http\Controllers\DMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Auth\RolesMaster;
use App\Http\Requests\DMS\Auth\RoleRequest;

class RoleController extends Controller
{
    public function index()
    {
        $cek = RolesMaster::select([
            'role_identifier',
            'menu_id'
        ])->with('menu')
            ->with('group')
            ->with('user')
            ->get();

        $hasil = [];
        foreach ($cek as $key => $value) {
            $hasil[$value['role_identifier']]['DESC'] = $value->group['division_name'];
            $hasil[$value['role_identifier']]['DATA'][] = $value;
        }

        return $hasil;
    }

    public function cekroleid($id)
    {
        return RolesMaster::where('role_identifier', $id)
            ->with('menu.child')
            ->get();
    }

    public function store(RoleRequest $req, $met)
    {
        if ($met == 'new') {
            $id = $req->role_id;
            foreach ($req->selected as $key => $value) {
                RolesMaster::insert([
                    'role_identifier' => $req->role_id,
                    'menu_id' => $value
                ]);
            }

            return 'success';
        } elseif ($met == 'update') {
            $id = $req->role_id;
            foreach ($req->selected as $key => $value) {
                $cekid = RolesMaster::where('role_identifier', $id)->where('menu_id', $value)->first();

                if (!empty($cekid)) {
                    RolesMaster::where('role_identifier', $id)->where('menu_id', $value)
                        ->update([
                            'role_identifier' => $id,
                            'menu_id' => $value
                        ]);
                } else {
                    RolesMaster::insert([
                        'role_identifier' => $id,
                        'menu_id' => $value
                    ]);
                }
            }


            $cekdeleted = RolesMaster::where('role_identifier', $id)->whereNotIn('menu_id', $req->selected)->delete();

            return $cekdeleted;

            // $id = $req->role_id;
            // foreach ($req->selected as $key => $value) {
            //     RolesMaster::where('role_identifier', $req->role_id)
            //         ->where('menu_id', $value)
            //         ->update([
            //             'role_identifier' => $id,
            //             'menu_id' => $value
            //         ]);
            // }
        } else {
            $cek = RolesMaster::where('role_identifier', $req->role_id)
                ->doesnthave('menu')
                ->doesnthave('user');

            if (!empty($cek->first())) {
                RolesMaster::where('role_identifier', $req->role_id)->delete();
                return 'success';
            } else {
                return response([
                    'message' => "The given data was invalid.",
                    'errors' => [
                        'menu_id' => ['Data has been used somewhere!!']
                    ]
                ], 422);
            }
        }
    }
    
    public function groupFetch()
    {
        $select = [
            'role_identifier'
        ];

        return RolesMaster::select($select)
            ->with('user.approval')
            ->with('group')
            ->groupBy($select)
            ->get()
            ->toArray();
    }
}
