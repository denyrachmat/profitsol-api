<?php

namespace App\Http\Controllers\HRMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\HRMS\Auth\RoleRequest;
use App\Models\HRMS\Auth\RoleMappingMenu;

class RoleController extends Controller
{
    public function index()
    {
        $cek = RoleMappingMenu::select([
            'role_id',
            'menu_id'
        ])
        ->with('menu')
        ->with('group')
        ->with('user')
        ->get();

        return $cek;

        $hasil = [];
        foreach ($cek as $key => $value) {
            $hasil[$value['role_id']]['DESC'] = $value->group['division_name'];
            $hasil[$value['role_id']]['DATA'][] = $value;
        }

        return $hasil;
    }

    public function cekroleid($id)
    {
        return RoleMappingMenu::where('role_id', $id)
            ->with('menu.child')
            ->get();
    }

    public function store(RoleRequest $req, $met)
    {
        if ($met == 'new') {
            $id = $req->role_id;
            foreach ($req->selected as $key => $value) {
                RoleMappingMenu::insert([
                    'role_id' => $req->role_id,
                    'menu_id' => $value
                ]);
            }

            return 'success';
        } elseif ($met == 'update') {
            $id = $req->role_id;
            foreach ($req->selected as $key => $value) {
                $cekid = RoleMappingMenu::where('role_id', $id)->where('menu_id', $value)->first();

                if (!empty($cekid)) {
                    RoleMappingMenu::where('role_id', $id)->where('menu_id', $value)
                        ->update([
                            'role_id' => $id,
                            'menu_id' => $value
                        ]);
                } else {
                    RoleMappingMenu::insert([
                        'role_id' => $id,
                        'menu_id' => $value
                    ]);
                }
            }


            $cekdeleted = RoleMappingMenu::where('role_id', $id)->whereNotIn('menu_id', $req->selected)->delete();

            return $cekdeleted;
        } else {
            $cek = RoleMappingMenu::where('role_id', $req->role_id)
                ->doesnthave('menu')
                ->doesnthave('user');

            if (!empty($cek->first())) {
                RoleMappingMenu::where('role_id', $req->role_id)->delete();
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
            'role_id'
        ];

        return RoleMappingMenu::select($select)
            ->with('user.approval')
            ->with('group')
            ->groupBy($select)
            ->get()
            ->toArray();
    }
}
