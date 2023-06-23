<?php

namespace App\Http\Controllers\API\MACROLIST;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\PORTAL\BaseController;

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
        $listFolders = Storage::disk('macro_list')->directories(base64_decode($id));
        $listFiles = Storage::disk('macro_list')->files(base64_decode($id));

        $resFolders = [];
        foreach ($listFolders as $key => $value) {
            $expData = explode('/', $value);
            $resFolders[] = [
                'icon' => 'folder',
                'filename' => end($expData),
                'path' => $value
            ];
        }

        $resFiles = [];
        foreach ($listFiles as $key => $value) {
            $expData = explode('/', $value);
            $resFiles[] = [
                'icon' => 'las la-file-excel',
                'filename' => end($expData),
                'path' => $value
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
}
