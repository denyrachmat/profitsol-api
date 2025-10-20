<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderRootMstr;
use App\Models\DMS\DMSDocRootMstr;
use App\Models\DMS\DMSShareDet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Config;
use Illuminate\Http\Request;
use App\Models\CMS\FormMaster;
use App\Traits\PORTAL\GencodeTraits;

trait FolderDocumentTraits
{
    use GencodeTraits;
    public function getFolder($author, $idParentFolder = 0, $root = '', $isFetchAll = false, $isFetchShared = true, $id = 0, $sharedOnly = false)
    {
        set_time_limit(60);
        $users = $this->getAliasFolderbyAuthor($author, 'user');

        // return $users;
        $dataFolder = DMSFolderMstr::with('shared')
            ->where('p_u_username', $users)
            // ->whereDoesntHave('shared')
            ->orderBy('dfm_folder_name');

        if ($sharedOnly) {
            $dataFolder->whereHas('shared');
        } else {
            $dataFolder->whereDoesntHave('shared');
        }

        if ($isFetchAll) {
            $dataFolder->with('doc.shared')
                ->with([
                    'childFolders' => function ($q) {
                        $q->orderBy('dfm_folder_name');

                    }
                ]);
        }

        if (!empty($root)) {
            $dataFolder->where('dfm_root_mstr', $root);
        }

        $docs = [];
        if ($idParentFolder > 0) {
            $dataFolder->where('dfm_parent_id', $idParentFolder);
            $docs = $this->getDoc($author, 0, $idParentFolder, $root);
        } elseif ($idParentFolder == 0) {
            $dataFolder->whereNull('dfm_parent_id');
            $docs = $this->getDoc($author, 0, 0, $root);
        }

        if ($id > 0) {
            $dataFolder->where('id', $id);
        }

        $getSharedHeader = DMSShareDet::select(
            'dfm_id',
            'p_u_username',
            'ddm_id',
        )

            ->groupBy(
                'dfm_id',
                'p_u_username',
                'ddm_id',
            );

        $getShared = (clone $getSharedHeader)->whereIn('ddfus_p_u_username', [$users, 'all'])
            ->where('p_u_username', '<>', $users)
            ->get()
            ->toArray();

        $getShared = array_merge(
            $getShared,
            (clone $getSharedHeader)
                ->where('p_u_username', $users)
                // ->where('ddfus_p_u_username', '<>', 'all')
                ->get()
                ->toArray()
        );

        // return $getShared;
        $dataShared = [];

        if ($isFetchShared && $idParentFolder == 0) {
            foreach ($getShared as $item) {
                // If Shared is Folder
                if (!empty($item['dfm_id'])) {
                    $getFolder = $this->getFolder($item['p_u_username'], -1, '', false, false, $item['dfm_id'], true)['child_folders'];

                    if (count($getFolder) > 0) {
                        foreach ($getFolder as $folder) {
                            $dataShared[] = $folder;
                        }
                    }
                }

                // If Shared is File
                if (!empty($item['ddm_id'])) {
                    $getDoc = $this->getDoc(
                        $item['p_u_username'],
                        $item['ddm_id'],
                        -1,
                        '',
                        true
                    );

                    if (count($getDoc) > 0) {
                        // $dataShared[] = $getDoc ;
                        foreach ($getDoc as $doc) {
                            $dataShared[] = $doc;
                        }
                    }
                }
            }
        }

        $formsCheck = FormMaster::where('cfm_type', 'files')->get();
        $listFilesOnForms = [];
        foreach ($formsCheck as $form) {
            $files[$form->cfmt_id] = [
                'idForms' => $form->cfmt_id,
                'files' => json_decode($form->cfm_content)->files ?? []
            ];
            if (is_array($files[$form->cfmt_id]['files'])) {
                $listFilesOnForms[] = $files[$form->cfmt_id];
            }
        }

        $dataFolder = $dataFolder->get()->map(function ($item) use ($listFilesOnForms) {
            $sharePointData = $this->getDataGencode('DMS_SHAREPOINT_SHARED', [
                'pgm_value' => $item->id,
            ], [
                'sites' => 'pgm_value2|string',
                'url' => 'pgm_value3|string'
            ], [], true);

            $idNya = $item->id;

            // return $listFilesOnForms;
            $filterByID = array_values(array_filter($listFilesOnForms, function ($valueDet) use ($idNya) {
                return count(array_filter($valueDet['files'], function ($file) use ($idNya) {
                    return $file->id == $idNya;
                })) > 0;
            }));

            return array_merge(
                $item->toArray(),
                [
                    'sent_to_fp' => $filterByID,
                    'from_sharepoint' => $sharePointData ? true : false,
                    'sites' => $sharePointData ? json_decode($sharePointData['sites']) : '',
                    'url' => $sharePointData['url'] ?? '',
                    'type' => 'folder',
                ]
            );
        })->toArray();

        return [
            'child_folders' => array_values(array_filter(
                array_merge($dataFolder, $dataShared),
                function ($item) {
                    return isset($item['type']) && $item['type'] === 'folder';
                }
            )),
            'doc' => array_values(array_filter(
                array_merge($docs, $dataShared),
                function ($item) {
                    return isset($item['type']) && $item['type'] === 'file';
                }
            )),
            'author' => $this->getAliasFolderbyAuthor($author, 'user')
        ];
    }

