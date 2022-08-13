<?php

namespace App\Http\Controllers\API\DMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\DMSFolderMstr;
use App\Traits\DMS\FolderDocumentTraits;
use App\Http\Controllers\API\PORTAL\BaseController;

class FolderController extends BaseController
{
    use FolderDocumentTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        $stored = DMSFolderMstr::create([
            'p_u_username' => $this->getAliasFolderbyAuthor($request->p_u_username),
            'dfm_folder_name' => $request->dfm_folder_name,
            'dfm_parent_id' => $request->dfm_parent_id,
        ]);

        $data = DMSFolderMstr::where('id', $stored->id)->with('parentFolders')->first()->toArray();

        $createRealFolder = $this->createNewFolder($this->getAliasFolderbyAuthor($request->p_u_username), $this->pathCreator($data));

        return $this->handleResponse([
            'stored' => $stored,
            'store_real_folder' => $createRealFolder
        ], 'Folder created successfully !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = DMSFolderMstr::where('id', $id)->with('parentFolders')->first()->toArray();
        // return $data;
        return $this->pathCreator($data);
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
