<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\ApprovalHist;
use Illuminate\Support\Str;
use App\Models\DMS\Core\DocsMaster;
use App\Models\PORTAL\DivisisPortal;
use App\Models\DMS\Core\ApprovalNotification;
use Illuminate\Support\Facades\DB;

use App\Models\DMS\Auth\DomainMaster;
use App\Models\DMS\Core\tagsMaster;

class DashboardController extends Controller
{
    public function masterQuery()
    {
        return DocsMaster::select(
            'doc_id',
            'doc_real_name',
            'doc_path',
            'created_at',
            'doc_author'
        )->with('version')
            ->with('users.group')
            // ->where('doc_author', $user)
            ->orderBy('doc_author')
            ->orderBy('created_at');
    }

    public function listnotif($user)
    {
        $gettotaldoc = $this->masterQuery()->with(['apprvhist' => function ($q) {
            $q->with('getallapprover')->with('notificationTo');
        }])->where('created_at', '>=', date('Y-m-d H:i:s', strtotime("-1 Months")));

        // return $gettotaldoc->get();
        $hasil = [
            'total_uploaded_doc' => $gettotaldoc->count(),
            'total_uploaded_user_doc' => $this->masterQuery()->with(['apprvhist' => function ($q) {
                $q->with('getallapprover')->with('notificationTo');
            }])->where('doc_author', $user)->count(),
        ];

        $countbydate = 0;
        $countbygroup = 0;

        $datanya = clone $gettotaldoc->get();

        // return $datanya;
        foreach ($datanya as $key => $value) {
            $getapproverlevel = isset($value['apprvhist']) > 0 ? $value['apprvhist']['getallapprover'][count($value['apprvhist']['getallapprover']) - 1] : '';
            $getdate = explode(' ', explode('T', $value['created_at'])[0])[0];
            if ($key !== 0 && $getdate !== explode(' ', explode('T', $datanya[$key - 1]['created_at'])[0])[0]) {
                $countbydate++;
            }

            if ($key !== 0 && $value['users']['role_id'] !== $datanya[$key - 1]['users']['role_id']) {
                $countbygroup++;
            }

            // By Date
            $hasil['uploaded_doc_det'][$countbydate]['upload_date'] = $getdate;

            $hasil['uploaded_doc_det'][$countbydate]['data_det'][] = [
                'doc_real_name' => $value['doc_real_name'],
                'created_at' => $value['created_at'],
                'last_approver' => $value['apprvhist'],
                'approver_list' => $value['apprvhist']['getallapprover'],
                'approver_level_count' => $getapproverlevel == '' ? 0 : $getapproverlevel['apprv_level'],
                'approver_flag' => isset($value['apprvhist'])
                    ? ($value['apprvhist']['apprv_hist_status'] === "1"
                        ? ($value['apprvhist']['approver_level'] === $getapproverlevel['apprv_level']
                            ? 3
                            : 2)
                        : 1)
                    : 0
            ];

            // By group User
            $hasil['uploaded_doc_role'][$countbygroup]['group'] = $value['users']['group']['domain_name'];

            $hasil['uploaded_doc_role'][$countbygroup]['data_det'][] = [
                'doc_real_name' => $value['doc_real_name'],
                'created_at' => $value['created_at'],
                'last_approver' => $value['apprvhist'],
                'approver_list' => $value['apprvhist']['getallapprover'],
                'approver_level_count' => $getapproverlevel == '' ? 0 : $getapproverlevel['apprv_level'],
                'approver_flag' => isset($value['apprvhist'])
                    ? ($value['apprvhist']['apprv_hist_status'] === "1"
                        ? ($value['apprvhist']['approver_level'] === $getapproverlevel['apprv_level']
                            ? 3
                            : 2)
                        : 1)
                    : 0
            ];

            // By User
            if ($value['doc_author'] == $user) {
                $hasil['uploaded_doc_det_by_user'][$countbydate]['upload_date'] = $getdate;
                $hasil['uploaded_doc_det_by_user'][$countbydate]['data_det'][] = [
                    'doc_real_name' => $value['doc_real_name'],
                    'created_at' => $value['created_at'],
                    'last_approver' => $value['apprvhist'],
                    'approver_list' => $value['apprvhist']['getallapprover'],
                    'approver_level_count' => $getapproverlevel == '' ? 0 : $getapproverlevel['apprv_level'],
                    'approver_flag' => isset($value['apprvhist'])
                        ? ($value['apprvhist']['apprv_hist_status'] === "1"
                            ? ($value['apprvhist']['approver_level'] === $getapproverlevel['apprv_level']
                                ? 3
                                : 2)
                            : 1)
                        : 0
                ];
            }
        }

        return $hasil;
    }

    public function readChildren($arr, $level)
    {
        if (count($arr['all_approver_list']) > 0) {
            # code...
        }
    }

