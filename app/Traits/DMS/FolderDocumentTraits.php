<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderRootMstr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FolderDocumentTraits
{
    public function getFolder($author, $id = null)
    {
        $users = $this->getAliasFolderbyAuthor($author, 'user');

        // return $users;
        $dataFolder = DMSFolderMstr::with(['childFolders'=> function($q) {
            $q->orderBy('dfm_folder_name');
        }])->with('doc')->where('p_u_username', $users)->whereNull('dfm_parent_id')->orderBy('dfm_folder_name');
        $dataFiles = DMSDocMstr::where('p_u_username', $users);

        return !empty($id)
            ? [
                'child_folders' => $dataFolder->where('id', $id)->get(),
                'doc' => $dataFiles->where('dfm_id', $id)->get()
            ]
            : [
                'child_folders' => $dataFolder->get(),
                'doc' => $dataFiles->whereNull('dfm_id')->get()
            ];
    }

    public function getAllFolder($author)
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->allFiles();
    }

    public function getAliasFolderbyAuthor($author, $data = 'path')
    {
        $checkRootAlias = DMSFolderRootMstr::where('p_u_username', $author)->first();

        $users = empty($checkRootAlias)
            ? 'DMS/' . $author
            : (
                $data === 'user'
                ? $author
                : $checkRootAlias->dudrm_path
            );
        $root = empty($checkRootAlias) ? 'data_folder' : $checkRootAlias->dudrm_source;

        return $data === 'path' || $data === 'user' ? $users : $root;
    }

    public function pathCreator($data, $hasil = '')
    {
        $hasil = $data['dfm_folder_name'];
        if (!empty($data['parent_folders'])) {
            return $this->pathCreator($data['parent_folders'], $data['dfm_folder_name']) . '/' . $hasil;
        }

        return $hasil;
    }

    public function createNewFolder($author, $path)
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->makeDirectory($this->getAliasFolderbyAuthor($author) . '/' . $path);
    }

    public function deleteFolder($author, $path)
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->deleteDirectory($this->getAliasFolderbyAuthor($author) . '/' . $path);
    }

    public function deleteFiles($author, $path, $file)
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->delete($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
    }

    public function openFiles($author, $path, $file)
    {
        $files = Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->get($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        $mime = Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->mimeType($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        return [
            'file' => $files,
            'mime' => $mime
        ];
    }

    public function uploadFiles($author, $path, $file, $contents)
    {
        return storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->put($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file, $contents);
    }

    public function getSizeFiles($author, $path, $file)
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->size($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
    }

    public function convertFolderPathToArray($author, $path = '', $parentKey = 0, $hasil = [])
    {
        $data = Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->directories($path === '' ? $this->getAliasFolderbyAuthor($author) : $path);

        // return $data;
        $kunci = 1;
        foreach ($data as $key => $value) {
            $path = !empty($path) ? $path . '/' . $value : $value;
            $hasil[] = [
                'key' => $parentKey + $kunci,
                'folders_name' => $value,
                'list_files' => Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->files($value),
                'children' => $this->convertFolderPathToArray($author, $value, $kunci, [])
            ];

            $kunci++;
        }

        if ($path === '') {
            return [
                'key' => 0,
                'folders_name' => $this->getAliasFolderbyAuthor($author),
                'list_files' => Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->files($path),
                'children' => $hasil
            ];
        }

        return $hasil;
    }

    public function migrateFolderToDB($author, $data = [])
    {
        if (count($data) === 0) {
            $data = $this->convertFolderPathToArray($author);
        }

        $hasil = [];
        foreach ($data as $key => $value) {
            $expFolder = explode('/', $value['folders_name']);
            $dataDBFolder = DMSFolderMstr::where('dfm_folder_name', $expFolder[count($expFolder) - 1])->first();
            $checkParent = count($expFolder) > 1
                ? DMSFolderMstr::where('dfm_folder_name', $expFolder[count($expFolder) - 2])->first()->id
                : NULL;

            if (empty($dataDBFolder)) {
                $insert = DMSFolderMstr::create([
                    'p_u_username' => $author,
                    'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                    'dfm_parent_id' => $checkParent
                ]);
                $idFolder = $insert->id;
                $hasilTemp = array_merge(
                    $insert->toArray(),
                    [
                        'status' => 'Inserted successfully !',
                        'children' => count( $value['children']) > 0 ? $this->migrateFolderToDB($author, $value['children']) : []
                    ]
                );
            } else {
                $idFolder = $dataDBFolder->id;
                $update = DMSFolderMstr::where('id', $idFolder)->create([
                    'p_u_username' => $author,
                    'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                    'dfm_parent_id' => $checkParent
                ]);
                $hasilTemp = array_merge(
                    $dataDBFolder->toArray(),
                    [
                        'status' => 'Alredy exists !',
                        'update' => $update,
                        'children' =>  count( $value['children']) > 0 ? $this->migrateFolderToDB($author, $value['children']) : []
                    ]
                );
            }

            $files = [];
            foreach ($value['list_files'] as $keyFile => $valueFile) {
                $expFile = explode('/', $valueFile);
                $dataDBFile = DMSDocMstr::where('ddm_doc_real_name', $expFile[count($expFile) - 1])->first();

                $getRealName = $expFile[count($expFile) - 1];
                $docName = 'DMS_'.Str::random(50).'.'.explode(".", $getRealName)[count(explode(".", $getRealName)) - 1];
                if (empty($dataDBFile)) {
                    $dataFolder = DMSFolderMstr::where('id', $idFolder)->with('parentFolders')->first()->toArray();

                    // logger(json_encode($dataFolder));
                    // logger($this->getAliasFolderbyAuthor($author) . '/' . $this->pathCreator($dataFolder) . '/' . $getRealName);
                    // logger(json_encode(Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->files($this->pathCreator($dataFolder))));
                    $insertFile = DMSDocMstr::create([
                        'p_u_username' => $author,
                        'dfm_id' => $idFolder,
                        'ddm_doc_name' => $docName,
                        'ddm_doc_real_name' => $getRealName,
                        'ddm_doc_size' => 0,
                        'ddm_doc_flag' => 0,
                    ]);
                    $files[] = array_merge(
                        $insertFile->toArray(),
                        [
                            'status' => 'Inserted successfully !'
                        ]
                    );
                } else {
                    $insertFile = DMSDocMstr::where('id', $dataDBFile->id)->update([
                        'p_u_username' => $author,
                        'dfm_id' => $idFolder,
                        'ddm_doc_name' => $docName,
                        'ddm_doc_real_name' => $getRealName,
                        'ddm_doc_size' => 0,
                        'ddm_doc_flag' => 0,
                    ]);
                    $files[] = array_merge(
                        $dataDBFile->toArray(),
                        [
                            'status' => 'Alredy exists !'
                        ]
                    );
                }
            }

            $hasil[] = array_merge(
                $hasilTemp,
                [
                    'files' => $files
                ]
            );
        }

        return $hasil;
    }

    public function migrateRealFileToDB($author)
    {
        $files = $this->migrateFolderToDB($author);
        // $files = $this->convertFolderPathToArray($author);

        return $files;
    }
}
