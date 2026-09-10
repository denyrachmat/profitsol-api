<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderRootMstr;
use App\Models\DMS\DMSDocRootMstr;
use App\Models\DMS\DMSShareDet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Config;
use Illuminate\Http\Request;
use App\Models\CMS\FormMaster;
use App\Traits\PORTAL\GencodeTraits;
use Illuminate\Filesystem\FilesystemAdapter;

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

    public function getDiskAlias($author, $source)
    {
        $getMapping = DMSFolderRootMstr::where('p_u_username', $author)->where('dudrm_source', $source)->first();
        // fallback to alias user if mapping not found for original author
        if (!$getMapping) {
            $aliasUser = $this->getAliasFolderbyAuthor($author, 'user');
            if ($aliasUser !== $author) {
                $getMapping = DMSFolderRootMstr::where('p_u_username', $aliasUser)->where('dudrm_source', $source)->first();
            }
        }
        if (!$getMapping) {
            logger()->warning('getDiskAlias mapping not found', ['author' => $author, 'source' => $source]);
            abort(404, "Disk mapping not found for user {$author} / source {$source}");
        }
        return $this->installDisk($getMapping->dudrm_source);
    }

    public function getPathRoot($author, $source)
    {
        $getMapping = DMSFolderRootMstr::where('p_u_username', $author)->where('dudrm_source', $source)->first();

        $this->installDisk($getMapping->dudrm_source);

        $getRoot = DMSDocRootMstr::where('ddrm_name', $getMapping->dudrm_source)->first();

        return $getRoot ? $getRoot->ddrm_root : null;
    }

    public function getAliasFolderbyAuthor($author, $data = 'path', $root = '')
    {
        $checkRootAliasTest = DMSFolderRootMstr::where('p_u_username', $author)->first();

        $checkRootAlias = null;
        if (!empty($checkRootAliasTest->dudrm_alias_username)) {
            $checkRootAlias = DMSFolderRootMstr::where('p_u_username', $checkRootAliasTest->dudrm_alias_username)->first();
        }

        $users = empty($checkRootAlias)
            ? $author
            : ($data === 'user'
                ? $checkRootAlias->p_u_username
                : ($checkRootAlias->dudrm_path)
            );

        // return $users;

        if (empty($root)) {
            // logger($users);
            $root = empty($checkRootAlias->dudrm_source)
                ? 'data_folder'
                : $checkRootAlias->dudrm_source;

            // $checkID = DMSDocRootMstr::where('ddrm_name', $root)->first();

            // logger($root);
            // if ($checkID) {
            //     $this->installDisk($checkID->id);
            // }
        } else {
            $checkID = DMSDocRootMstr::where('ddrm_name', $root)->first();
            if ($checkID) {
                $this->installDisk($checkID->ddrm_name);
            }
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
        return $this->getDiskAlias($author, $root)->makeDirectory($this->getAliasFolderbyAuthor($author) . '/' . $path);
    }

    public function deleteFolder($author, $path, $root = '')
    {
        return $this->getDiskAlias($author, $root)->deleteDirectory($this->getAliasFolderbyAuthor($author) . '/' . $path);
    }

    public function deleteFiles($author, $path, $file, $root = '')
    {
        return $this->getDiskAlias($author, $root)->delete($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
    }

    public function openFiles($author, $path, $file, $root = '', $id = '')
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = $this->getDiskAlias($author, $root);

        $base = trim($this->getAliasFolderbyAuthor($author), '/');

        $fullPath = $path === ''
            ? "$base/$file"
            : "$base/" . trim($path, '/') . "/$file";

        if (!$disk->exists($fullPath)) {
            logger('File not found', [
                'disk_root' => $disk->path(''),
                'fullPath' => $fullPath,
                'path' => $path,
                'base' => $base,
            ]);

            $fullPath = $path === ''
                ? "$file"
                : trim($path, '/') . "/$file";
        }

        if (!$disk->exists($fullPath)) {
            logger('File still not found with alternative path', [
                'disk_root' => $disk->path(''),
                'fullPath' => $fullPath,
                'path' => $path,
                'base' => $base,
            ]);

            abort(404, 'File not found');
        }

        $files = $disk->get($fullPath);
        $mime = $disk->mimeType($fullPath);
        $ext = pathinfo($file, PATHINFO_EXTENSION);

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
        // logger('disk', [$this->getAliasFolderbyAuthor($author, 'root', $root)]);
        // logger('put', [$this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file]);
        return $this->getDiskAlias($author, $root)->put($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file, $contents);
    }

    public function getSizeFiles($author, $path, $file, $root = '')
    {
        logger($this->getDiskAlias($author, $root)->path($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file));
        try {
            return $this->getDiskAlias($author, $root)->size($this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file);
        } catch (\Throwable $th) {
            return 0;
        }
    }

    public function convertFolderPathToArray($author, $path = '', $parentKey = 0, $hasil = [], $root = '')
    {
        try {
            $disk = $this->getDiskAlias($author, $root);
        } catch (\Throwable $e) {
            logger()->error('convertFolderPathToArray getDiskAlias failed', ['author' => $author, 'root' => $root, 'error' => $e->getMessage()]);
            return $parentKey == 0 ? ['key' => 0, 'folders_name' => $path, 'list_files' => [], 'children' => []] : [];
        }
        $scanPath = $path === '' ? '' : $path;
        try {
            $data = $disk->directories($scanPath);
        } catch (\Throwable $e) {
            logger()->warning('convertFolderPathToArray directories failed', ['author' => $author, 'root' => $root, 'scanPath' => $scanPath, 'error' => $e->getMessage()]);
            $data = [];
        }

        $kunci = 1;
        $pathDet = $path;
        foreach ($data as $key => $value) {
            $pathDet = !empty($path) ? $pathDet . '/' . $value : $value;
            try {
                $files = $disk->files($value);
            } catch (\Throwable $e) {
                logger()->warning('convertFolderPathToArray files failed', ['value' => $value, 'error' => $e->getMessage()]);
                $files = [];
            }
            $hasil[] = [
                'key' => $parentKey + $kunci,
                'folders_name' => $value,
                'list_files' => $files,
                'children' => $this->convertFolderPathToArray($author, $value, $kunci, [], $root)
            ];

            $kunci++;
        }

        if ($parentKey == 0) {
            try {
                $rootFiles = $disk->files($path === '' ? '' : $path);
            } catch (\Throwable $e) {
                logger()->warning('convertFolderPathToArray root files failed', ['path' => $path, 'error' => $e->getMessage()]);
                $rootFiles = [];
            }
            return [
                'key' => 0,
                'folders_name' => $path,
                'list_files' => $rootFiles,
                'children' => $hasil
            ];
        }

        return $hasil;
    }

    public function migrateFolderToDB($author, $path = '', $data = [], $root = '')
    {
        $dbUser = $this->getAliasFolderbyAuthor($author, 'user');
        logger()->info('migrateFolderToDB start', ['author' => $author, 'dbUser' => $dbUser, 'root' => $root, 'path' => $path]);
        if (count($data) === 0) {
            $converted = $this->convertFolderPathToArray($author, $path, 0, [], $root);
            logger()->info('convertFolderPathToArray result', ['converted' => $converted]);
            $data = [$converted];
        }

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
                // strip user alias prefix (e.g. "deny-rachmat/FolderA/Sub" -> "FolderA/Sub")
                $baseAlias = trim($this->getAliasFolderbyAuthor($author), '/');
                $rawPath = trim($value['folders_name'], '/');
                if ($baseAlias !== '' && str_starts_with($rawPath, $baseAlias)) {
                    $rawPath = ltrim(substr($rawPath, strlen($baseAlias)), '/');
                }
                $expFolder = $rawPath === '' ? [] : explode('/', $rawPath);
                if (empty($expFolder)) {
                    // fallback if stripping failed - use basename
                    $expFolder = [basename(trim($value['folders_name'], '/'))];
                }
                $folderName = $expFolder[count($expFolder) - 1];
                $parentName = count($expFolder) > 1 ? $expFolder[count($expFolder) - 2] : null;

                if (!empty($parentName)) {
                    $cekParent = DMSFolderMstr::where('dfm_folder_name', $parentName)
                        ->where('p_u_username', $dbUser)
                        ->where('dfm_root_mstr', $root)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                $dataDBFolder = DMSFolderMstr::where('dfm_folder_name', $folderName)
                    ->where('dfm_parent_id', !empty($cekParent) ? $cekParent->id : null)
                    ->where('p_u_username', $dbUser)
                    ->where('dfm_root_mstr', $root)
                    ->first();

                $checkParent = !empty($cekParent) ? $cekParent->id : null;

                if (empty($dataDBFolder)) {
                    $insert = DMSFolderMstr::create([
                        'p_u_username' => $dbUser,
                        'dfm_folder_name' => $folderName,
                        'dfm_parent_id' => $checkParent,
                        'dfm_root_mstr' => $root
                    ]);
                    $idFolder = $insert->id;
                    $hasilTemp = array_merge(
                        $insert->toArray(),
                        [
                            'status' => 'Inserted successfully !',
                            'folderName' => $folderName,
                            'parents' => $parentName,
                            'created_data' => [
                                'p_u_username' => $dbUser,
                                'dfm_folder_name' => $folderName,
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
                    ->where('p_u_username', $dbUser)
                    ->where('dfm_root_mstr', $root);

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
                        'p_u_username' => $dbUser,
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

        if (count($folderNames) > 0) {
            DMSFolderMstr::whereNotIn('dfm_folder_name', $folderNames)
                ->where('dfm_parent_id', !empty($cekParent) ? $cekParent->id : null)
                ->where('p_u_username', $dbUser)
                ->where('dfm_root_mstr', $root)
                ->delete();
        }

        // per-folder file cleanup (fixes bug where only last $idFolder was cleaned)
        $filesByFolder = [];
        foreach ($listUpdatedData as $item) {
            if ($item['type'] === 'file') {
                $key = $item['dfm_id'] ?? '__root__';
                $filesByFolder[$key][] = $item['ddm_doc_real_name'];
            }
        }
        foreach ($filesByFolder as $fid => $names) {
            $q = DMSDocMstr::whereNotIn('ddm_doc_real_name', $names)
                ->where('p_u_username', $dbUser)
                ->where('dfm_root_mstr', $root);
            if ($fid === '__root__') {
                $q->whereNull('dfm_id');
            } else {
                $q->where('dfm_id', $fid);
            }
            $q->delete();
        }
        $fileNames = array_column(array_filter($listUpdatedData, function ($item) {
            return $item['type'] == 'file';
        }), 'ddm_doc_real_name');

        return [
            'data' => $hasil,
            'listFolderFile' => $folderNames,
            'listFile' => $fileNames
        ];
    }

    /**
     * Start a chunked folder/file sync. Lists only top-level directories initially
     * (fast), stores queue in cache. Subdirectories are expanded during polling.
     */
    public function syncFolderToDBPrepare($author, $root = '')
    {
        $disk = $this->getDiskAlias($author, $root);
        $dbUser = $this->getAliasFolderbyAuthor($author, 'user');

        // Only scan top-level dirs here - fast, no recursive walk
        $topDirs = $disk->directories('');
        sort($topDirs);

        $token = 'dms_sync_' . Str::random(24);
        Cache::put($token, [
            'author' => $author,
            'db_user' => $dbUser,
            'root' => $root,
            'queue' => $topDirs,
            'done' => 0,
            'total' => count($topDirs), // initial estimate, grows as subdirs expand
            'started_at' => now()->toDateTimeString(),
        ], now()->addHours(6));

        return [
            'token' => $token,
            'total' => count($topDirs),
            'done' => 0,
        ];
    }

    /**
     * Process the next batch of directories for a running sync token.
     * For each dir: migrate folder + files, then enqueue its subdirectories.
     */
    public function syncFolderToDBStep($token, $batch = 20)
    {
        $state = Cache::get($token);
        if (!$state) {
            return ['status' => 'done', 'token' => $token];
        }

        $queue = $state['queue'];
        $batch = max(1, (int) $batch);
        $slice = array_splice($queue, 0, $batch);

        $disk = $this->getDiskAlias($state['author'], $state['root']);

        foreach ($slice as $dirPath) {
            $this->migrateSingleDirToDB($state['author'], $dirPath, $state['root']);
            $state['done']++;

            // Expand subdirectories and add to queue
            try {
                $subDirs = $disk->directories($dirPath);
                if (count($subDirs) > 0) {
                    sort($subDirs);
                    $queue = array_merge($queue, $subDirs);
                    $state['total'] += count($subDirs);
                }
            } catch (\Throwable $e) {
                // ignore scan errors for subdirs
            }
        }

        $state['queue'] = $queue;
        Cache::put($token, $state, now()->addHours(6));

        $remaining = count($queue);
        if ($remaining === 0) {
            Cache::forget($token);
            return [
                'status' => 'done',
                'token' => $token,
                'total' => $state['total'],
                'done' => $state['done'],
            ];
        }

        return [
            'status' => 'running',
            'token' => $token,
            'total' => $state['total'],
            'done' => $state['done'],
            'remaining' => $remaining,
        ];
    }

    public function syncFolderToDBInfo($token)
    {
        $state = Cache::get($token);
        if (!$state) {
            return ['status' => 'done', 'token' => $token];
        }
        return [
            'status' => 'running',
            'token' => $token,
            'total' => $state['total'],
            'done' => $state['done'],
            'remaining' => count($state['queue']),
        ];
    }

    /**
     * Migrate a single directory (folder + its files) into the DB.
     * $dirPath is a path relative to the disk root, e.g. "FolderA/Sub".
     */
    public function migrateSingleDirToDB($author, $dirPath, $root = '')
    {
        $disk = $this->getDiskAlias($author, $root);
        $dbUser = $this->getAliasFolderbyAuthor($author, 'user');

        $dirPath = trim($dirPath, '/');
        $expFolder = $dirPath === '' ? [] : explode('/', $dirPath);
        $folderName = count($expFolder) > 0 ? $expFolder[count($expFolder) - 1] : '';
        $parentName = count($expFolder) > 1 ? $expFolder[count($expFolder) - 2] : null;

        $cekParent = null;
        if (!empty($parentName)) {
            $cekParent = DMSFolderMstr::where('dfm_folder_name', $parentName)
                ->where('p_u_username', $dbUser)
                ->where('dfm_root_mstr', $root)
                ->orderBy('id', 'desc')
                ->first();
        }
        $checkParent = !empty($cekParent) ? $cekParent->id : null;

        // Folder
        $idFolder = null;
        $dataDBFolder = DMSFolderMstr::where('dfm_folder_name', $folderName)
            ->where('dfm_parent_id', $checkParent)
            ->where('p_u_username', $dbUser)
            ->where('dfm_root_mstr', $root)
            ->first();

        if (empty($dataDBFolder)) {
            $dataDBFolder = DMSFolderMstr::create([
                'p_u_username' => $dbUser,
                'dfm_folder_name' => $folderName,
                'dfm_parent_id' => $checkParent,
                'dfm_root_mstr' => $root,
            ]);
        }
        $idFolder = $dataDBFolder->id;

        // Files
        try {
            $filesInDir = $disk->files($dirPath);
        } catch (\Throwable $e) {
            $filesInDir = [];
        }

        $existingFiles = [];
        foreach ($filesInDir as $valueFile) {
            $expFile = explode('/', $valueFile);
            $realName = $expFile[count($expFile) - 1];

            $exists = DMSDocMstr::where('ddm_doc_real_name', $realName)
                ->where('dfm_id', $idFolder)
                ->where('p_u_username', $dbUser)
                ->where('dfm_root_mstr', $root)
                ->first();

            if (empty($exists)) {
                DMSDocMstr::create([
                    'p_u_username' => $dbUser,
                    'dfm_id' => $idFolder,
                    'ddm_doc_name' => $realName,
                    'ddm_doc_real_name' => $realName,
                    'ddm_doc_size' => 0,
                    'ddm_doc_flag' => 0,
                    'dfm_root_mstr' => $root,
                ]);
            }
            $existingFiles[] = $realName;
        }

        // Cleanup: remove DB files in this folder that no longer exist on disk
        if (count($existingFiles) > 0) {
            DMSDocMstr::whereNotIn('ddm_doc_real_name', $existingFiles)
                ->where('dfm_id', $idFolder)
                ->where('p_u_username', $dbUser)
                ->where('dfm_root_mstr', $root)
                ->delete();
        }
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

    /**
     * Flat listing of folders + files under a specific path.
     * Returns Table-Select friendly rows: value (relative path), label (name),
     * type (folder|file), path (relative path).
     */
    public function browsePath($author, $root = '', $path = '', $recursive = false)
    {
        $disk = $this->getDiskAlias($author, $root);
        $scanPath = trim($path ?? '', '/');

        $dirs = $recursive
            ? $disk->allDirectories($scanPath)
            : $disk->directories($scanPath);
        $files = $recursive
            ? $disk->allFiles($scanPath)
            : $disk->files($scanPath);

        $result = [];
        foreach ($dirs as $dir) {
            $result[] = [
                'value' => $dir,
                'label' => basename($dir),
                'type' => 'folder',
                'path' => $dir,
            ];
        }
        foreach ($files as $file) {
            $result[] = [
                'value' => $file,
                'label' => basename($file),
                'type' => 'file',
                'path' => $file,
            ];
        }

        return $result;
    }

    public function checkPerm($author, $root = '')
    {
        $diskName = $this->getAliasFolderbyAuthor($author, 'root', $root);
        try {
            // use ephemeral disk to support dynamic ddrm_name not registered in config/filesystems.php
            $disk = $this->installDisk($diskName);
            return $disk->allDirectories();
        } catch (\Throwable $e) {
            logger()->warning('checkPerm installDisk failed, fallback to Storage::disk', ['author' => $author, 'root' => $root, 'disk' => $diskName, 'error' => $e->getMessage()]);
            return Storage::disk($diskName)->allDirectories();
        }
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
        $r = DMSDocRootMstr::where('ddrm_name', $id)->firstOrFail();

        $driver = strtolower($r->ddrm_driver);

        if ($driver === 'local') {
            $root = rtrim(str_replace('\\', '/', $r->ddrm_root), '/');

            if (!is_dir($r->ddrm_root) || !is_readable($r->ddrm_root)) {
                throw new \RuntimeException("Local disk root not accessible: {$r->ddrm_root}");
            }

            return Storage::build([
                'driver' => 'local',
                'root' => $root,
            ]);
        }

        if ($driver === 's3') {
            // sesuaikan nama kolom DB kamu (ini contoh umum)
            return Storage::build([
                'driver' => 's3',
                'key' => $r->ddrm_key,       // AWS_ACCESS_KEY_ID
                'secret' => $r->ddrm_secret,    // AWS_SECRET_ACCESS_KEY
                'region' => $r->ddrm_region,    // AWS_DEFAULT_REGION
                'bucket' => $r->ddrm_bucket,    // AWS_BUCKET
                // optional:
                'url' => $r->ddrm_url ?: null,       // AWS_URL (kalau ada)
                'endpoint' => $r->ddrm_endpoint ?: null,  // penting untuk MinIO / custom S3
                'use_path_style_endpoint' => (bool) ($r->ddrm_path_style ?? false),
                'throw' => false, // kalau versi Laravel kamu support
            ]);
        }

        if ($driver === 'ftp' || $driver === 'sftp') {
            return Storage::build([
                'driver' => $driver,
                'host' => $r->ddrm_host,
                'username' => $r->ddrm_username,
                'password' => $r->ddrm_password,

                // optional tapi sering dibutuhkan:
                'root' => $r->ddrm_root ?: '/',  // remote root
                'port' => $r->ddrm_port ? (int) $r->ddrm_port : null,
                'timeout' => $r->ddrm_timeout ? (int) $r->ddrm_timeout : 30,

                // FTP-only optional:
                'passive' => $r->ddrm_passive !== null ? (bool) $r->ddrm_passive : true,

                // SFTP-only optional:
                // 'privateKey' => $r->ddrm_private_key_path,
                // 'passphrase' => $r->ddrm_passphrase,
            ]);
        }

        abort(400, "Unsupported filesystem driver: {$r->ddrm_driver}");
    }

    public function getDataFilter(Request $request)
    {
        $hist = new DMSDocRootMstr;

        if ($request->has('filter') && count(array_filter($request->filter, function ($f) {
            return !empty($f['cols']); })) > 0) {
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
            foreach ($datanya as $value) {
                $status = true;
                $checkList = null;
                $error = null;

                try {
                    $disk = $this->installDisk($value['ddrm_name']);
                    // lightweight probe - do NOT use allDirectories/allFiles (recursive scan of entire NAS)
                    // is_dir already validated in installDisk for local; just verify disk is usable
                    $exists = $disk->directoryExists('') || $disk->exists('');
                    if (!$exists) {
                        throw new \RuntimeException("Disk root not reachable: {$value['ddrm_root']}");
                    }
                    // shallow listing only (no recursion) for sample - limit to 5 entries to avoid blocking
                    try {
                        $checkList = $disk->directories('', false);
                        $checkList = array_slice($checkList, 0, 5);
                    } catch (\Throwable $inner) {
                        $checkList = [];
                    }
                } catch (\Throwable $e) {
                    $status = false;
                    $error = $e->getMessage();
                    logger()->warning('Disk check failed for ddrm_name: ' . $value['ddrm_name'], ['exception' => $e]);
                    $checkList = [];
                }

                $hasil[] = array_merge($value, [
                    'config_status' => $status,
                    'check_list' => $checkList,
                    'check_error' => $error,
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
