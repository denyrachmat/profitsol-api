<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;

use App\Http\Requests\PORTAL\RoleRequest;

use App\Models\PORTAL\PortalRole;
use App\Models\PORTAL\PortalRoleAppMap;
use App\Models\PORTAL\PortalRoleUserMap;

class RoleController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->handleResponse(PortalRole::with('users_map')->with('app_map.apps.childApps')->get(), 'Data found !');
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
    public function store(RoleRequest $request)
    {
        $stored = PortalRole::create($request->all());

        if (count($request->app_map) > 0) {
            PortalRoleAppMap::create($request->app_map);
        }

        if (count($request->users_map) > 0) {
            PortalRoleUserMap::create($request->users_map);
        }

        return $this->handleResponse($stored, 'Store Successfull !');
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
    public function update(RoleRequest $req, $id)
    {
        $update = PortalRole::where('id', $id)->update([
            'u_username' => $req->u_username,
            'rm_role_name' => $req->rm_role_name,
            'rm_role_desc' => $req->rm_role_desc,
        ]);

        if (count($req->app_map) > 0) {
            PortalRoleAppMap::updateOrCreate([
                'rm_role_id' => $id,
                'u_username' => $req->u_username,
            ], $req->app_map);
        }

        if (count($req->users_map) > 0) {
            PortalRoleUserMap::updateOrCreate([
                'rm_role_id' => $id,
                'u_username' => $req->u_username,
            ], $req->users_map);
        }

        return $this->handleResponse($update, 'Update Successfull !');
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
