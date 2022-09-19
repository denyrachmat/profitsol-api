<?php

namespace App\Http\Controllers\API\DMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Support\Str;

use App\Traits\DMS\FolderDocumentTraits;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderMstr;

class DocumentController extends BaseController
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
    public function store(Request $req)
    {
        $result = [];
        foreach ($req->fileName as $key => $value) {
            $dataFolder = DMSFolderMstr::where('id', $req->dfm_id)->with('parentFolders')->first();
            // return $req->files[$key];
            $fileNameFormat = 'DMS_' . Str::random(50) . '.' . explode(".", $value)[1];
            $file = base64_decode(explode(",", $req->file_all[$key])[1]);
            $storeRealFile = $this->uploadFiles(
                $req->p_u_username,
                !empty($dataFolder) ? $this->pathCreator($dataFolder->toArray()) : '',
                $this->getAliasFolderbyAuthor($req->p_u_username, 'source') == 1
                    ? $value
                    : $fileNameFormat,
                $file,
            );

            // return $storeRealFile;

            if ($storeRealFile) {
                $stored = DMSDocMstr::create([
                    'p_u_username' => $req->p_u_username,
                    'dfm_id' => $req->dfm_id,
                    'ddm_doc_name' => $fileNameFormat,
                    'ddm_doc_real_name' => $value,
                    'ddm_doc_size' => $this->getSizeFiles(
                        $req->p_u_username,
                        !empty($dataFolder) ? $this->pathCreator($dataFolder->toArray()) : '',
                        $this->getAliasFolderbyAuthor($req->p_u_username, 'source') == 1
                            ? $value
                            : $fileNameFormat,
                    ),
                    'ddm_doc_flag' => $req->ddm_doc_flag,
                ]);

                $result[] = $stored;
            }
        }

        if (count($result) > 0) {
            return $this->handleResponse($stored, 'Store successfull !');
        } else {
            return $this->handleError("Something's wrong with the upload !");
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
        $getData = DMSDocMstr::where('id', $id)->with('folder.parentFolders')->first()->toArray();

        // return $getData;
        $files = $this->openFiles(
            $getData['p_u_username'],
            !empty($getData['folder']) ? $this->pathCreator($getData['folder']) : '',
            $this->getAliasFolderbyAuthor($getData['p_u_username'], 'source') == 1
                ? $getData['ddm_doc_real_name']
                : $getData['ddm_doc_name']
        );

        $hasil = [
            'base64Files' => 'data:' . $files['mime'] . ';base64,' . base64_encode($files['file']),
            'mime' => $files['mime'],
            'ext' => $files['ext'],
        ];
        // return $files;

        return $this->handleResponse($hasil, 'Data Found !!');
    }

    public function sourceOnly($id)
    {
        return $this->show($id)['data'];
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
        $data = DMSDocMstr::whereIn('id', $splitID)->with('folder.parentFolders')->get()->toArray();

        $deleteRealFiles = [];
        foreach ($data as $key => $value) {
            $delete = $this->deleteFiles(
                $this->getAliasFolderbyAuthor($value['p_u_username'], 'user'),
                !empty($value['folder']) ? $this->pathCreator($value['folder']) : '',
                $this->getAliasFolderbyAuthor($value['p_u_username'], 'source') == 1
                ? $value['ddm_doc_real_name']
                : $value['ddm_doc_name']
            );

            if ($delete) {
                DMSDocMstr::where('id', $value['id'])->delete();
            }

            $deleteRealFiles[] = $delete;
        }
        return $this->handleResponse([
            'deleted' => $data,
            'delete_real_folder' => $deleteRealFiles
        ], 'Files deleted successfully !');
    }
}