    public function getDoc($author, $id = 0, $idFolder = 0, $root = '', $sharedOnly = false)
    {
        $users = $this->getAliasFolderbyAuthor($author, 'user');

        $dataFiles = DMSDocMstr::where('p_u_username', $users)->with('shared');

        if (!$sharedOnly) {
            $dataFiles->whereDoesntHave('shared');
        }

        if (!empty($root)) {
            $dataFiles->where('dfm_root_mstr', $root);
        }

        if (!empty($id)) {
            $dataFiles->where('id', $id);
        }

        if (!empty($idFolder) && $idFolder > 0) {
            $dataFiles->where('dfm_id', $idFolder);
        } elseif ($idFolder === 0) {
            $dataFiles->whereNull('dfm_id');
        }

        $formsCheck = FormMaster::where('cfm_type', 'files')->get();
        $listFilesOnForms = [];
        foreach ($formsCheck as $form) {
            $files[$form->cfmt_id] = [
                'idForms' => $form->cfmt_id,
                'files' => json_decode($form->cfm_content)->files ?? []
            ];
            if (is_array($files[$form->cfmt_id]['files'])) {
                $listFilesOnForms[] = $files[$form->cfmt_id];
            }
        }

        return $dataFiles->get()->map(function ($item) use ($id, $sharedOnly, $listFilesOnForms) {
            $sharePointData = $this->getDataGencode('DMS_SHAREPOINT_SHARED', [
                'pgm_value' => $item->id,
            ], [
                'sites' => 'pgm_value2|string',
                'url' => 'pgm_value3|string'
            ], [], true);

            $dataShared = [];
            if ($sharedOnly) {
                $dataShared = DMSShareDet::where('ddm_id', $item->id)
                    ->first()
                        ?->toArray();
            }

            $idNya = $item->id;

            $filterByID = array_values(array_filter($listFilesOnForms, function ($valueDet) use ($idNya) {
                return count(array_filter($valueDet['files'], function ($file) use ($idNya) {
                    return $file->id == $idNya;
                })) > 0;
            }));

            return array_merge(
                $item->toArray(),
                [
                    'sent_to_fp' => $filterByID,
                    'type' => 'file',
                    'from_sharepoint' => $sharePointData ? true : false,
                    'sites' => $sharePointData ? json_decode($sharePointData['sites']) : '',
                    'url' => isset($sharePointData['url'])
                        ? $sharePointData['url']
                        : ($sharedOnly
                            ? env('APP_URL') . '/api/dms/documentsRoots/getSharedFilesFolder/' . $dataShared['ddfus_token'] . '/' . $dataShared['id']
                            : '')
                ]
            );
        })->toArray();
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

        // return $users;

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

    public function openFiles($author, $path, $file, $root = '', $id = '')
    {
        // logger($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        // return $this->getAliasFolderbyAuthor($author, 'path') . '/' . $path . '/' . $file;
        $files = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->get($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        $mime = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->mimeType($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        $ext = explode('.', $file)[1];

        $sharePointData = $this->getDataGencode('DMS_SHAREPOINT_SHARED', [
            'pgm_value' => $id,
        ], [
            'sites' => 'pgm_value2|string',
            'url' => 'pgm_value3|string'
        ]);

        return [
            'file' => $files,
            'mime' => $mime,
            'ext' => $ext,
            'from_sharepoint' => $sharePointData ? true : false,
            'sites' => $sharePointData ? json_decode($sharePointData['sites']) : [],
            'url' => $sharePointData ? $sharePointData['url'] : '',
            'test' => $this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file
        ];
    }

    public function uploadFiles($author, $path, $file, $contents, $root = '')
    {
        return storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->put($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file, $contents);
    }

    public function getSizeFiles($author, $path, $file, $root = '')
    {
        logger(Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->path($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file));
        try {
            return storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->size($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        } catch (\Throwable $th) {
            return 0;
        }
    }

    public function convertFolderPathToArray($author, $path = '', $parentKey = 0, $hasil = [], $root = '')
    {
        // return [$this->getAliasFolderbyAuthor($author, 'root'), $path === '' ? $this->getAliasFolderbyAuthor($author) : $path];
        $data = Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->directories($path === '' ? $this->getAliasFolderbyAuthor($author) : $path);
        // return $this->getAliasFolderbyAuthor($author, 'root', $root);

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

        if ($parentKey == 0) {
            return [
                'key' => 0,
                'folders_name' => $path,
                'list_files' => Storage::disk($this->getAliasFolderbyAuthor($author, 'root', $root))->files($path),
                'children' => $hasil
            ];
        }

        return $hasil;
    }

    public function migrateFolderToDB($author, $path = '', $data = [], $root = '')
    {
        if (count($data) === 0) {
            $data = [$this->convertFolderPathToArray($author, $path, 0, [], $root)];
        }

        // return $data;

        $hasil = [];
        $listUpdatedData = [];
        foreach ($data as $key => $value) {
            // If Folders
            $cekParent = null;
            $idFolder = null;
            if (empty($value['folders_name'])) {
                $hasilTemp = [
                    'status' => 'Inserted successfully !',
                    'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], $root) : []
                ];
            } else {
                $expFolder = explode('/', $value['folders_name']);

                if (count($expFolder) > 1) {
                    $cekParent = DMSFolderMstr::where('dfm_folder_name', $expFolder[count($expFolder) - 2])
                        ->where('p_u_username', $author)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                $dataDBFolder = DMSFolderMstr::where('dfm_folder_name', $expFolder[count($expFolder) - 1])
                    ->where('dfm_parent_id', !empty($cekParent) ? $cekParent->id : null)
                    ->where('p_u_username', $author)
                    ->where('dfm_root_mstr', $root)
                    ->first();

                $checkParent = !empty($cekParent) && count($expFolder) > 1
                    ? $cekParent->id
                    : NULL;

                if (empty($dataDBFolder)) {
                    $insert = DMSFolderMstr::create([
                        'p_u_username' => $author,
                        'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                        'dfm_parent_id' => $checkParent,
                        'dfm_root_mstr' => $root
                    ]);
                    $idFolder = $insert->id;
                    $hasilTemp = array_merge(
                        $insert->toArray(),
                        [
                            'status' => 'Inserted successfully !',
                            'folderName' => $expFolder[count($expFolder) - 1],
                            'parents' => $expFolder[count($expFolder) - 2] ?? null,
                            'created_data' => [
                                'p_u_username' => $author,
                                'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                                'dfm_parent_id' => $checkParent,
                                'dfm_root_mstr' => $root
                            ],
                            'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], $root) : []
                        ]
                    );

                    $listUpdatedData[] = array_merge($insert->toArray(), ['type' => 'folder']);
                } else {
                    $idFolder = $dataDBFolder->id;
                    $listUpdatedData[] = array_merge($dataDBFolder->toArray(), ['type' => 'folder']);
                    // $checkParent2 = DMSFolderMstr::where('id', $idFolder)->where('dfm_parent_id', $checkParent)->first();

                    // $update = DMSFolderMstr::create([
                    //     'p_u_username' => $author,
                    //     'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                    //     'dfm_parent_id' => $checkParent,
                    //     'dfm_root_mstr' => $root
                    // ]);

                    // $idFolder = $update->id;
                    $hasilTemp = array_merge(
                        $dataDBFolder->toArray(),
                        [
                            'status' => 'Folder Already exists !',
                            'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], $root) : []
                        ]
                    );
                }
            }

            // If Files
            $files = [];
            foreach ($value['list_files'] as $keyFile => $valueFile) {
                $expFile = explode('/', $valueFile);
                $dataDBFileCheck = DMSDocMstr::where('ddm_doc_real_name', $expFile[count($expFile) - 1])
                    ->where('p_u_username', $author);

                if (!empty($idFolder)) {
                    $dataDBFileCheck->where('dfm_id', $idFolder);
                } else {
                    $dataDBFileCheck->whereNull('dfm_id');
                }

                $dataDBFile = $dataDBFileCheck->first();

                $getRealName = $expFile[count($expFile) - 1];
                $docName = 'DMS_' . Str::random(50) . '.' . explode(".", $getRealName)[count(explode(".", $getRealName)) - 1];
                if (empty($dataDBFile)) {
                    $insertFile = DMSDocMstr::create([
                        'p_u_username' => $author,
                        'dfm_id' => $idFolder,
                        'ddm_doc_name' => $getRealName,
                        'ddm_doc_real_name' => $getRealName,
                        'ddm_doc_size' => 0,
                        'ddm_doc_flag' => 0,
                        'dfm_root_mstr' => $root
                    ]);
                    $files[] = array_merge(
                        $insertFile->toArray(),
                        [
                            'status' => 'Inserted successfully !'
                        ]
                    );

                    $listUpdatedData[] = array_merge($insertFile->toArray(), ['type' => 'file']);
                } else {
                    $listUpdatedData[] = array_merge($dataDBFile->toArray(), ['type' => 'file']);
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

        // Remove Data Not in List
        $folderNames = array_column(array_filter($listUpdatedData, function ($item) {
            return $item['type'] == 'folder';
        }), 'dfm_folder_name');

        $fileNames = array_column(array_filter($listUpdatedData, function ($item) {
            return $item['type'] == 'file';
        }), 'ddm_doc_real_name');

        if (count($folderNames) > 0) {
            $dataDBFolder = DMSFolderMstr::whereNotIn('dfm_folder_name', $folderNames)
                ->where('dfm_parent_id', !empty($cekParent) ? $cekParent->id : null)
                ->where('p_u_username', $author)
                ->where('dfm_root_mstr', $root)
                ->delete();
        }

        if (count($fileNames) > 0) {
            $dataDBFile = DMSDocMstr::whereNotIn('ddm_doc_real_name', $fileNames)
                ->where('dfm_id', $idFolder)
                ->where('p_u_username', $author)
                ->where('dfm_root_mstr', $root)
                ->delete();
        }

        return [
            'data' => $hasil,
            'listFolderFile' => $folderNames,
            'listFile' => $fileNames
        ];
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

    public function shareFileFolder(Request $request)
    {
        if ($request->has('ddfus_token') && !empty($request->ddfus_token)) {
            $token = $request->ddfus_token;
        } else {
            $token = Str::random(30);
        }

        $result = [];
        foreach ($request->det as $key => $value) {
            foreach ($request->ddfus_p_u_username as $keyUsers => $valueUsers) {
                $result[] = DMSShareDet::updateOrCreate(
                    [
                        'ddm_id' => $value['type'] === 'files' ? $value['id'] : null,
                        'dfm_id' => $value['type'] === 'folder' ? $value['id'] : null,
                        'ddfus_p_u_username' => $request->ddfus_p_u_username,
                        'ddfus_token' => $token,
                    ],
                    [
                        'p_u_username' => $request->header('username'),
                        'ddm_id' => $value['type'] === 'files' ? $value['id'] : null,
                        'dfm_id' => $value['type'] === 'folder' ? $value['id'] : null,
                        'ddfus_p_u_username' => $valueUsers,
                        'ddfus_read' => $request->ddfus_read,
                        'ddfus_write' => $request->ddfus_write,
                        'ddfus_token' => $token,
                    ]
                );
            }
        }

        if ($request->has('frontPageList') && count($request->frontPageList) > 0) {
            foreach ($request->frontPageList as $fp) {
                foreach ($fp['forms'] as $fpDetail) {
                    if (isset($fpDetail['checked']) && $fpDetail['checked'] === true) {
                        FormMaster::where('id', $fpDetail['id'])->update([
                            'cfm_content' => json_encode(array_merge(
                                $fpDetail['cfm_content'],
                                [
                                    'files' => array_merge(
                                        isset($fpDetail['cfm_content']['files']) ? $fpDetail['cfm_content']['files'] : [],
                                        $request->sharedData
                                    )
                                ]
                            ))
                        ]);
                    }
                }
            }
        }

        return $result;
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

    public function getSharedToken($token, $sharedId = '', $users = 'all')
    {
        $data = DMSShareDet::where('ddfus_token', $token)
            ->with('folder')
            ->with('file')
            ->where('ddfus_p_u_username', $users);

        if (!empty($sharedId)) {
            $data->where('id', $sharedId);
        }

        return $data->get();
    }

    public function getSharedFilesFolder($token, $sharedId = '', $users = 'all')
    {
        $data = $this->getSharedToken($token, $sharedId, $users);

        // return $data;

        if (count($data) > 0) {
            $hasil = [];

            foreach ($data as $key => $value) {
                if (!empty($value->file)) {
                    $getData = DMSDocMstr::where('id', $value->file->id)->with('folder.parentFolders')->with('shared')->first()->toArray();

                    // return $getData;
                    $files = $this->openFiles(
                        $getData['p_u_username'],
                        !empty($getData['folder']) ? $this->pathCreator($getData['folder']) : '',
                        $this->getAliasFolderbyAuthor($getData['p_u_username'], 'source') == 1
                        ? $getData['ddm_doc_real_name']
                        : $getData['ddm_doc_name'],
                        empty($getData['folder']) ? $getData['dfm_root_mstr'] : $getData['folder']['dfm_root_mstr']
                    );

                    $hasil[] = [
                        'data' => $getData,
                        'base64Files' => base64_encode($files['file']),
                        'mime' => $files['mime'],
                        'ext' => $files['ext'],
                        'filename' => $getData['ddm_doc_real_name'],
                        'test' => $files
                    ];
                }
            }

            if (count($data) === 1) {
                $contents = base64_decode($hasil[0]['base64Files']);

                $path = public_path($hasil[0]['filename']);
                //store file temporarily
                file_put_contents($path, $contents);

                //download file and delete it
                return response()->stream(function () use ($contents) {
                    echo $contents;
                }, 200, [
                    'Content-Type' => $hasil[0]['mime'],
                    'Content-Disposition' => 'inline; filename="' . $hasil[0]['filename'] . '"',
                ]);
            }

            return $hasil;
        } else {
            return $this->handleError("Shared files / folder not found !");
        }
    }
}
