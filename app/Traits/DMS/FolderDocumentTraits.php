<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderRootMstr;
use App\Models\DMS\DMSDocRootMstr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Config;
use Illuminate\Http\Request;

trait FolderDocumentTraits
{
    public function getFolder($author, $id = null, $root = '')
    {
        $users = $this->getAliasFolderbyAuthor($author, 'user');

        // return $users;
        $dataFolder = DMSFolderMstr::with([
            'childFolders' => function ($q) {
                $q->orderBy('dfm_folder_name');
            }
        ])->with('doc')
            ->where('p_u_username', $users)
            ->whereNull('dfm_parent_id')
            ->orderBy('dfm_folder_name');

        $dataFiles = DMSDocMstr::where('p_u_username', $users);

        if (!empty($root)) {
            $dataFolder->where('dfm_root_mstr', $root);
            $dataFiles->where('dfm_root_mstr', $root);
        }

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

    public function getAllFolder($author, $root = '')
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->allFiles();
    }

    public function getAliasFolderbyAuthor($author, $data = 'path', $root = '')
    {

        $checkRootAliasTest = DMSFolderRootMstr::where('p_u_username', $author)->first();
        $checkRootAlias = $checkRootAliasTest;

        if (!empty($checkRootAliasTest->dudrm_alias_username)) {
            $checkRootAlias = DMSFolderRootMstr::where('p_u_username', $checkRootAliasTest->dudrm_alias_username)->first();
        }

        $users = empty($checkRootAlias)
            ? 'DMS/' . $author
            : ($data === 'user'
                ? $checkRootAlias->p_u_username
                : ($checkRootAlias->dudrm_path)
            );


        if (empty($root)) {
            $root = empty($checkRootAlias->dudrm_source)
                ? 'data_folder'
                : $checkRootAlias->dudrm_source;
        } else {
            $checkID = DMSDocRootMstr::where('ddrm_name', $root)->first();
            $this->installDisk($checkID->id);
        }

        $isUseRealNameFile = empty($checkRootAlias)
            ? 0
            : $checkRootAlias->dudrm_use_real_nm;

        return $data === 'path' || $data === 'user' || $data === 'source'
            ? ($data === 'source'
                ? $isUseRealNameFile
                : $users
            )
            : $root;
    }

    public function pathCreator($data, $hasil = '')
    {
        $hasil = $data['dfm_folder_name'];
        if (!empty($data['parent_folders'])) {
            return $this->pathCreator($data['parent_folders'], $data['dfm_folder_name']) . '/' . $hasil;
        }

        return $hasil;
    }

    public function createNewFolder($author, $path, $root = '')
    {
        // return Config::get('filesystems.disks');
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->makeDirectory($this->getAliasFolderbyAuthor($author) . '/' . $path);
    }

