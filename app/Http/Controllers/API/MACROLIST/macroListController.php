<?php

namespace App\Http\Controllers\API\MACROLIST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\PORTAL\PortalApp;
class macroListController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $listData = Storage::disk('macro_list')->directories();

        $resFolders = [];
        foreach ($listData as $key => $value) {
            $expData = explode('/', $value);
            $resFolders[] = [
                'icon' => 'folder',
                'filename' => end($expData),
                'path' => $value
            ];
        }

        return $this->handleResponse($resFolders, 'Data Found !!');
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
        // PortalApp::create([]);
        $hasil = [];
        foreach ($request->selectedMacro as $key => $value) {
            if (str_contains($value, '.')) {
                $getLatestID = PortalApp::where('am_app_code', 'like', 'M%')->orderBy('created_at', 'desc')->first();
                $explodeFilePath = explode('/', $value);
                $fileName = $explodeFilePath[count($explodeFilePath) - 1];
                $hasil[] = PortalApp::create([
                    'u_username' => $request->u_username,
                    'am_app_code' => empty($getLatestID) ? 'M0001' : 'M'.sprintf('%04d', (int) substr($getLatestID->am_app_code, -3) + 1),
                    'am_app_name' => $fileName,
                    'am_app_desc' => 'Macro system',
                    'am_app_url' => $value,
                    'am_app_icon' => 'las la-file-excel',
                    'am_app_parent' => PortalApp::where('id', $request->idMenu)->first()->am_app_code,
                    'am_is_files' => 1
                ]);
            }
        }

        return $this->handleResponse($hasil, 'Data Inserted !!'); 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $listFolders = Storage::disk('macro_list')->directories(base64_decode($id));
        $listFiles = Storage::disk('macro_list')->files(base64_decode($id));

        $resFolders = [];
        foreach ($listFolders as $key => $value) {
            $expData = explode('/', $value);
            $resFolders[] = [
                'icon' => 'folder',
                'filename' => end($expData),
                'path' => $value,
            ];
        }

        $resFiles = [];
        foreach ($listFiles as $key => $value) {
            $portalMenu = PortalApp::where('am_app_url', $value)->first();
            $expData = explode('/', $value);
            $resFiles[] = [
                'icon' => 'las la-file-excel',
                'filename' => end($expData),
                'path' => $value,
                'AppRole' => empty($portalMenu) ? '' : $portalMenu->am_app_code
            ];
        }

        $combineData = array_merge($resFolders, $resFiles);

        return $this->handleResponse($combineData, 'Data Found !!');
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

    public function download($path) {
        return Storage::disk('macro_list')->download(base64_decode($path));
    }

    public function listFolderStxiWebSystem(){
        $resFolders = PortalApp::with('childApps')->where('am_app_code', 'A005')->first()->childApps;

        $hasil = [];
        foreach ($resFolders as $key => $value) {
            $hasil[] = [
                'label' => $value->am_app_name,
                'value' => $value->id
            ];
        }
        return $this->handleResponse($hasil, 'Data Found !!');
    }
}