    public function readnotif($user, $content, $apprv)
    {
        ApprovalNotification::where('apprv_user_to', $user)
            ->where('apprv_content_id', $content)
            ->where('apprv_id', $apprv)
            ->update([
                'apprv_read_flag' => date('Y-m-d H:i:s')
            ]);

        $notif = ApprovalNotification::where('apprv_user_to', $user)
            ->where('apprv_content_id', $content)
            ->where('apprv_id', $apprv)
            ->get();

        $hasil = [];
        foreach ($notif as $key => $value) {
            $hasil[] = $value;
            ApprovalHist::where('id', $value->apprv_hist_from_id)->update([
                'apprv_hist_vwtime' => date('Y-m-d H:i:s')
            ]);
        }

        return $hasil;
    }

    public function getalldocumentbyrole($div = null)
    {
        $selectdiv = [
            'domain_name',
            'role_id'
        ];
        if (empty($div)) {
            return DomainMaster::select($selectdiv)
                ->where('ROLE_ID', 'not like', '%ROOT%')
                ->where('ROLE_ID', 'like', '%DMS')
                ->with(['userdms' => function ($q1) {
                    $q1->select(
                        'username',
                        'first_name',
                        'last_name',
                        'email',
                        'role_id'
                    );
                    $q1->has('doc');
                    $q1->with(['doc' => function ($q2) {
                        $q2->select(
                            'doc_id',
                            'doc_name',
                            'doc_author',
                            'doc_real_name',
                            'doc_path',
                            'doc_stat_flag',
                            'created_at'
                        );
                        $q2->where('doc_stat_flag', '3');
                        $q2->doesnthave('version');
                        $q2->with('apprvhist.allApproverList');
                    }]);
                    $q1->with('docLocation.allChildFolder');
                    $q1->with('approver');
                }])
                ->orderBy('domain_name')
                ->get()
                ->toArray();
        } else {
            return DomainMaster::select($selectdiv)
                ->where('ROLE_ID', 'not like', '%ROOT%')
                ->where('ROLE_ID', 'like', '%DMS')
                ->where('id', $div)
                ->orderBy('domain_name')
                ->first()
                ->toArray();
        }
    }

    public function newDocumentLib()
    {

        $tags = tagsMaster::pluck('id')->toArray();
        $data = DocsMaster::with('users.group.group')->with(['tags' => function ($f) use ($tags) {
            $f->with('tagsMaster');
            $f->whereIn('tags_id', $tags);
        }])
        ->where('doc_stat_flag', 3)
        ->get()
        ->toArray();

        $hasilTaggedDocs = [];
        foreach (tagsMaster::get()->toArray() as $key => $value) {
            $cek = array_values(array_filter($data, function ($f) use ($value) {
                return count(array_filter($f['tags'], function ($f2) use ($value) { return $f2['tags_id'] == $value['id']; })) > 0;
            }));

            if (count($cek) > 0) {
                $hasilTaggedDocs[$value['tags_name']] = $cek;
            }
        }

        $hasilTaggedDocs["UNCATEGORIES"] = array_values(array_filter($data, function ($f) use ($value) {
            return count($f['tags']) === 0;
        }));

        return $hasilTaggedDocs;
    }

    public function getOutstandingByUser($user)
    {
        $gettotaldoc = $this->masterQuery()
            ->where('doc_stat_flag','1')
            ->with(['apprvhist' => function ($q) use ($user) {
                $q->with('getallapprover')->with(['notificationFrom' => function ($qdet) use ($user) {
                    $qdet->where('apprv_user_to', $user);
                }])->whereHas('notificationFrom', function ($qdet) use ($user) {
                    $qdet->where('apprv_user_to', $user)->whereNull('apprv_hist_to_id');
                });
            }])->whereHas('apprvhist', function ($q) use ($user) {
                $q->with('getallapprover')->with(['notificationFrom' => function ($qdet) use ($user) {
                    $qdet->where('apprv_user_to', $user);
                }])->whereHas('notificationFrom', function ($qdet) use ($user) {
                    $qdet->where('apprv_user_to', $user)->whereNull('apprv_hist_to_id');
                });
            })->get()
            ->toArray();

        $hasil = [];

        $countbygroup = 0;
        foreach ($gettotaldoc as $key => $value) {
            if ($key !== 0 && $value['users']['role_id'] !== $gettotaldoc[$key - 1]['users']['role_id']) {
                $countbygroup++;
            }

            $hasil[$countbygroup]['group'] = $value['users']['group']['domain_name'];
            $hasil[$countbygroup]['value'] = count(array_filter($gettotaldoc, function($q) use ($value) {
                if ($q['users']['group']['role_id'] === $value['users']['group']['role_id']) {
                    return true;
                }
            }));
        }

        return $hasil;
    }
}
