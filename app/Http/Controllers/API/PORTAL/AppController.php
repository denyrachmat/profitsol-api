<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Models\PORTAL\PortalApp;
use App\Models\PORTAL\PortalRoleAppMap;
use App\Http\Requests\PORTAL\AppsRequest;

class AppController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->handleResponse(PortalApp::with('childApps')->get(), 'Data Found !');
    }

    public function indexParentOnly()
    {
        return $this->handleResponse(PortalApp::with('childApps')->whereNull('am_app_parent')->get(), 'Data Found !');
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
    public function store(AppsRequest $request)
    {
        $stored = PortalApp::create($request->all());

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
        return $this->handleResponse(PortalApp::with('childApps')->where('am_is_shared', $id)->get(), 'Data Found !');
    }

    /**
     * Role ids that are assigned to an app code. Used at render time to gate
     * portal-menu links by the viewer's role, so author-time restrictions
     * cannot leak and later role changes take effect immediately.
     */
    public function appRoles($code)
    {
        $roleIds = PortalRoleAppMap::where('am_app_id', $code)
            ->pluck('rm_role_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        return $this->handleResponse($roleIds, 'Data Found !');
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
    public function update(AppsRequest $req, $id)
    {
        $update = PortalApp::where('am_app_code', $id)->update([
            'u_username' => $req->u_username,
            'am_app_code' => $req->am_app_code,
            'am_app_name' => $req->am_app_name,
            'am_app_icon' => $req->am_app_icon,
            'am_app_desc' => $req->am_app_desc,
            'am_app_url' => $req->am_app_url,
            'am_app_parent' => $req->am_app_parent,
            'am_is_files' => $req->am_is_files,
            'am_is_shared' => $req->am_is_shared,
        ]);

        PortalRoleAppMap::where('am_app_id', $id)->update([
            'u_username' => $req->u_username,
            'am_app_id' => $req->am_app_code,
            'am_app_parent' => $req->am_app_parent,
        ]);

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
