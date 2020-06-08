<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\ApprovalHist;
use Illuminate\Support\Str;
use App\Models\DMS\Core\DocsMaster;
use App\Models\DMS\Core\ContentCreator;
use App\Models\DMS\Core\ContentDefine;
use App\Models\DMS\Core\ApprovalNotification;
use Illuminate\Support\Facades\Mail;
use App\Jobs\DMS\NotificationEmailQueue;

use App\Mail\DMS\NotificationEmail;

class ApprovalController extends Controller
{
    public function ApproveDoc($iddoc, $author)
    {
        $getpath = DocsMaster::where('doc_id', $iddoc)->first();
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->setSourceFile('D:/data/' . $getpath['doc_real_path'] . $getpath['doc_name']);
        $tplIdx = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplIdx);
        logger($size);
        $pdf->AddPage();
        // use the imported page and place it at point 10,10 with a width of 100 mm
        $pdf->useTemplate($tplIdx, null, null, $size['width'], 310, FALSE);

        // now write some text above the imported page
        $pdf->SetFont('Helvetica');
        // $pdf->SetTextColor(0, 218, 26);

        $pdf->AddPage();

        $pdf->SetXY(10, 15);

        $pdf->Write(0, 'Approval Status');

        $cekapprvset = ApprovalMaster::where('apprv_author', $author)->with('user')->get()->toArray();

        foreach ($cekapprvset as $key => $value) {
            $pdf->SetXY(10, ($key + 1) * 25);

            $pdf->Cell(40, 5, ' ', 'LTR', 0, 'L', 0);   // empty cell with left,top, and right borders
            $pdf->Cell(100, 5, 'Digitally signed by @' . $value['user']['username'], 'LTR', 0, 'L', 0);
            // $pdf->Cell(50, 5, '222 Here', 1, 0, 'L', 0);

            $pdf->Ln();

            $pdf->SetFont('Times', 'BIU');
            $pdf->Cell(40, 5, $value['user']["first_name"] . ' ' . $value['user']["last_name"], 'LR', 0, 'C', 0);  // cell with left and right borders
            $pdf->SetFont('Helvetica');
            $pdf->Cell(100, 5, 'Email : ', 'LR', 0, 'L', 0);
            // $pdf->Cell(50, 5, '[ x ] che2', 'LR', 0, 'L', 0);

            $pdf->Ln();

            $pdf->Cell(40, 5, '', 'LR', 0, 'LR', 0);   // empty cell with left,bottom, and right borders
            $pdf->Cell(100, 5, 'Reason : ', 'LR', 0, 'L', 0);
            // $pdf->Cell(50, 5, '[ o ] def4', 'LRB', 0, 'L', 0);

            $pdf->Ln();

            $pdf->Cell(40, 5, '', 'LBR', 0, 'LR', 0);   // empty cell with left,bottom, and right borders
            $pdf->Cell(100, 5, 'Date : ', 'LRB', 0, 'L', 0);
            // $pdf->Cell(50, 5, '[ o ] def4', 'LRB', 0, 'L', 0);

            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();
        }

        $pdf->Output();

