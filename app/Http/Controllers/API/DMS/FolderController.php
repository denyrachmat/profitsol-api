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
        $files = $this->getFolder($id);

        return $this->handleResponse($files, 'Data Found !!');
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
        $splitID = explode(",", base64_decode($id));
        $data = DMSFolderMstr::whereIn('id', $splitID)->with('parentFolders')->get()->toArray();

        // return $data;
        $deleteRealFolder = [];
        foreach ($data as $key => $value) {
            $delete = $this->deleteFolder($this->getAliasFolderbyAuthor($value['p_u_username']), $this->pathCreator($value));

            if ($delete) {
                DMSFolderMstr::where('id', $value['id'])->delete();
            }

            $deleteRealFolder[] = $this->pathCreator($value);
        }
        return $this->handleResponse([
            'deleted' => $data,
            'delete_real_folder' => $deleteRealFolder
        ], 'Folder deleted successfully !');
    }
}
