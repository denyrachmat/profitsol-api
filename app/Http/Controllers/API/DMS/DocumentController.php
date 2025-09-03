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
        // logger($req->all());
        $result = [];
        if (is_array($req->fileName)) {
            foreach ($req->fileName as $key => $value) {
                $dataFolder = DMSFolderMstr::where('id', $req->dfm_id)->with('parentFolders')->first();
                // return $req->files[$key];
                $fileNameFormat = 'DMS_' . Str::random(50) . '.' . explode(".", $value)[1];
                $file = base64_decode(explode(",", $req->file_all[$key])[1]);
                $storeRealFile = $this->uploadFiles(
                    $req->header('username'),
                    !empty($dataFolder) ? $this->pathCreator($dataFolder->toArray()) : '',
                    $this->getAliasFolderbyAuthor($req->header('username'), 'source') == 1
                    ? $value
                    : $fileNameFormat,
                    $file,
                    $req->dfm_root_mstr
                );

                // return $storeRealFile;
                // logger($storeRealFile);

                if ($storeRealFile) {
                    logger($this->getAliasFolderbyAuthor($req->header('username'), 'source'));
                    $stored = DMSDocMstr::create([
                        'p_u_username' => $req->header('username'),
                        'dfm_id' => $req->dfm_id,
                        'ddm_doc_name' => $fileNameFormat,
                        'ddm_doc_real_name' => $value,
                        'ddm_doc_size' => $this->getSizeFiles(
                            $req->header('username'),
                            !empty($dataFolder) ? $this->pathCreator($dataFolder->toArray()) : '',
                            $this->getAliasFolderbyAuthor($req->header('username'), 'source') == 1
                            ? $value
                            : $fileNameFormat,
                            $req->dfm_root_mstr
                        ),
                        'ddm_doc_flag' => $req->ddm_doc_flag,
                        'dfm_root_mstr' => $req->dfm_root_mstr
                    ]);

                    $result[] = $stored;
                }
            }
        } else {
            $dataFolder = DMSFolderMstr::where('id', $req->dfm_id)->with('parentFolders')->first();
            $files = base64_encode(file_get_contents($req->file));
            // return $req->files[$key];
            $fileNameFormat = 'DMS_' . Str::random(50) . '.' . explode(".", $req->filename)[1];
            // $file = base64_decode(explode(",", $req->file)[1]);
            $storeRealFile = $this->uploadFiles(
                $req->header('username'),
                !empty($dataFolder) ? $this->pathCreator($dataFolder->toArray()) : '',
                $this->getAliasFolderbyAuthor($req->header('username'), 'source') == 1
                ? $req->filename
                : $fileNameFormat,
                file_get_contents($req->file),
                $req->dfm_root_mstr
            );

            // return $storeRealFile;
            // logger($storeRealFile);
            if ($storeRealFile) {
                $stored = DMSDocMstr::updateOrCreate([
                    'dfm_id' => $req->dfm_id,
                    'ddm_doc_real_name' => $req->filename,
                    'dfm_root_mstr' => $req->dfm_root_mstr
                ], [
                    'p_u_username' => $req->header('username'),
                    'dfm_id' => $req->dfm_id,
                    'ddm_doc_name' => $fileNameFormat,
                    'ddm_doc_real_name' => $req->filename,
                    'ddm_doc_size' => $this->getSizeFiles(
                        $req->header('username'),
                        !empty($dataFolder) ? $this->pathCreator($dataFolder->toArray()) : '',
                        $req->filename,
                        $req->dfm_root_mstr
                    ),
                    'ddm_doc_flag' => $req->ddm_doc_flag,
                    'dfm_root_mstr' => $req->dfm_root_mstr
                ])->toArray();

                return $this->handleResponse(array_merge($stored, ['path' => $this->pathCreator($dataFolder->toArray())]), 'Store successfull !');
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
    public function show($id, $fileOnly = false)
    {
        $getData = DMSDocMstr::where('id', $id)->with('folder.parentFolders')->with('shared')->first()->toArray();

        $files = $this->openFiles(
            $getData['p_u_username'],
            !empty($getData['folder']) ? $this->pathCreator($getData['folder']) : '',
            $this->getAliasFolderbyAuthor($getData['p_u_username'], 'source') == 1
            ? $getData['ddm_doc_real_name']
            : $getData['ddm_doc_name'],
            empty($getData['folder']) ? $getData['dfm_root_mstr'] : $getData['folder']['dfm_root_mstr'],
            $getData['id']
        );

        // return $files;

        $hasil = [
            'data' => $getData,
            'base64Files' => 'data:' . $files['mime'] . ';base64,' . base64_encode($files['file']),
            'mime' => $files['mime'],
            'ext' => $files['ext'],
            'filename' => $getData['ddm_doc_real_name'],
        ];

        // return $files;
        if ($fileOnly) {
            return $hasil;
        }

        return $this->handleResponse($hasil, 'Data Found !!');
    }

    public function sourceOnly($id)
    {
        return $this->show($id, true);
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
                : $value['ddm_doc_name'],
                $value['dfm_root_mstr']
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
