<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Core\DocsLocationMaster;
use App\Http\Requests\DMS\Core\CreateNewFolderRequest;
use Illuminate\Support\Str;
use App\Models\DMS\Core\DocsMaster;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\sharedMapping;

class DocsLocationController extends Controller
{
    public function getlist($user, $idfolder = null)
    {
        $select = [
            'apprv_author',
            'apprv_title',
            'apprv_id',
            'apprv_approver',
            'apprv_level'
        ];

        $apprvMasterFirst = ApprovalMaster::select($select)
            ->where('apprv_author', $user)
            ->with('userAuthor')
            ->with('user')
            ->with(['contentDef' => function ($q1) {
                $q1->with('contentDet');
                $q1->with('contentMstr');
            }])
            ->groupBy($select)
            ->orderBy('apprv_level')
            ->get()
            ->toArray();

        $apprvMaster = [];
        $count = 0;
        foreach ($apprvMasterFirst as $key => $value) {
            if ($key !== 0 && $apprvMasterFirst[$key - 1]['apprv_id'] !== $value['apprv_id']) {
                $count++;
            }
            $apprvMaster[$count]['apprv_author'] = $value['apprv_author'];
            $apprvMaster[$count]['apprv_title'] = $value['apprv_title'];
            $apprvMaster[$count]['apprv_id'] = $value['apprv_id'];
            $apprvMaster[$count]['user_author'] = $value['user_author'];
            $apprvMaster[$count]['content_def'] = $value['content_def'];
            $apprvMaster[$count]['content_def'] = $value['content_def'];
            $apprvMaster[$count]['apprv_detail'][$key]['apprv_approver'] = $value['apprv_approver'];
            $apprvMaster[$count]['apprv_detail'][$key]['apprv_level'] = $value['apprv_level'];
            $apprvMaster[$count]['apprv_detail'][$key]['user_approver'] = $value['user'];
        }

        // return $apprvMaster;

        if (empty($idfolder)) {
            $folder = DocsLocationMaster::with('getParents')
                ->with('users')
                ->with(['listDoc' => function ($q) {
                    $q->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version');
                }])
                ->where('creator_loc', $user)
                ->where('parent_loc', '0')
                ->get();

            $sharedFolder = sharedMapping::where('shared_to', $user)
                ->with(['docMaster.listDoc' => function ($q2) {
                    $q2->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version');
                }])
                ->with('docMaster.users')
                ->get();

            $files = DocsMaster::where('doc_path', '0')
                ->where('doc_author', $user)
                ->with(['users', 'currentversion', 'apprvhist.getallapprover', 'tags.tagsMaster'])
                ->doesnthave('version')
                ->get()
                ->toArray();

            return [
                'datafolder' => $folder,
                'sharedfolder' => $sharedFolder,
                'datafile' => $files,
                'apprvMaster' => $apprvMaster,
            ];
        } else {
            $folder =  DocsLocationMaster::where('parent_loc', $idfolder)
                ->where('creator_loc', $user)
                ->with(['listDoc' => function ($q) {
                    $q->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version');
                }])
                ->with('getParents')
                ->with('users')
                ->get();

            $files = DocsMaster::where('doc_path', $idfolder)
                // ->where('doc_author', $user)
                ->with(['users', 'currentversion', 'apprvhist.getallapprover', 'tags.tagsMaster'])
                ->doesnthave('version')
                ->get();

            $cekSharedFiles = sharedMapping::where('shared_to', $user)->where('folder_id', $idfolder)->first();

            $sharedFiles = [];
            if (!empty($cekSharedFiles['files_id'])) {
                $sharedFiles = DocsMaster::where('doc_path', $idfolder)
                    ->with(['users', 'currentversion', 'apprvhist.getallapprover', 'tags.tagsMaster'])
                    ->doesnthave('version')
                    ->get();
            }
            return [
                'datafolder' => $folder,
                'datafile' => $files,
                'sharedFiles' => $sharedFiles,
                'apprvMaster' => $apprvMaster
            ];
        }
    }

