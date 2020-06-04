<?php

namespace App\Http\Controllers\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PORTAL\MenusPortal;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($cek)
    {
        if ($cek == 'parent') {
            return MenusPortal::with('child.child')->where('menu_parent','0')->get();
        } else {
            return MenusPortal::with('child.child')->get();
        }
    }

    public function ceklistmenubyparent($parent, $ex = null)
    {
        $hasil = MenusPortal::with('child.child')
            ->where('menu_parent',$parent);

        if ($ex == null) {
            return $hasil->get();
        }

        return $hasil->where('id','<>',$ex)->get();
    }

    public function cekmenuid($id)
    {
        return MenusPortal::where('id',$id)->first();
    }

    public function getmenu(Request $req, $met)
    {
        if ($met == 'new') {            
            $id = $req->menu_id;
            MenusPortal::insert([
                // 'id' => $id,
                'menu_name' => $req->menu_nm,
                'menu_desc' => $req->menu_desc,
                'menu_parent' => $req->menu_parent,
                'menu_url' => $req->menu_url,
                'menu_icon' => $req->menu_icon
            ]);

            return 'success';
        } elseif ($met == 'update') {
            $id = $req->menu_id;
            MenusPortal::where('id', $req->menu_id)->update([
                'menu_name' => $req->menu_nm,
                'menu_desc' => $req->menu_desc,
                'menu_parent' => $req->menu_parent,
                'menu_url' => $req->menu_url,
                'menu_icon' => $req->menu_icon
            ]);

            return 'success';
        } else {
            $cek = MenusPortal::where('id', $req->menu_id)->doesnthave('child')->doesnthave('role');
            if (!empty($cek->first())) {
                MenusPortal::where('id', $req->menu_id)->delete();
                return 'success';
            } else {
                return response([
                    'message' => "The given data was invalid.",
                    'errors' => [
                        'menu_id' => ['Data has been used somewhere!!']
                    ]
                    ],422);
            }
        }
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return MenusPortal::where('menu_url',$id)->with('child')->first();
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
