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

use App\Traits\PORTAL\GencodeTraits;

trait FolderDocumentTraits
{
    use GencodeTraits;
    public function getFolder($author, $id = null, $root = '')
    {
        $users = $this->getAliasFolderbyAuthor($author, 'user');

        // return $users;
        $dataFolder = DMSFolderMstr::with([
            'childFolders' => function ($q) {
                $q->orderBy('dfm_folder_name');

            }
        ])
            ->with('doc.shared')
            ->with('shared')
            ->where('p_u_username', $users)
            ->whereNull('dfm_parent_id')
            ->orderBy('dfm_folder_name');

        $dataFiles = DMSDocMstr::where('p_u_username', $users)->with('shared');

        if (!empty($root)) {
            $dataFolder->where('dfm_root_mstr', $root);
            $dataFiles->where('dfm_root_mstr', $root);
        }

        return !empty($id)
            ? [
                'child_folders' => $dataFolder->where('id', $id)->get()->map(function ($item) use ($id) {
                    $sharePointData = $this->getDataGencode('DMS_SHAREPOINT_SHARED', [
                        'pgm_value' => $id,
                    ], [
                        'sites' => 'pgm_value2|string',
                        'url' => 'pgm_value3|string'
                    ]);

                    return array_merge(
                        $item->toArray(),
                        [
                            'from_sharepoint' => $sharePointData ? true : false,
                            'sites' => json_decode($sharePointData['sites']) ?? '',
                            'url' => $sharePointData['url'] ?? '',
                        ]
                    );
                }),
                'doc' => $dataFiles->where('dfm_id', $id)->get()
            ]
            : [
                'child_folders' => $dataFolder->get()->map(function ($item) use ($id) {
                    $sharePointData = $this->getDataGencode('DMS_SHAREPOINT_SHARED', [
                        'pgm_value' => $item->id,
                    ], [
                        'sites' => 'pgm_value2|string',
                        'url' => 'pgm_value3|string'
                    ], [], true);

                    return array_merge(
                        $item->toArray(),
                        $sharePointData ? [
                            'from_sharepoint' => $sharePointData ? true : false,
                            'sites' => json_decode($sharePointData['sites']) ?? '',
                            'url' => $sharePointData['url'] ?? '',
                        ] : []
                    );
                }),
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
            'test' =>  $this->getAliasFolderbyAuthor($author) . '/' . $path . '/' . $file
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
            $data = [$this->convertFolderPathToArray($author, $path, 0, [], $root)];
        }

        $hasil = [];
        foreach ($data as $key => $value) {
            if (empty($value['folders_name'])) {
                $idFolder = null;
                $hasilTemp = [
                    'status' => 'Inserted successfully !',
                    'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], $root) : []
                ];
            } else {

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
                        'dfm_parent_id' => $checkParent,
                        'dfm_root_mstr' => $root
                    ]);
                    $idFolder = $insert->id;
                    $hasilTemp = array_merge(
                        $insert->toArray(),
                        [
                            'status' => 'Inserted successfully !',
                            'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], $root) : []
                        ]
                    );
                } else {
                    $idFolder = $dataDBFolder->id;
                    $checkParent2 = DMSFolderMstr::where('id', $idFolder)->where('dfm_parent_id', $checkParent)->first();

                    $update = DMSFolderMstr::create([
                        'p_u_username' => $author,
                        'dfm_folder_name' => $expFolder[count($expFolder) - 1],
                        'dfm_parent_id' => $checkParent,
                        'dfm_root_mstr' => $root
                    ]);

                    $idFolder = $update->id;
                    $hasilTemp = array_merge(
                        $dataDBFolder->toArray(),
                        [
                            'status' => 'Alredy exists !',
                            'update' => $update,
                            'children' => count($value['children']) > 0 ? $this->migrateFolderToDB($author, '', $value['children'], $root) : []
                        ]
                    );
                }
            }

            $files = [];
            foreach ($value['list_files'] as $keyFile => $valueFile) {
                $expFile = explode('/', $valueFile);
                $dataDBFile = DMSDocMstr::where('ddm_doc_real_name', $expFile[count($expFile) - 1])->first();

                $getRealName = $expFile[count($expFile) - 1];
                $docName = 'DMS_' . Str::random(50) . '.' . explode(".", $getRealName)[count(explode(".", $getRealName)) - 1];
                if (empty($dataDBFile)) {
                    $insertFile = DMSDocMstr::create([
                        'p_u_username' => $author,
                        'dfm_id' => $idFolder,
                        'ddm_doc_name' => $docName,
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
                } else {
                    $insertFile = DMSDocMstr::where('id', $dataDBFile->id)->create([
                        'p_u_username' => $author,
                        'dfm_id' => $idFolder,
                        'ddm_doc_name' => $docName,
                        'ddm_doc_real_name' => $getRealName,
                        'ddm_doc_size' => 0,
                        'ddm_doc_flag' => 0,
                        'dfm_root_mstr' => $root
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
