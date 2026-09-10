<?php

namespace App\Http\Controllers\API\DMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Traits\DMS\FolderDocumentTraits;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Traits\PORTAL\GencodeTraits;
use App\Models\PORTAL\PortalGencode;

class FolderController extends BaseController
{
    use FolderDocumentTraits, GencodeTraits;
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
            'p_u_username' => $this->getAliasFolderbyAuthor($request->p_u_username, 'user'),
            'dfm_folder_name' => $request->dfm_folder_name,
            'dfm_parent_id' => $request->dfm_parent_id,
            'dfm_root_mstr' => $request->dfm_root_mstr,
        ]);

        $data = DMSFolderMstr::where('id', $stored->id)->with('parentFolders')->first()->toArray();
        if ($request->has('from_sharepoint') && $request->from_sharepoint == true) {
            PortalGencode::updateOrCreate(
                [
                    'pgm_code' => 'DMS_SHAREPOINT_SHARED',
                    'pgm_value' => $stored->id,
                ],[
                    'pgm_code' => 'DMS_SHAREPOINT_SHARED',
                    'pgm_value' => $stored->id,
                    'pgm_value2' => json_encode($request->sites),
                    'pgm_value3' => $request->url,
                    'pgm_desc' => 'Folder imported from SharePoint'
                ]
            );
        }
        try {
            $createRealFolder = $this->createNewFolder($this->getAliasFolderbyAuthor($request->p_u_username, 'user'), $this->pathCreator($data), $request->dfm_root_mstr);
            return $this->handleResponse([
                'stored' => $stored,
                'store_real_folder' => $createRealFolder
            ], 'Folder created successfully !');
        } catch (\Throwable $th) {
            DMSFolderMstr::where('id', $stored->id)->delete();

            return $this->handleError($th->getMessage());
        }

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

    public function showList($username, $root, $idParentFolder = null, $isFetchAll = false, $id = 0)
    {
        $files = $this->getFolder($username, $idParentFolder, $root, $isFetchAll, true, $id);

        return $this->handleResponse($files, 'Data Found !!');
    }

    /**
     * Flat folder + file listing under a specific storage path.
     * Query: ?recursive=1 to include all children recursively.
     */
    public function browse(Request $request, $users, $root, $path = null)
    {
        try {
            $recursive = filter_var($request->query('recursive', false), FILTER_VALIDATE_BOOLEAN);
            $data = $this->browsePath($users, $root, $path ?? '', $recursive);

            if ($request->query('debug')) {
                return $this->handleResponse([
                    'rows' => $data,
                    'debug' => [
                        'scan_path' => $this->browseScanPath,
                        'recursive' => $recursive,
                        'total' => count($data),
                    ],
                ], 'Data Found !!');
            }

            return $this->handleResponse($data, 'Data Found !!');
        } catch (\Throwable $th) {
            return $this->handleError($th->getMessage());
        }
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $splitID = explode(",", base64_decode($id));
        $data = DMSFolderMstr::whereIn('id', $splitID)->with('parentFolders')->get()->toArray();

        $deleteRealFolder = [];
        foreach ($data as $key => $value) {
            $delete = $this->deleteFolder(
                $request->header('username'), 
                $this->pathCreator($value), 
                $value['dfm_root_mstr']
            );

            if ($delete) {
                DMSFolderMstr::where('id', $value['id'])->delete();
                DMSDocMstr::where('dfm_id', $value['id'])->delete();
            }

            $deleteRealFolder[] = $this->pathCreator($value);
        }
        return $this->handleResponse([
            'deleted' => $data,
            'delete_real_folder' => $deleteRealFolder
        ], 'Folder deleted successfully !');
    }
}
