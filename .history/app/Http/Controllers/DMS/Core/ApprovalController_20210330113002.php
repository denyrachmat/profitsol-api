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
use Illuminate\Support\Facades\DB;
use Excel;
use App\Exports\DMS\exportDocumentStatus;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use App\Models\DMS\Core\LogicalMaster;

use Illuminate\Support\Carbon;

use App\Jobs\DMS\ReminderPendingApprovalJobs;

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
        $tot = [];
        $ceklast = ApprovalMaster::where('apprv_id', 'like', 'APPRV' . date('ymd') . '%')->orderBy('apprv_id', 'desc')->first();
        if (empty($ceklast)) {
            $nextid = 'APPRV' . date('ymd') . '001';
        } else {
            $nextid = 'APPRV' . date('ymd') . sprintf('%04d', (int) substr($ceklast->apprv_id, -3) + 1);
        }

        foreach ($req->author as $key => $value) {
            foreach ($req->apprv as $key_apprv => $value_apprv) {
                $idApprv = Str::random(50);
                $tot[$value['username']][] = ApprovalMaster::create([
                    'id' => $idApprv,
                    'apprv_author' => $value['username'],
                    'apprv_approver' => $value_apprv['user']['username'],
                    'apprv_email_notify' => 1,
                    'apprv_level' => $value_apprv['level'],
                    'apprv_mandatory' => $value_apprv['optapprv'],
                    'apprv_title' => $req->title,
                    'apprv_id' => $nextid
                ]);

                if (isset($value_apprv['logics'])) {
                    $idAccum = [];
                    foreach ($value_apprv['logics'] as $key_logics => $value_logics) {
                        foreach ($value_logics as $key_logics_det => $value_logics_det) {
                            $ceklastid = LogicalMaster::latest('created_at')->first();
                            if (empty($ceklastid))
                                $id = 'LOG-' . date('ymd') . '0001';
                            else
                                $id = 'LOG-' . date('ymd') . sprintf("%04d", intval(substr($ceklastid->logical_id, -4)) + 1);

                            LogicalMaster::create([
                                'logical_id' => $id,
                                'apprv_group_id' => $nextid,
                                'apprv_id' => $idApprv,
                                'logical_connection' => isset($value_logics_det['andor']) ? $value_logics_det['andor'] : '',
                                'logical_type' => $value_logics_det['type'],
                                'logical_cond' => isset($value_logics_det['condition']) ? $value_logics_det['condition'] : '',
                                'logical_param' => isset($value_logics_det['logicsCond']) ? $value_logics_det['logicsCond'] : '',
                                'logical_val_opt' => isset($value_logics_det['optCond']) ? $value_logics_det['optCond'] : '',
                                'logical_val' => $value_logics_det['valueCond'],
                                'logical_parent' => $key_logics_det === 0 ? '0' : $idAccum[$key_logics_det - 1]
                            ]);

                            $idAccum[$key_logics_det] = $id;
                        }
                    }
                }
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

                            $hasilsuccess[$value['apprv_approver']] = $this->outstandingApprovalByApprover($value['apprv_approver'], null, true)->items();
                        }
                    } else { // Jika approver yang approve
                        // $cekLevelHist = ApprovalHist::where('apprv_hist_doc', $value['doc_id'])->where('apprv_hist_user', $req->user_apprv)->first();
                        //Get last inserted approval
                        $cekLevelHist = ApprovalHist::where('apprv_hist_doc', $value['doc_id'])
                            ->with('doc.users')
                            ->where('approver_level', '<>', null)
                            ->latest()
                            ->first();

                        //Update apprv_hist_to_id to latest approval hist
                        ApprovalNotification::where('apprv_hist_from_id', $req->parent_apprv)
                            ->update([
                                'apprv_hist_to_id' => $idhistory,
                            ]);

                        $ceknotifjuga = ApprovalNotification::where('apprv_hist_from_id', $cekLevelHist->id)->first();

                        $nextLevel = empty($ceknotifjuga) ? strval(intval($cekLevelHist['approver_level']) + 2) : strval(intval($ceknotifjuga['approver_level_to']) + 1);

                        // $nextLevel = strval(intval($cekLevelHist['approver_level']) + 2);
                        $ceknextapprover = ApprovalMaster::where('apprv_id', $req->master_apprv)
                            ->where('apprv_level', $nextLevel)
                            ->with('logical.allChildList')
                            ->with(['logical' => function ($q) {
                                $q->where('logical_parent', '0');
                                $q->with('allChildList');
                            }])
                            ->get()
                            ->toArray();

                        if (count($ceknextapprover) > 0 && $req->status == "1") {
                            logger([$nextLevel, $ceknextapprover, $value['doc_id'], $idhistory, $nextid, empty($ceknotifjuga) ? $cekLevelHist['approver_level'] : $ceknotifjuga['approver_level_to'], $req]);
                            $notifnya = $this->checkTheApprovalCycle($nextLevel, $ceknextapprover, $value['doc_id'], $idhistory, $nextid, empty($ceknotifjuga) ? $cekLevelHist['approver_level'] : $ceknotifjuga['approver_level_to'], $req);

                            $hasilsuccess = $notifnya;
                        } else {
                            $hasilsuccess = $this->checkTheApprovalCycle($nextLevel, $ceknextapprover, $value['doc_id'], $idhistory, $nextid, $ceknotifjuga['approver_level_to'], $req, 0, true);
                            // logger([$ceknextapprover, $req->status]);
                            // ApprovalNotification::create([
                            //     'apprv_user_from' => $req->user_apprv,
                            //     'apprv_user_to' => $cekLevelHist->doc->users->username,
                            //     'apprv_hist_from_id' => $idhistory,
                            //     'apprv_content_id' => $nextid,
                            //     'apprv_id' => $req->master_apprv,
                            //     'content_def_id' => $req->contentDefine,
                            //     'approver_level' => $cekLevelHist['approver_level'] + 1,
                            //     'approver_level_to' => 0
                            // ]);

                            // ApprovalHist::where('id', $idhistory)->update([
                            //     'approver_level' => $cekLevelHist['approver_level'] + 1
                            // ]);

                            // if ($req->status == "1") {
                            //     DocsMaster::where('doc_id', $value['doc_id'])->update([
                            //         'doc_stat_flag' => '2'
                            //     ]);
                            // } else {
                            //     DocsMaster::where('doc_id', $value['doc_id'])->update([
                            //         'doc_stat_flag' => '0'
                            //     ]);
                            // }

                            // $hasilsuccess[$cekLevelHist->doc->users->username] = $this->outstandingApprovalByApprover($cekLevelHist->doc->users->username, null, true)->items();
                        }
                    }
                }
            }

            if (count($hasil) > 0) {
                return response($hasil, 422);
            } else {
                $newhasil = [];
                // foreach ($hasilsuccess as $keyHasil => $valueHasil) {
                //     $newhasil[$keyHasil] = [
                //         'data' => $valueHasil,
                //         'email_status' => $this->emailsender($keyHasil, $this->outstandingApprovalByApprover($keyHasil, null, true)->items()[0])
                //     ];
                // }


                $newhasil = [
                    'data' => $hasilsuccess,
                    'email_status' => $this->emailsender('', $this->outstandingApprovalByApprover($keyHasil, null, true)->items()[0])
                ];

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

    public function checkTheApprovalCycle($qLevel, $valnextapprv, $doc, $idhistory, $idcontent, $currentLevel, $req, $count = 0, $backToSender = false)
    {
        $data = $this->sendNotification($valnextapprv, $doc, $idhistory, $idcontent, $currentLevel, $req, $backToSender);
        if (count($data) === 0) {
            logger('tidak ada datanya');
            logger(json_encode([$valnextapprv, $doc, $idhistory, $idcontent, $currentLevel]));
            $nextLevel = strval(intval($qLevel) + 1);

            $ceknextapprover = ApprovalMaster::where('apprv_id', $req->master_apprv)
                ->where('apprv_level', $nextLevel)
                ->with('logical.allChildList')
                ->with(['logical' => function ($q) {
                    $q->where('logical_parent', '0');
                    $q->with('allChildList');
                }])
                ->get()
                ->toArray();

            if (!empty($ceknextapprover)) {
                $count++;
                return $this->checkTheApprovalCycle($nextLevel, $ceknextapprover, $doc, $idhistory, $idcontent, $currentLevel, $req, $count);
            } else {
                return $this->checkTheApprovalCycle($nextLevel, $ceknextapprover, $doc, $idhistory, $idcontent, $currentLevel, $req, $count, true);
            }
        } else {
            return $data;
        }
    }

    public function sendNotification($valnextapprv, $doc, $idhistory, $idcontent, $currentLevel, $req, $backToSender = false)
    {
        $cekSukses = [];
        if (!$backToSender) {
            foreach ($valnextapprv as $keyDet => $valueDet) {
                $cekLogical = $this->logicalCheck($valueDet['logical'], $doc);
                $hasilLogic = !$cekLogical ? 'false' : (count($cekLogical) === 1 ? $cekLogical[0] : implode('&&', $cekLogical));
                if (!$valueDet['logical'] || ($cekLogical && global_parse_condition($hasilLogic) === true)) {
                    ApprovalNotification::create([
                        'apprv_user_from' => $req->user_apprv,
                        'apprv_user_to' => $valueDet['apprv_approver'],
                        'apprv_hist_from_id' => $idhistory,
                        'apprv_content_id' => $idcontent,
                        'apprv_id' => $req->master_apprv,
                        'content_def_id' => $req->contentDefine,
                        'approver_level' => $currentLevel,
                        'approver_level_to' => $valueDet['apprv_level']
                    ]);

                    $cekSukses[$valueDet['apprv_approver']] = $this->outstandingApprovalByApprover($valueDet['apprv_approver'], null, true)->items();

                    ApprovalHist::where('id', $idhistory)->update([
                        'approver_level' => $currentLevel
                    ]);
                }
            }
        } else {
            $getSender = ApprovalMaster::where('apprv_id', $req->master_apprv)->first();

            ApprovalNotification::create([
                'apprv_user_from' => $req->user_apprv,
                'apprv_user_to' => $getSender->apprv_author,
                'apprv_hist_from_id' => $idhistory,
                'apprv_content_id' => $idcontent,
                'apprv_id' => $req->master_apprv,
                'content_def_id' => $req->contentDefine,
                'approver_level' => $currentLevel,
                'approver_level_to' => 0
            ]);

            ApprovalHist::where('id', $idhistory)->update([
                'approver_level' => $currentLevel
            ]);

            if ($req->status == "1") {
                DocsMaster::where('doc_id', $doc)->update([
                    'doc_stat_flag' => '2'
                ]);
            } else {
                DocsMaster::where('doc_id', $doc)->update([
                    'doc_stat_flag' => '0'
                ]);
            }

            $cekSukses[$getSender->apprv_author] = $this->outstandingApprovalByApprover($getSender->apprv_author, null, true)->items();
        }

        return $cekSukses;
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

    public function findSenderComment($data) {
        if ($data['approver_level'] === '0') {
            return $data['apprv_hist_comment'];
        } else {
            if (empty($data->allPastApproverList)) {
                return $data['apprv_hist_comment'];
            } else {
                return $this->findSenderComment($data->allPastApproverList);
            }
        }
    }

    public function emailsender($data)
    {
        // return $this->outstandingApprovalByApprover($user, null, true)->items()[0];
        $datadummy = $data;

        $listDocs = '<ul>';
        foreach ($datadummy->histFromByContentId as $key => $value) {
            $listDocs .= '<li><b>'.$value->doc->doc_real_name.'</b></li>';
        }
        $listDocs .= '</ul>';

        $html = '<h2>Hello ' . $datadummy->userTo->first_name . ',</h2>
        <!-- <p>We have inform you about your pending approval and need your action immediately.</p> -->
        <p>We have a notification for you, please login to link below and take an action immediately.</p>
        <p><a href="http://192.168.100.32:8081/dms">STX DMS</a></p><hr>
        <p>Files: </p>
        <p>
            '.$listDocs.'
        </p>
        <hr>
        <p>Sender Remark: '.$this->findSenderComment($datadummy->histFromByContentId[0]).'<b></b></p>';

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
            // $json_array = json_decode($value->apprv_hist_comment);

            // logger($json_array);
            if ($value->apprv_hist_status == "1") {
                $tableapprv .= '<tr>';
                $tableapprv .= '<td>' . $no . '</td>';
                $tableapprv .= "<td>" . $value->users->username . '</td>';
                $tableapprv .= '<td>@' . $value->users->username . '</td>';
                $tableapprv .= !json_decode($value->apprv_hist_comment) ? '<td>' .json_encode($value->apprv_hist_comment). '</td>' : '<td>Login to view comment</td>';
                $tableapprv .= '<td>' . $value->created_at . '</td>';
                $tableapprv .= '</tr>';
            } else {
                $tableapprv .= '<tr style="background-color: red">';
                $tableapprv .= '<td>' . $no . '</td>';
                $tableapprv .= "<td>" . $value->users->username . '</td>';
                $tableapprv .= '<td>@' . $value->users->username . '</td>';
                $tableapprv .= !json_decode($value->apprv_hist_comment) ? '<td>' .json_encode($value->apprv_hist_comment). '</td>' : '<td>Login to view comment</td>';
                $tableapprv .= '<td>' . $value->created_at . '</td>';
                $tableapprv .= '</tr>';
            }

            $no++;
        }

        $html .= $tableapprv;

        if ($datadummy->contentDefine !== null) {
            $html .= $datadummy->contentDefine->contentMstr->content_html;

            if (strpos($html, '|surname|')) {
                $html = str_replace('|surname|', $datadummy->histFromByContentId[0]->doc->users->first_name . ' ' . $datadummy->histFromByContentId[0]->doc->users->last_name, $html);
            }

            if (strpos($html, '|approval_list_here|')) {
                $html = str_replace('|approval_list_here|', '', $html);
            }
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

    public function outstandingApprovalByApprover($approver, $inout = null, $last = false, $filter = null)
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
            ->with(['histFromByContentId' => function ($q) use ($selecthist, $approver) {
                $q->select($selecthist);
                $q->with('doc.users');
                $q->with(['notificationFrom' => function ($q2) use ($approver) {
                    $q2->where('apprv_user_to', $approver);
                }]);
                $q->with('allPastApproverList');
                $q->with('users');
            }])
            ->with(['histToByContentId' => function ($q) use ($selecthist, $approver) {
                $q->select($selecthist);
                $q->with('users');
                $q->with('doc.users');
                $q->with(['notificationTo' => function ($q2) use ($approver) {
                    $q2->where('apprv_user_from', $approver);
                }]);
                $q->with('allApproverList');
            }])
            ->with(['histToBySameLevel' => function ($q) use ($selecthist, $approver) {
                $q->select($selecthist);
                $q->with('users');
                $q->with('doc.users');
                $q->with('allApproverList');
                // $q->whereHas('notificationTo' , function ($q2) use ($approver) {
                //     $q2->where('apprv_user_from', $approver);
                // });
            }])
            ->with('contentVariable')
            ->with('contentDefine.contentMstr')
            ->with(['userFrom', 'userTo'])
            ->with('approvalMaster.userAuthor')
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

        if (!empty($filter)) {
            $test = $hasil;
            foreach ($filter as $key => $value) {
                $test->{$value['op']}($value['col'], $value['condition'], $value['val']);
            }

            $perpage = $test->count();

            return $test->paginate(-1);
        }

        if ($last == true) {
            return $hasil->paginate(1);
        } else {
            return $hasil->paginate(5);
        }
    }

    public function filtermail(Request $r, $user, $type)
    {
        if ($r->has('receivedate') && !empty($r->receivedate)) {
            $dataawal = [
                [
                    'op' => 'where',
                    'col' => 'created_at',
                    'condition' => '>=',
                    'val' => $r->receivedate . ' 00:00:00'
                ],
                [
                    'op' => 'where',
                    'col' => 'created_at',
                    'condition' => '<=',
                    'val' => $r->receivedate . ' 23:59:59'
                ]
            ];
        } else {
            $dataawal = [];
        }

        if ($r->has('reviewed') && $r->reviewed === 'unread') {
            $data = array_merge($dataawal, [[
                'op' => 'where',
                'col' => 'apprv_hist_to_id',
                'condition' => '=',
                'val' => NULL
            ]]);
        } else {
            $data = $dataawal;
        }

        // return $data;

        return $this->outstandingApprovalByApprover($user, $type, false, $data);
    }

    public function listDocSenttoApprover($user, $lastapprvstat = null, $date = null, $doc = null)
    {
        $hasil = ApprovalHist::where('apprv_parent', '0')
            ->with('allApproverList')
            ->with(['getallapprover' => function ($q) {
                $q->with('user')->with('logical');
            }])
            ->with('lastapprv.users')
            ->orderBy('created_at', 'desc')
            ->with('doc.users');

        if (!empty($doc)) {
            $hasil->whereHas('doc', function ($q) use ($doc) {
                $q->where('doc_id', $doc);
            });
        } else {
            $hasil->where('apprv_hist_user', $user);
        }

        if (empty($lastapprvstat) && empty($date)) {
            $hasil;
        } else {
            if (!empty($lastapprvstat)) {
                $hasil->whereHas('doc', function ($q) use ($lastapprvstat) {
                    $q->where('doc_stat_flag', $lastapprvstat == 'partial' ? '1' : ($lastapprvstat == 'full' ? '2' : '0'));
                    $q->with('users');
                });
            }

            if (!empty($date)) {
                $hasil->whereBetween('created_at', [$date . ' 00:00:00', $date . ' 23:59:59']);
            }
        }

        // $parsing = [];

        // foreach ($hasil->get()->toArray() as $key => $valueNya) {
        //     $parsing[] = $valueNya;
        //     // $parsing[] = array_merge($valueNya, ['status_logical_doc' => $this->logicalCheck($valueNya['logical'], $doc)]);
        // }

        return $hasil
            ->get();
    }

    public function listDocSenttoApproverByDoc($user, $doc = null)
    {
        return $this->listDocSenttoApprover($user, null, null, $doc);
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
            ->with(['contentDef' => function ($q3) {
                $q3->with(['contentDet', 'contentMstr']);
                $q3->doesnthave('mappingApp');
            }])
            ->with(['approvalDocSet' => function ($q) {
                $q->with('doc');
                $q->wheredoesnthave('docHist');
            }])
            ->with(['docs' => function ($q2) {
                $q2->where('doc_lapprv_flag', '1');
                $q2->wheredoesnthave('docHist');
            }])
            ->with('user')
            ->groupBy($selectMaster)
            ->get()
            ->toArray();

        return $cekMaster;
    }

    public function AllApprovalList($user = null)
    {
        $selectMaster = [
            'id',
            'apprv_author',
            'apprv_approver',
            'apprv_level',
            'apprv_title',
            'apprv_id'
        ];
        if (empty($user)) {
            $cekall = ApprovalMaster::select($selectMaster)->with('userAuthor')->with(['contentDef' => function ($q3) {
                $q3->with(['contentDet', 'contentMstr']);
                $q3->doesnthave('mappingApp');
            }])
                ->with(['docs' => function ($q2) {
                    $q2->where('doc_lapprv_flag', '1');
                    $q2->wheredoesnthave('docHist');
                    $q2->orderBy('created_at', 'desc');
                }])
                ->with('user')
                ->with('logical')
                ->orderBy('apprv_id')->get()->toArray();
        } else {
            $cekall = ApprovalMaster::select($selectMaster)->with('userAuthor')->with(['contentDef' => function ($q3) {
                $q3->with(['contentDet', 'contentMstr']);
                $q3->doesnthave('mappingApp');
            }])
                ->with(['docs' => function ($q2) {
                    $q2->where('doc_lapprv_flag', '1');
                    $q2->wheredoesnthave('docHist');
                    $q2->orderBy('created_at', 'desc');
                }])
                ->with('logical')
                ->orderBy('apprv_id')->where('apprv_author', $user)->with('user')->get()->toArray();
        }

        // return $cekall;
        $count = 0;
        $countdet = 0;
        $hasil = [];
        foreach ($cekall as $key => $value) {
            if ($key !== 0 && ($value['apprv_id'] !== $cekall[$key - 1]['apprv_id'])) {
                $count++;
                $countdet = 0;
            }

            $hasil[$count]['USERNAME'] = $value['apprv_author'];
            $hasil[$count]['AUTHOR'] = $value['user_author']['first_name'] . ' ' . $value['user_author']['last_name'];
            $hasil[$count]['TITLE_APPRV'] = $value['apprv_title'];
            $hasil[$count]['ID_APPRV'] = $value['apprv_id'];
            $hasil[$count]['DET'][$countdet] = $value;
            $hasil[$count]['DOCS'] = $value['docs'];
            $hasil[$count]['CONTENT_DEF'] = $value['content_def'];

            $countdet++;
        }

        return $hasil;
    }

    public function updateApprovalList(Request $req)
    {
        ApprovalMaster::where('apprv_id', $req->id_apprv)->delete();
        LogicalMaster::where('apprv_group_id', $req->id_apprv)->delete();
        foreach ($req->datanya as $key => $value) {
            $idApprv = Str::random(50);
            ApprovalMaster::create([
                'id' => $idApprv,
                'apprv_author' => $value['apprv_author'],
                'apprv_approver' => $value['apprv_approver'],
                'apprv_email_notify' => 1,
                'apprv_level' => $value['apprv_level'],
                'apprv_mandatory' => 0,
                'apprv_title' => $value['apprv_title'],
                'apprv_id' => $value['apprv_id'],
            ]);

            if (count($value['logical']) > 0) {
                $idAccum = [];
                foreach ($value['logical'] as $key_logics_det => $value_logics_det) {
                    $ceklastid = LogicalMaster::latest('created_at')->first();
                    if (empty($ceklastid))
                        $id = 'LOG-' . date('ymd') . '0001';
                    else
                        $id = 'LOG-' . date('ymd') . sprintf("%04d", intval(substr($ceklastid->logical_id, -4)) + 1);

                    LogicalMaster::create([
                        'logical_id' => $id,
                        'apprv_group_id' => $value['apprv_id'],
                        'apprv_id' => $idApprv,
                        'logical_connection' => isset($value_logics_det['logical_connection']) ? $value_logics_det['logical_connection'] : '',
                        'logical_type' => isset($value_logics_det['logical_type']) ? $value_logics_det['logical_type'] : '',
                        'logical_cond' => isset($value_logics_det['logical_cond']) ? $value_logics_det['logical_cond'] : '',
                        'logical_param' => isset($value_logics_det['logical_param']) ? $value_logics_det['logical_param'] : '',
                        'logical_val_opt' => isset($value_logics_det['logical_val_opt']) ? $value_logics_det['logical_val_opt'] : '',
                        'logical_val' => isset($value_logics_det['logical_val']) ? $value_logics_det['logical_val'] : '',
                        'logical_parent' => $key_logics_det === 0 ? '0' : $idAccum[$key_logics_det - 1]
                    ]);

                    $idAccum[$key_logics_det] = $id;
                }
            }
        }

        return 'success';
    }

    public function checkPendingApproval()
    {
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

        $ceknotifkosong = ApprovalNotification::select($selectNotif)
            ->where('apprv_hist_to_id', null)
            ->where('created_at', '<=', date('Y-m-d H:i:s', strtotime("-1 week")))
            ->where('approver_level_to', '<>', '0')
            ->with(['histFromByContentId' => function ($q) use ($selecthist) {
                $q->select($selecthist);
                $q->with('doc.users');
                $q->with('users');
            }])
            ->with('userFrom')
            ->with('userTo')
            ->get();

        logger('scheduller jalan loh');

        // return $ceknotifkosong;
        if (empty($ceknotifkosong)) {
            return 'No pending approval';
        } else {
            foreach ($ceknotifkosong as $key => $value) {
                try {
                    $insertJob = (new ReminderPendingApprovalJobs($value->histFromByContentId, $value->userTo, $value->userFrom));

                    dispatch($insertJob);
                } catch (\Exception $th) {
                    logger('Email error Reminder Notification');
                }
            }

            return 'Email success';
        }
    }

    public function downloadDocstatus(Request $req)
    {
        return Excel::download(new exportDocumentStatus($req->data), 'StatusDocument.xlsx');
    }

    public function logicalCheck($dataLogic, $fileLoc)
    {
        $hasilAll = [];
        foreach ($dataLogic as $key => $value) {
            $getpath = DocsMaster::where('doc_id', $fileLoc)->first();
            $file = env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'];

            $fileTempName = 'DEN-OCR-' . Str::random(50);
            $to = 'C:/Windows/temp/' . $fileTempName . '.txt';
            $process = new Process(['pdftotext', $file, $to]);
            $process->run();

            $file = File::get($to);

            logger(json_encode($value));

            $cek = $this->checkerFoo([], $value, $file);

            if (global_parse_condition($cek)) {
                $hasilAll[] = $cek;
            }
        }

        return $hasilAll;
    }

    public function checkerFoo($currArr, $val, $file)
    {
        if ($val['logical_type'] === 'condition') {
            if ($val['logical_connection']) {
                $currArr[] = $val['logical_connection'] === 'AND' ? '&&' : '||';
            }

            if ($val['logical_cond'] === 'after_word') {
                $splitByWord = explode($val['logical_val_opt'], $file);
                if (isset($splitByWord[1])) {
                    $getAfterWordExactly = explode(' ', $splitByWord[1]);
                    if ($val['logical_param'] === 'contain') {
                        if (strpos($splitByWord[1], $val['logical_val'])) {
                            $currArr[] = 'true';
                        } else {
                            $currArr[] = 'false';
                        }
                    } elseif ($val['logical_param'] === 'not_contain') {
                        if (strpos($splitByWord[1], $val['logical_val'])) {
                            $currArr[] = 'false';
                        } else {
                            $currArr[] = 'true';
                        }
                    } elseif ($val['logical_cond'] === '===') {
                        if ($getAfterWordExactly[0] === $val['logical_val']) {
                            $currArr[] = 'true';
                        } else {
                            $currArr[] = 'false';
                        }
                    } elseif ($val['logical_cond'] === '>') {
                        if ($getAfterWordExactly[0] > $val['logical_val']) {
                            $currArr[] = 'true';
                        } else {
                            $currArr[] = 'false';
                        }
                    } elseif ($val['logical_cond'] === '>=') {
                        if ($getAfterWordExactly[0] >= $val['logical_val']) {
                            $currArr[] = 'true';
                        } else {
                            $currArr[] = 'false';
                        }
                    } elseif ($val['logical_cond'] === '<') {
                        if ($getAfterWordExactly[0] < $val['logical_val']) {
                            $currArr[] = 'true';
                        } else {
                            $currArr[] = 'false';
                        }
                    } elseif ($val['logical_cond'] === '<=') {
                        if ($getAfterWordExactly[0] <= $val['logical_val']) {
                            $currArr[] = 'true';
                        } else {
                            $currArr[] = 'false';
                        }
                    } elseif ($val['logical_cond'] === '<>') {
                        if ($getAfterWordExactly[0] <> $val['logical_val']) {
                            $currArr[] = 'false';
                        } else {
                            $currArr[] = 'true';
                        }
                    }
                } else {
                    $currArr[] = 'false';
                }

                return $this->checkerFoo($currArr, $val['all_child_list'], $file);
            } else {
                $getAfterWordExactly = explode(' ', $file);
                if ($val['logical_param'] === 'contain') {
                    if (strpos($file, $val['logical_val'])) {
                        $currArr[] = 'true';
                    } else {
                        $currArr[] = 'false';
                    }
                } elseif ($val['logical_param'] === 'not_contain') {
                    if (strpos($splitByWord[1], $val['logical_val'])) {
                        $currArr[] = 'false';
                    } else {
                        $currArr[] = 'true';
                    }
                } elseif ($val['logical_cond'] === '===') {
                    if ($getAfterWordExactly[0] === $val['logical_val']) {
                        $currArr[] = 'true';
                    } else {
                        $currArr[] = 'false';
                    }
                } elseif ($val['logical_cond'] === '>') {
                    if ($getAfterWordExactly[0] > $val['logical_val']) {
                        $currArr[] = 'true';
                    } else {
                        $currArr[] = 'false';
                    }
                } elseif ($val['logical_cond'] === '>=') {
                    if ($getAfterWordExactly[0] >= $val['logical_val']) {
                        $currArr[] = 'true';
                    } else {
                        $currArr[] = 'false';
                    }
                } elseif ($val['logical_cond'] === '<') {
                    if ($getAfterWordExactly[0] < $val['logical_val']) {
                        $currArr[] = 'true';
                    } else {
                        $currArr[] = 'false';
                    }
                } elseif ($val['logical_cond'] === '<=') {
                    if ($getAfterWordExactly[0] <= $val['logical_val']) {
                        $currArr[] = 'true';
                    } else {
                        $currArr[] = 'false';
                    }
                } elseif ($val['logical_cond'] === '<>') {
                    if ($getAfterWordExactly[0] <> $val['logical_val']) {
                        $currArr[] = 'false';
                    } else {
                        $currArr[] = 'true';
                    }
                }

                return $this->checkerFoo($currArr, $val['all_child_list'], $file);
            }
        } else {
            $currArr[] = '===';
            $currArr[] = $val['logical_val'] === 'selected_notif_only' ? 'true' : 'false';

            return implode(' ', $currArr);
        }
    }

    public function getDocByApprover($user, $stat = '0', $date = '')
    {
        $selected = [
            'apprv_hist_user',
            'apprv_hist_doc'
        ];

        $cekHist = ApprovalHist::select($selected)
            ->where('apprv_hist_user', $user)
            ->with('notificationFrom');

        if ($stat !== '0') {
            if ($stat === 'full') {
                return $cekHist->with(['doc' => function ($q) {
                    $q
                        ->where('doc_stat_flag', '2')
                        ->orWhere('doc_stat_flag', '3')
                        ->with('users');
                }])
                    ->whereHas('doc', function ($q) {
                        $q
                            ->where('doc_stat_flag', '2')
                            ->orWhere('doc_stat_flag', '3')
                            ->with('users');
                    })
                    ->groupBy($selected)
                    ->get();
            } else {
                if ($stat === 'partial') {
                    return $cekHist->with(['doc' => function ($q) {
                        $q
                            ->where('doc_stat_flag', '1')
                            ->with('users');
                    }])
                        ->whereHas('doc', function ($q) {
                            $q
                                ->where('doc_stat_flag', '1')
                                ->with('users');
                        })->where('apprv_hist_status', '1')
                        ->groupBy($selected)
                        ->get();
                }

                return $cekHist->with('doc.users')->where('apprv_hist_status', '0')
                    ->groupBy($selected)
                    ->get();
            }
        }

        if ($date !== '') {
            return $cekHist->with(['doc' => function ($q) use ($date) {
                $q
                    ->whereBetween('created_at', [$date . ' 00:00:00', $date . ' 23:59:59'])
                    ->with('users');
            }])
                ->whereHas('doc', function ($q) use ($date) {
                    $q
                        ->whereBetween('created_at', [$date . ' 00:00:00', $date . ' 23:59:59'])
                        ->with('users');
                })
                ->groupBy($selected)
                ->get();
        }

        return $cekHist
            ->with('doc.users')
            ->groupBy($selected)
            ->get();
    }
}
