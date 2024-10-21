<?php

namespace App\Http\Controllers\API\DMS;

use Illuminate\Http\Request;
use App\Models\DMS\DMSDocRootMstr;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Http\Requests\DMS\DocumentRootStoreRequest;
use Illuminate\Filesystem\FilesystemManager;

class DocumenRootController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DMSDocRootMstr::get()
            ->toArray();

        return $this->handleResponse($data, 'Data Found !!');
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
    public function store(DocumentRootStoreRequest $request)
    {
        $insert = DMSDocRootMstr::create($request->all());

        $fsMgr = new FilesystemManager(app());

        switch ($request->ddrm_driver) {
            case 'local':
                $fsMgr->createLocalDriver([
                    'root' => $request->ddrm_root
                ]);
                break;
            default :
                $fsMgr->createFtpDriver([
                    'host' => $request->ddrm_host,
                    'username' => $request->ddrm_username,
                    'password' => $request->ddrm_password,
                ]);
        }

        return $this->handleResponse($insert, 'Document Root Created !!');
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

    public function getDataFilter(Request $request) {
        $hist = new DMSDocRootMstr;

        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $key => $value) {
                if (isset($value['step']) && $value['step'] === 'or') {
                    $hist = (clone $hist)->orwhere($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                } else {
                    $hist = (clone $hist)->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return $this->handleResponse($datanya, 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
    }
}
