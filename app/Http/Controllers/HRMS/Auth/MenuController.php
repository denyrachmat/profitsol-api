<?php

namespace App\Http\Controllers\HRMS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Auth\MenuMaster;

class MenuController extends Controller
{
    public function index($cek)
    {
        if ($cek == 'parent') {
            return MenuMaster::with('child.child')->where('menu_parent_id', '0')->get();
        } elseif ($cek === 'mappingform') {
            return MenuMaster::with('child.child')
                ->where('menu_parent_id', '<>', '0')
                ->whereDoesntHave('child')
                ->with('parent')
                ->whereHas('parent', function ($q) {
                    $q->whereNotIn('id', [1, 17]);
                })
                ->get();
        } else {
            return MenuMaster::with('child.child')->with('parent')->get();
        }
    }

    public function cekmenuid($id)
    {
        return MenuMaster::where('menu_order', $id)->with('childMenu')->first();
    }

    public function getmenu(Request $req, $met)
    {
        if ($met == 'new') {
            $id = $req->menu_id;
            MenuMaster::insert([
                'menu_order' => $id,
                'menu_name' => $req->menu_name,
                'menu_desc' => $req->menu_desc,
                'menu_parent_id' => $req->menu_parent_id,
                'menu_url' => $req->menu_url,
                'menu_icon' => $req->menu_icon
            ]);

            return 'success';
        } elseif ($met == 'update') {
            $id = $req->menu_id;
            MenuMaster::where('id', $req->menu_id)->update([
                'menu_name' => $req->menu_name,
                'menu_desc' => $req->menu_desc,
                'menu_parent_id' => $req->menu_parent_id,
                'menu_url' => $req->menu_url,
                'menu_icon' => $req->menu_icon
            ]);

            return 'success';
        } else {
            $cek = MenuMaster::where('id', $req->menu_id)->doesnthave('child')->doesnthave('role');
            if (!empty($cek->first())) {
                MenuMaster::where('id', $req->menu_id)->delete();
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
}
