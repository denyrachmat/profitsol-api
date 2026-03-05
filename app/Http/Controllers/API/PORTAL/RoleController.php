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
        return $this->handleResponse(PortalRole::with(['users_map' => function($query) {
            // Add any additional query constraints here if needed
            $query
                ->join('portal_users_det', 'portal_users_det.u_username', '=', 'portal_users_det.u_username')
                ->where('pud_is_active', 1);
        }])->with('role_app_map.apps.childApps')->get(), 'Data found !');
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

        if (count($request->role_app_map) > 0) {
            PortalRoleAppMap::create($request->role_app_map);
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
    public function show(Request $request, $id)
    {
        $data = PortalRole::with('users_map')
            ->with([
                'role_app_map' => function ($query) use ($id) {
                    $query->whereNull('am_app_parent');
                    // $query->where('u_username', $request->header('username'));
                    $query->with([
                        'childRoles' => function ($queryChild) use ($id) {
                        $queryChild->where('rm_role_id', $id);
                        // $queryChild->where('u_username', $request->header('username'));
                    }
                    ]);
                },
                'role_app_map.apps.childApps'
            ])
            ->find($id);

        return $this->handleResponse($data, 'Data found !');
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
        // return $req->all()['role_app_map'];
        // $update = PortalRole::where('id', $id)->update([
        //     'u_username' => $req->u_username,
        //     'rm_role_name' => $req->rm_role_name,
        //     'rm_role_desc' => $req->rm_role_desc,
        // ]);

        if (count($req->role_app_map) > 0) {
            PortalRoleAppMap::where('rm_role_id', $id)->delete();
            foreach ($req->role_app_map as $key => $valueRole) {
                $checkExist = PortalRoleAppMap::where('rm_role_id', $valueRole['rm_role_id'])
                    ->where('am_app_id', $valueRole['am_app_id'])
                    ->first();

                if ($checkExist) {
                    PortalRoleAppMap::where('rm_role_id', $valueRole['rm_role_id'])
                        ->where('am_app_id', $valueRole['am_app_id'])
                        ->delete();
                }

                PortalRoleAppMap::create($valueRole);
            }
        }

        if (count($req->users_map) > 0) {
            PortalRoleUserMap::where('rm_role_id', $req->id)->delete();
            foreach ($req->users_map as $key => $valueUsers) {
                PortalRoleUserMap::create($valueUsers);
            }
        }

        return $this->handleResponse([], 'Update Successfull !');
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
