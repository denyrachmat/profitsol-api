<?php

namespace App\Http\Controllers\API\DMS;

use Illuminate\Http\Request;
use App\Models\DMS\DMSDocRootMstr;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Http\Requests\DMS\DocumentRootStoreRequest;
use Illuminate\Filesystem\FilesystemManager;
use App\Models\DMS\DMSFolderRootMstr;
use Storage;
use Config;
use App\Traits\DMS\FolderDocumentTraits;

class DocumenRootController extends BaseController
{
    use FolderDocumentTraits;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DMSDocRootMstr::get()->toArray();

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

    public function getDataFilter(Request $request)
    {
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
                ->get()
                ->toArray();

            $hasil = [];
            foreach ($datanya as $key => $value) {
                try {
                    $this->installDisk($value['id']);

                    $check = Storage::disk($value['ddrm_name'])->exists('');
                    $status = true;
                } catch (\Throwable $th) {
                    $status = false;
                }

                $hasil[] = array_merge($value, [
                    'config_status' => $status,
                    'check_config' => Config::get('filesystems.disks'),
                    'check_list' => $this->checkPerm('deny-rachmat@sumitronics.co.jp', $value['ddrm_name'])
                ]);
            }

            return $this->handleResponse($hasil, 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
    }

    public function getMapping($rootName)
    {
        $data = DMSFolderRootMstr::where('dudrm_source', $rootName)->get()->pluck('p_u_username');

        if (count($data) > 0) {
            return $this->handleResponse($data, 'Data Found');
        }

        return $this->handleError('No data found !!');
    }

    public function storeMappingRoot(Request $request)
    {
        $insert = [];
        foreach ($request->det as $key => $value) {
            $insert[] = DMSFolderRootMstr::updateOrCreate([
                'p_u_username' => $value,
                'dudrm_source' => $request->ddrm_name,
            ], [
                'p_u_username' => $value,
                'dudrm_path' => '',
                'dudrm_source' => $request->ddrm_name,
                'dudrm_use_real_nm' => '',
                'dudrm_alias_username' => '',
            ]);
        }

        return $this->handleResponse($insert, 'Data Updated');
    }

    public function getRegisteredRoot($users)
    {
        $getListRoot = DMSFolderRootMstr::where('p_u_username', $users)->join('dms_doc_root_mstr', 'ddrm_name', 'dudrm_source')->get();

        if (count($getListRoot) > 0) {
            return $this->handleResponse($getListRoot, 'Data Found');
        }

        return $this->handleError('Data not found !!');
    }

    public function installDisk($id)
    {
        $getListRoot = DMSDocRootMstr::where('id', $id)->first();

        if ($getListRoot->ddrm_driver == 'local') {
            $result = config([
                'filesystems.disks.' . $getListRoot->ddrm_name => [
                    'driver' => 'local',
                    'root' => $getListRoot->ddrm_root
                ]
            ]);
        } else {
            $result = config([
                'filesystems.disks.' . $getListRoot->ddrm_name => [
                    'driver' => $getListRoot->ddrm_driver,
                    'host' => $getListRoot->ddrm_host,
                    'username' => $getListRoot->ddrm_username,
                    'password' => $getListRoot->ddrm_password
                ]
            ]);
        }

        return $this->handleResponse($result, 'Disk installed');
    }
}
