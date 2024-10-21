<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PORTAL\PortalDomain;
use DB;

use App\Http\Controllers\API\PORTAL\BaseController;

class DomainController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->handleResponse(PortalDomain::get(), 'Data Found');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $insert = PortalDomain::create(array_merge(['p_u_username' => $request->header('username')], $request->all()));


        return $this->handleResponse($insert, 'Data Found');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function changeDomain($id)
    {
        $domain = PortalDomain::where('id', $id)->first();

        $listDataBases = [
            'PORTAL',
            'AMS',
            'CMS',
            'DMS',
            'MRS'
        ];

        foreach ($listDataBases as $key => $value) {
            DB::statement("CREATE DATABASE {$domain->pd_prefix_db}_{$value}");
        }

        config([
            "database.connections." => [
                'driver' => 'mysql',
                'host' => 'your_host',
                'database' => 'your_new_database_name',
                'username' => 'your_username',
                'password' => 'your_password',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]
        ]);
    }
}