        return $pdf;
    }

    public function ApprovalSetup(Request $req)
    {
        // return $req;
        $tot = [];
        $ceklast = ApprovalMaster::where('apprv_id', 'like', 'APPRV' . date('ymd') . '%')->orderBy('apprv_id', 'desc')->first();
        if (empty($ceklast)) {
            $nextid = 'APPRV' . date('ymd') . '001';
        } else {
            $nextid = 'APPRV' . date('ymd') . sprintf('%04d', (int) substr($nextid->apprv_id, -3) + 1);
        }
        foreach ($req->author as $key => $value) {
            foreach ($req->apprv as $key_apprv => $value_apprv) {
                $tot[$value['username']][] = ApprovalMaster::create([
                    'id' => Str::random(50),
                    'apprv_author' => $value['username'],
                    'apprv_approver' => $value_apprv['user']['username'],
                    'apprv_email_notify' => 1,
                    'apprv_level' => $value_apprv['level'],
                    'apprv_mandatory' => $value_apprv['optapprv'],
                    'apprv_title' => $req->title,
                    'apprv_id' => $nextid
                ]);
            }
        }

        return $tot;
    }

    public function ApprovalSent(Request $req)
    {
        $cekapprover = ApprovalMaster::where('apprv_author', $req->user_apprv)->get()->toArray();

        if (count($cekapprover) > 0 || $req->parent_apprv !== '0') { //Jika peminta approver sudah di setting, atau jika parent approver nya bukan '0' atau root
            $hasil = [];
            $hasilsuccess = [];
            $nextid = "";

            if ($req->has('formnya')) { // Jika ada post dengan parameter 'formnya'
                $ceklast = ApprovalHist::where('content_creator_id', 'like', 'CRTR' . date('ymd') . '%')->orderBy('content_creator_id', 'desc')->first();
                if (empty($ceklast)) {
                    $nextid = 'CRTR' . date('ymd') . '0001';
                } else {
                    $nextid = 'CRTR' . date('ymd') . sprintf('%04d', (int) substr($ceklast['content_creator_id'], -3) + 1);
                }

                if (is_array($req->formnya) && count($req->formnya) > 0) {
                    foreach ($req->formnya as $key_form => $value_form) {
                        ContentCreator::create([
                            'content_var_id' => $key_form,
                            'content_var_value' => $value_form,
                            'content_users' => $req->user_apprv,
                            'content_creator_id' => $nextid
                        ]);
                    }
                }
            }

            if ($req->has('idcreator')) { // Jika ada post dengan parameter 'idcreator'
                $nextid = $req->idcreator;
            }

            foreach ($req->doc_id as $key => $value) { // Loop berdasarkan dokumen yang dimintai approval
                $cekhist = ApprovalHist::where('apprv_hist_doc', $value['doc_id'])->where('apprv_parent', $req->parent_apprv)->where('id_approval', $req->master_apprv)->first();
                if (!empty($cekhist)) {
                    $doc = DocsMaster::where('doc_id', $value['doc_id'])->first();
                    $hasil['errors'][] = ['Document ' . $doc['doc_real_name'] . ' already sent to the next approver!'];
                } else {
                    $idhistory = Str::random(50);
                    ApprovalHist::create([
                        'id' => $idhistory,
                        'apprv_parent' => $req->parent_apprv,
                        'apprv_hist_user' => $req->user_apprv,
                        'apprv_hist_doc' => $value['doc_id'],
                        'apprv_hist_comment'  => $req->comment,
                        'apprv_hist_status'  => $req->status,
                        'content_creator_id' => $nextid,
                        'id_approval' => $req->master_apprv,
                        'content_def_id' => $req->contentDefine,
                    ]);

                    if ($req->parent_apprv == '0') { // Jika si pembuat approval yang approve
                        DocsMaster::where('doc_id', $value['doc_id'])->update([
                            'doc_stat_flag' => '1'
                        ]);

                        $masterApprv = ApprovalMaster::where('apprv_author', $value['doc_author'])
                            ->where('apprv_id', $req->master_apprv)
                            ->where('apprv_level', '1')
                            ->get();

                        ApprovalHist::where('id', $idhistory)->update([
                            'approver_level' => "0"
                        ]);

                        foreach ($masterApprv as $key => $value) {
                            ApprovalNotification::create([
                                'apprv_user_from' => $req->user_apprv,
                                'apprv_user_to' => $value['apprv_approver'],
                                'apprv_hist_from_id' => $idhistory,
                                'apprv_content_id' => $nextid,
                                'apprv_id' => $req->master_apprv,
                                'content_def_id' => $req->contentDefine,
                                'approver_level' => "0",
                                'approver_level_to' => "1"
                            ]);

                            $hasilsuccess[$value['apprv_approver']] = $this->outstandingApprovalByApprover($value['apprv_approver'],null, true)->items();
                        }
                    } else { // Jika approver yang approve
                        $cekLevelHist = ApprovalHist::where('apprv_hist_doc',$value['doc_id'])->where('apprv_hist_user', $req->user_apprv)->first();

                        $masterApprv = ApprovalMaster::where('apprv_author', $value['doc_author'])->where('apprv_id', $req->master_apprv);

                        $ceklagi = clone $masterApprv->where('apprv_approver', $req->user_apprv)->where('apprv_level','<>',$cekLevelHist->approver_level)->orderBy('created_at','asc')->first();

                        ApprovalNotification::where('apprv_hist_from_id', $req->parent_apprv)
                            ->update([
                                'apprv_hist_to_id' => $idhistory,
                            ]);

                        $ceknextapprover = ApprovalMaster::where('apprv_author', $ceklagi['apprv_author'])
                            ->where('apprv_id', $req->master_apprv)
                            ->where('apprv_level', $ceklagi['apprv_level'] + 1)
                            ->get()
                            ->toArray();

                        if (count($ceknextapprover) > 0 && $req->status == "1") {
                            foreach ($ceknextapprover as $keyDet => $valueDet) {
                                ApprovalNotification::create([
                                    'apprv_user_from' => $req->user_apprv,
                                    'apprv_user_to' => $valueDet['apprv_approver'],
                                    'apprv_hist_from_id' => $idhistory,
                                    'apprv_content_id' => $nextid,
                                    'apprv_id' => $req->master_apprv,
                                    'content_def_id' => $req->contentDefine,
                                    'approver_level' => $ceklagi['apprv_level'],
                                    'approver_level_to' => $ceklagi['apprv_level'] + 1
                                ]);

                                $hasilsuccess[$valueDet['apprv_approver']] = $this->outstandingApprovalByApprover($valueDet['apprv_approver'],null, true)->items();

                                ApprovalHist::where('id', $idhistory)->update([
                                    'approver_level' => $ceklagi['apprv_level']
                                ]);
                            }
                        } else {
                            ApprovalNotification::create([
                                'apprv_user_from' => $req->user_apprv,
                                'apprv_user_to' => $ceklagi['apprv_author'],
                                'apprv_hist_from_id' => $idhistory,
                                'apprv_content_id' => $nextid,
                                'apprv_id' => $req->master_apprv,
                                'content_def_id' => $req->contentDefine,
                                'approver_level' => $ceklagi['apprv_level'],
                                'approver_level_to' => 0
                            ]);

                            ApprovalHist::where('id', $idhistory)->update([
                                'approver_level' => $ceklagi['apprv_level']
                            ]);
                            
                            $hasilsuccess[$ceklagi['apprv_author']] = $this->outstandingApprovalByApprover($ceklagi['apprv_author'],null, true)->items();                            

                            if ($req->status == "1") {
                                DocsMaster::where('doc_id', $value['doc_id'])->update([
                                    'doc_stat_flag' => '2'
                                ]);
                            }
                        }
                    }
                }
            }

            if (count($hasil) > 0) {
                return response($hasil, 422);
            } else {
                $newhasil = [];
                foreach ($hasilsuccess as $keyHasil => $valueHasil) {
                    $newhasil[$keyHasil] = [
                        'data' => $valueHasil,
                        'email_status' => $this->emailsender($keyHasil, $this->outstandingApprovalByApprover($keyHasil, null, true)->items()[0])
                    ];
                }
                
                return $newhasil;
            }
        } else {
            return response([
                'errors' => [
                    'approver' => ['Please setup the approver of this user first !!']
                ]
            ], 422);
        }
    }

    public function arrfet($arr, $gethasil)
    {
        if ($arr->approver_level !== '0') {
            if ($arr->allPastApproverList !== null) {
                return $this->arrfet($arr->allPastApproverList, array_merge($gethasil, [$arr]));
            } else {
                $gethasil[] = array_merge($gethasil, $arr);
            }
        }

        return $gethasil;
    }

    public function emailsender($user, $data)
    {
        // return $this->outstandingApprovalByApprover($user, null, true)->items()[0];
        $datadummy = $data;

        logger(json_encode($datadummy));

        $html = '<h2>Hello '.$datadummy->userTo->first_name.',</h2>
        <!-- <p>We have inform you about your pending approval and need your action immediately.</p> -->
        <p>We have got a notification for you, please login to link below and take an action immediately.</p>
        <p><a href="http://192.168.100.32:8081/dms">STX DMS</a></p><hr>';

        $html .= $datadummy->contentDefine->contentMstr->content_html;

        if (strpos($html, '|surname|')) {
            $html = str_replace('|surname|',$datadummy->histFromByContentId[0]->doc->users->first_name .' '.$datadummy->histFromByContentId[0]->doc->users->last_name, $html);
        } 
        
        if (strpos($html, '|approval_list_here|')) {
            $tableapprv = '<table style="border-collapse: collapse; width: 100%; height: 32px;" border="1">
            <tbody>
              <tr style="height: 16px;font-weight: bold">
                <td>No</td>
                <td>Signature</td>
                <td>Username</td>
                <td>Reason</td>
                <td>Signed At</td>
              </tr>';

            $no = 1;
            foreach (array_reverse($this->arrfet($datadummy->histFromByContentId[0], [])) as $key => $value) {
                $tableapprv .= '<tr>';
                $tableapprv .= '<td>'. $no .'</td>';
                $tableapprv .= "<td>". $value->users->username .'</td>';
                $tableapprv .= '<td>@'. $value->users->username .'</td>';
                $tableapprv .= '<td>'. $value->apprv_hist_comment .'</td>';
                $tableapprv .= '<td>'. $value->created_at .'</td>';
                $tableapprv .= '</tr>';

                $no++;
            }

            $html = str_replace('|approval_list_here|',$tableapprv, $html);
        }

        try {
            $insertJob = (new NotificationEmailQueue($datadummy->userTo->email, $datadummy->userTo, $html));

            dispatch($insertJob);            

            return 'Email success';
        } catch (\Exception $th) {
            return 'Email error';
        }
    }

    public function outstandingApproval($apprv_user, $level = null, $content_id = null)
    {
        $selectmstr = [
            'apprv_author',
            'apprv_email_notify',
            'apprv_level',
            'apprv_approver'
        ];

        $selecthist = [
            'id',
            'apprv_parent',
            'apprv_hist_user',
            'apprv_hist_doc',
            'apprv_hist_comment',
            'apprv_hist_status',
            'apprv_hist_vwtime',
            'created_at',
            'content_creator_id',
            'content_def_id'
        ];

        $cekmaster = ApprovalMaster::select($selectmstr)
            ->where('apprv_approver', $apprv_user)
            // ->where('apprv_author', $author)
            ->when(!empty($level), function ($l) use ($level) {
                $l->where('apprv_level', $level);
            })
            ->when(!empty($content_id), function ($c) use ($content_id) {
                $c->where('apprv_id', $content_id);
            })
            ->get()
            ->toArray();

        // return $cekmaster;

        $hasil = [];
        foreach ($cekmaster as $key => $value) {
            $cekbefore = ApprovalMaster::select($selectmstr)
                ->where('apprv_author', $value['apprv_author'])
                ->where('apprv_level', $value['apprv_level'] - 1)
                ->first();

            if ($value['apprv_level'] == 1) { // Jika level array saat ini == 1 maka
                $cekhist = ApprovalHist::select($selecthist)
                    ->where('apprv_hist_user', $value['apprv_author'])
                    ->where('apprv_parent', '=', '0')
                    ->with('doc.users')
                    ->WhereHas('doc', function ($qdoc) use ($value) {
                        $qdoc->where('doc_author', $value['apprv_author']);
                    })
                    ->with(['child' => function ($q) use ($value) {
                        $q->where('apprv_hist_user', $value['apprv_approver']);
                    }])
                    ->with('content')
                    ->where('apprv_hist_status', '1')
                    ->get()
                    ->toArray();
            } else {
                $cekhist = ApprovalHist::select($selecthist)
                    ->where('apprv_hist_user', $cekbefore['apprv_approver'])
                    ->where('apprv_parent', '<>', '0')
                    ->with('doc.users')
                    ->WhereHas('doc', function ($qdoc) use ($value) {
                        $qdoc->where('doc_author', $value['apprv_author']);
                        $qdoc->with('users');
                    })
                    ->with('content')
                    ->where('apprv_hist_status', '1')
                    ->with(['child' => function ($q) use ($value) {
                        $q->where('apprv_hist_user', $value['apprv_approver']);
                    }])
                    ->get()
                    ->toArray();
            }

            if (!empty($cekbefore)) {
                $hasil[] = array_merge((array) $value, [
                    'approver_before' => $this->outstandingApproval($cekbefore['apprv_approver'], $value['apprv_level'] - 1),
                    'approve_status' => $cekhist
                ]);
            } else {
                $hasil[] = array_merge((array) $value, [
                    'approver_before' => [],
                    'approve_status' => $cekhist
                ]);
            }
        }

        return $hasil;
    }

    public function outstandingApprovalByApprover($approver, $inout = null, $last = false)
    {
        $selecthist = [
            'id',
            'apprv_parent',
            'apprv_hist_user',
            'apprv_hist_doc',
            'apprv_hist_comment',
            'apprv_hist_status',
            'apprv_hist_vwtime',
            'created_at',
            'content_creator_id',
            'id_approval',
            'approver_level'
        ];

        $selectNotif = [
            'apprv_user_from',
            'apprv_user_to',
            'apprv_content_id',
            'apprv_id',
            'content_def_id',
            'apprv_read_flag',
            'approver_level',
            'approver_level_to'
        ];

        $ceknotif = ApprovalNotification::select($selectNotif)
            ->with(['histFromByContentId' => function ($q) use ($selecthist) {
                $q->select($selecthist);
                $q->with('doc.users');
                $q->with('allPastApproverList');
                $q->with('users');
            }])
            ->with(['histToByContentId' => function ($q) use ($selecthist) {
                $q->select($selecthist);
                $q->with('users');
                $q->with('doc.users');
                $q->with('allApproverList');
            }])
            ->with('contentVariable')
            ->with('contentDefine.contentMstr')
            ->with(['userFrom', 'userTo'])
            ->with('approvalMaster')
            ->groupBy($selectNotif)
            ->orderBy('apprv_content_id', 'desc')
            ->orderBy('approver_level', 'desc');
            // ->get()
            // ->toArray();
        
        if (!empty($inout)) {
            $hasil = $inout == 'inc' 
                ? $ceknotif->where('apprv_user_to', $approver) 
                : $ceknotif->where('apprv_user_from', $approver);
        } else {
            $hasil = $ceknotif
            ->where('apprv_user_to', $approver)
            ->orWhere('apprv_user_from', $approver);
        }

        if ($last == true) {
            return $hasil->paginate(1);
        } else {
            return $hasil->paginate(3);
        }
    }

    public function listDocSenttoApprover($user)
    {
        return ApprovalHist::where('apprv_hist_user', $user)
            ->where('apprv_parent', '0')
            ->with('doc.users')
            ->with('allApproverList')
            ->with('getallapprover.user')
            ->with('lastapprv')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function ApprovalList($user)
    {
        return $this->AllApprovalList($user);
        $selectMaster = [
            'apprv_author',
            'apprv_title',
            'apprv_id'
        ];

        $cekMaster = ApprovalMaster::select($selectMaster)
            ->where('apprv_author', $user)
            ->with(['contentDef' => function ($q3){
                $q3->with(['contentDet','contentMstr']);
                $q3->doesnthave('mappingApp');
            }])
            ->with(['approvalDocSet' => function ($q) {
                $q->with('doc');
                $q->wheredoesnthave('docHist');
            }])
            ->with(['docs' => function($q2){
                $q2->where('doc_lapprv_flag', '1');
                $q2->wheredoesnthave('docHist');
            }])
            ->groupBy($selectMaster)
            ->get()
            ->toArray();

        return $cekMaster;
    }

    public function AllApprovalList($user = null)
    {
        $selectMaster = [
            'apprv_author',
            'apprv_approver',
            'apprv_level',
            'apprv_title',
            'apprv_id'
        ];
        if (empty($user)) {
            $cekall = ApprovalMaster::select($selectMaster)->with('userAuthor')->with(['contentDef' => function ($q3){
                $q3->with(['contentDet','contentMstr']);
                $q3->doesnthave('mappingApp');
            }])
            ->with(['docs' => function($q2){
                $q2->where('doc_lapprv_flag', '1');
                $q2->wheredoesnthave('docHist');
                $q2->orderBy('created_at','desc');
            }])
            ->with('user')->get()->toArray();
        } else {
            $cekall = ApprovalMaster::select($selectMaster)->with('userAuthor')->with(['contentDef' => function ($q3){
                $q3->with(['contentDet','contentMstr']);
                $q3->doesnthave('mappingApp');
            }])
            ->with(['docs' => function($q2){
                $q2->where('doc_lapprv_flag', '1');
                $q2->wheredoesnthave('docHist');
                $q2->orderBy('created_at','desc');
            }])
            ->where('apprv_author', $user)->with('user')->get()->toArray();
        }
        
        // return $cekall;
        $count = 0;
        $countdet = 0;
        $hasil = [];
        foreach ($cekall as $key => $value) {
            if ($key!== 0 && ($value['apprv_id'] !== $cekall[$key -1]['apprv_id'])) {
                $count++;
                $countdet = 0;
            }

            $hasil[$count]['USERNAME'] = $value['apprv_author'];
            $hasil[$count]['AUTHOR'] = $value['user_author']['first_name'].' '.$value['user_author']['last_name'];
            $hasil[$count]['TITLE_APPRV'] = $value['apprv_title'];
            $hasil[$count]['ID_APPRV'] = $value['apprv_id'];
            $hasil[$count]['DET'][$countdet] = $value;
            $hasil[$count]['DOCS'] = $value['docs'];
            $hasil[$count]['CONTENT_DEF'] = $value['content_def'];

            $countdet++;
        }

        return $hasil;
    }

    public function updateApprovalList(Request $req){
        ApprovalMaster::where('apprv_id', $req->id_apprv)->delete();
        foreach ($req->datanya as $key => $value) {
            ApprovalMaster::create([
                'id' => Str::random(50),
                'apprv_author' => $value['apprv_author'],
                'apprv_approver' => $value['apprv_approver'],
                'apprv_email_notify' => 1,
                'apprv_level' => $value['apprv_level'],
                'apprv_mandatory' => $value['apprv_mandatory'],
                'apprv_title' => $value['apprv_title'],
                'apprv_id' => $value['apprv_id'],
            ]);
        }

        return 'success';
    }
}