    public function deleteFolder($author, $path, $root = '')
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->deleteDirectory($this->getAliasFolderbyAuthor($author) . '/' . $path);
    }

    public function deleteFiles($author, $path, $file, $root = '')
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->delete($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
    }

    public function openFiles($author, $path, $file, $root = '')
    {
        // logger($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        // return $this->getAliasFolderbyAuthor($author, 'path') . '/' . $path . '/' . $file;
        $files = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->get($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        $mime = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->mimeType($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        $ext = explode('.', $file)[1];
        return [
            'file' => $files,
            'mime' => $mime,
            'ext' => $ext,
            'test' => $this->getAliasFolderbyAuthor($author, 'path') . '/' . $path . '/' . $file
        ];
    }

    public function uploadFiles($author, $path, $file, $contents, $root = '')
    {
        return storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->put($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file, $contents);
    }

    public function getSizeFiles($author, $path, $file, $root = '')
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->size($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
    }

    public function convertFolderPathToArray($author, $path = '', $parentKey = 0, $hasil = [], $root = '')
    {
        // return [$this->getAliasFolderbyAuthor($author, 'root'), $path === '' ? $this->getAliasFolderbyAuthor($author) : $path];
        $data = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->directories($path === '' ? $this->getAliasFolderbyAuthor($author) : $path);
        // return $path === '' ? $this->getAliasFolderbyAuthor($author) : $path;

        // return $data;
        $kunci = 1;
        $pathDet = $path;
        foreach ($data as $key => $value) {
            $pathDet = !empty($path) ? $pathDet . '/' . $value : $value;
            $hasil[] = [
                'key' => $parentKey + $kunci,
                'folders_name' => $value,
                'list_files' => Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->files($value),
                'children' => $this->convertFolderPathToArray($author, $value, $kunci, [], $root)
            ];

            $kunci++;
        }

        if ($parentKey === 0) {
            return [
                'key' => 0,
                'folders_name' => $path,
                'list_files' => Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->files($pathDet),
                'children' => $hasil
            ];
        }

        return $hasil;
    }

    public function migrateFolderToDB($author, $path = '', $data = [], $root = '')
    {
        if (count($data) === 0) {
            $data = $path === ''
                ? $this->convertFolderPathToArray($author, $path, 0, [], $root)
                : [$this->convertFolderPathToArray($author, $path, 0, [], $root)];
        }

        $hasil = [];
        foreach ($data as $key => $value) {
            $expFolder = explode('/', $value['folders_name']);

            $dataDBFolder = DMSFolderMstr::where('dfm_folder_name', $expFolder[count($expFolder) - 1])->first();

            $cekParent = null;
            if (count($expFolder) > 1) {
                $cekParent = DMSFolderMstr::where('dfm_folder_name', $expFolder[count($expFolder) - 2])->orderBy('id', 'desc')->first();
            }

            $checkParent = !empty($cekParent) && count($expFolder) > 1
                ? $cekParent->id
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
                        'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], false) : []
                    ]
                );
            } else {
                $idFolder = $dataDBFolder->id;
                $checkParent2 = DMSFolderMstr::where('id', $idFolder)->where('dfm_parent_id', $checkParent)->first();

                $update = DMSFolderMstr::create([
                    'p_u_username' => $author,
                    'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                    'dfm_parent_id' => $checkParent
                ]);

                $idFolder = $update->id;
                // if (empty($checkParent2)) {
                //     $update = DMSFolderMstr::insert([
                //         'p_u_username' => $author,
                //         'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                //         'dfm_parent_id' => $checkParent
                //     ]);
                // } else {
                //     $update = DMSFolderMstr::where('id', $idFolder)->update([
                //         'p_u_username' => $author,
                //         'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                //         'dfm_parent_id' => $checkParent
                //     ]);
                // }
                $hasilTemp = array_merge(
                    $dataDBFolder->toArray(),
                    [
                        'status' => 'Alredy exists !',
                        'update' => $update,
                        'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], false) : []
                    ]
                );
            }

            $files = [];
            foreach ($value['list_files'] as $keyFile => $valueFile) {
                $expFile = explode('/', $valueFile);
                $dataDBFile = DMSDocMstr::where('ddm_doc_real_name', $expFile[count($expFile) - 1])->first();

                $getRealName = $expFile[count($expFile) - 1];
                $docName = 'DMS_' . Str::random(50) . '.' . explode(".", $getRealName)[count(explode(".", $getRealName)) - 1];
                if (empty($dataDBFile)) {
                    // $dataFolder = DMSFolderMstr::where('id', $idFolder)->with('parentFolders')->first()->toArray();

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
                    $insertFile = DMSDocMstr::where('id', $dataDBFile->id)->create([
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

    public function dbSyncToRealDoc($author, $root = '')
    {
        $data = DMSFolderMstr::with('parentFolders')->with('doc')->where('p_u_username', $author)->where('dfm_parent_id', '<>', NULL)->get()->toArray();

        $hasil = [];
        foreach ($data as $key => $value) {
            $getFolder = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->directories($this->pathCreator($value));

            if (!$getFolder) {
                $hasil[] = $value;
            }
            // $createRealFolder = $this->openFiles($this->getAliasFolderbyAuthor($author), $this->pathCreator($value));
        }

        return $hasil;
    }

    public function migrateRealFileToDB($author, $path = '', $isCheck = false)
    {
        // return $this->checkPath($author, $path);
        $files = $isCheck ? $this->checkPath($author, $path) : $this->migrateFolderToDB($author, $path);
        // $files = $this->convertFolderPathToArray($author);

        return $files;
    }

    public function checkPath($author, $path = '')
    {
        // return $path;
        // return Storage::disk($this->getAliasFolderbyAuthor($author, 'root'))->directories($path === '' ? $this->getAliasFolderbyAuthor($author) : $path);
        $files = $this->convertFolderPathToArray($author, $path);

        return $files;
    }

    public function checkPerm($author, $root = '')
    {
        return Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->allDirectories();
    }

    public function syncRootFiles($author, $root = '')
    {
        $data = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->files();
        $hasil = [];
        foreach ($data as $key => $value) {
            $docName = 'DMS_' . Str::random(50) . '.' . explode(".", $value)[count(explode(".", $value)) - 1];
            $hasil[] = DMSDocMstr::create([
                'p_u_username' => $author,
                'dfm_id' => NULL,
                'ddm_doc_name' => $docName,
                'ddm_doc_real_name' => $value,
                'ddm_doc_size' => 0,
                'ddm_doc_flag' => 0,
            ]);
        }

        return $hasil;
    }

    public function shareFileFolder($id)
    {
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
}