    public function getApprovalList($user)
    {
        $select = [
            'apprv_author',
            'apprv_title',
            'apprv_id',
            'apprv_approver',
            'apprv_level'
        ];

        $apprvMasterFirst = ApprovalMaster::select($select)
            ->where('apprv_author', $user)
            ->with('userAuthor')
            ->with('user')
            ->with(['contentDef' => function ($q1) {
                $q1->with('contentDet');
                $q1->with('contentMstr');
            }])
            ->groupBy($select)
            ->orderBy('apprv_level')
            ->get()
            ->toArray();

        $apprvMaster = [];
        $count = 0;
        foreach ($apprvMasterFirst as $key => $value) {
            if ($key !== 0 && $apprvMasterFirst[$key - 1]['apprv_id'] !== $value['apprv_id']) {
                $count++;
            }
            $apprvMaster[$count]['apprv_author'] = $value['apprv_author'];
            $apprvMaster[$count]['apprv_title'] = $value['apprv_title'];
            $apprvMaster[$count]['apprv_id'] = $value['apprv_id'];
            $apprvMaster[$count]['user_author'] = $value['user_author'];
            $apprvMaster[$count]['content_def'] = $value['content_def'];
            $apprvMaster[$count]['content_def'] = $value['content_def'];
            $apprvMaster[$count]['apprv_detail'][$key]['apprv_approver'] = $value['apprv_approver'];
            $apprvMaster[$count]['apprv_detail'][$key]['apprv_level'] = $value['apprv_level'];
            $apprvMaster[$count]['apprv_detail'][$key]['user_approver'] = $value['user'];
        }
    }

    public function getFolder($user, $idFolder = '')
    {
        $folder = DocsLocationMaster::with('getParents')
            ->with('users')
            ->with(['listDoc' => function ($q) {
                $q->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version');
            }])
            ->where('creator_loc', $user)
            ->where('parent_loc', '0');

        if (empty($idFolder)) {
            $sharedFolder = sharedMapping::where('shared_to', $user)
                ->with(['docMaster.listDoc' => function ($q2) {
                    $q2->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version');
                }])
                ->with('docMaster.users')
                ->get();

            return [
                'dataFolder' => $folder->get(),
                'sharedfolder' => $sharedFolder
            ];
        } else {
            $cekSharedFiles = sharedMapping::where('shared_to', $user)->where('folder_id', $idfolder)->first();

            $sharedFiles = [];
            if (!empty($cekSharedFiles['files_id'])) {
                $sharedFiles = DocsMaster::where('doc_path', $idfolder)
                    ->with(['users', 'currentversion', 'apprvhist.getallapprover', 'tags.tagsMaster'])
                    ->doesnthave('version')
                    ->get();
            }

            return [
                'dataFolder' => $folder->where('parent_loc', $idFolder)->get(),
                'sharedfolder' => $sharedFiles
            ];
        }
    }

    public function getFiles($user, $idFolder = '')
    {
        $files = DocsMaster::with(['users', 'currentversion', 'apprvhist.getallapprover', 'tags.tagsMaster'])
            ->doesnthave('version');

        if (empty($idFolder)) {
            return $files->where('doc_path', '0')->where('doc_author', $user)->get()->toArray();
        } else {
            return $files->where('doc_path', $idFolder)->get()->toArray();
        }
    }

    public function store(CreateNewFolderRequest $r)
    {
        return DocsLocationMaster::create([
            'id' => Str::random(50),
            'parent_loc' => $r->parent_doc,
            'name_loc' => $r->folder_name,
            'creator_loc' => $r->header('username')
        ]);
    }

    public function deletefolder($id)
    {
        $cek_isi_doc = DocsLocationMaster::where('id', $id)
            ->doesnthave('getParents')
            ->doesnthave('listdoc')
            ->first();
        $cek_isinyac = DocsLocationMaster::where('id', $id)->first();

        if (empty($cek_isi_doc)) {
            return response([
                'message' => "The given data was invalid.",
                'errors' => [
                    'id' => ['Folder ' . $cek_isinyac['name_loc'] . ' not empty!!']
                ]
            ], 422);
        } else {
            DocsLocationMaster::where('id', $id)->delete();
            return 'success';
        }
    }

    public function getlistfolder($user)
    {
        return DocsLocationMaster::where('creator_loc', $user)
            ->with('allChildFolder')
            ->where('parent_loc', '0')
            ->get();
    }
}
