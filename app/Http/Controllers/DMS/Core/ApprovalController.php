<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\ApprovalHist;
use Illuminate\Support\Str;
use App\Models\DMS\Core\DocsMaster;
use App\Models\DMS\Core\ContentCreator;
use App\Models\DMS\Core\ApprovalNotification;

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
                $ceklast = ContentCreator::where('content_creator_id', 'like', 'CRTR' . date('ymd') . '%')->orderBy('content_creator_id', 'desc')->first();
                if (empty($ceklast)) {
                    $nextid = 'CRTR' . date('ymd') . '0001';
                } else {
                    $nextid = 'CRTR' . date('ymd') . sprintf('%04d', (int) substr($nextid['content_creator_id'], -3) + 1);
                }

                if (count($req->formnya) > 0) {
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

            foreach ($req->doc_id as $key => $value) {
                $cekhist = ApprovalHist::where('apprv_hist_doc', $value['doc_id'])->where('apprv_parent', $req->parent_apprv)->first();
                if (!empty($cekhist)) {
                    $doc = DocsMaster::where('doc_id', $value['doc_id'])->first();
                    $hasil['errors'][] = ['Document ' . $doc['doc_real_name'] . ' already sent to the next approver!'];
                } else {
                    $idhistory = Str::random(50);
                    $hasilinsert = ApprovalHist::create([
                        'id' => $idhistory,
                        'apprv_parent' => $req->parent_apprv,
                        'apprv_hist_user' => $req->user_apprv,
                        'apprv_hist_doc' => $value['doc_id'],
                        'apprv_hist_comment'  => $req->comment,
                        'apprv_hist_status'  => $req->status,
                        'content_creator_id' => $nextid,
                        'id_approval' => $req->master_apprv,
                    ])->load('doc');

                    if ($req->parent_apprv == '0') {
                        $hasilsuccess[$cekapprover[0]['apprv_approver']]['APPROVAL_REQUEST'][] = $hasilinsert;
                        $hasilsuccess[$cekapprover[0]['apprv_approver']]['APPROVAL_DETAIL'] = $this->outstandingApproval($cekapprover[0]['apprv_approver'], $value['doc_author'], 1)[0];

                        ApprovalNotification::create([
                            'apprv_user_from' => $req->user_apprv,
                            'apprv_user_to' => $cekapprover[0]['apprv_approver'],
                            'apprv_hist_from_id' => $nextid == "" ? $idhistory : $nextid,
                        ]);
                    } else {
                        $ceklagi = ApprovalMaster::where('apprv_author', $value['doc_author'])
                            // ->where('apprv_approver', $req->user_apprv)
                            ->where('apprv_id', $nextid)
                            ->where('apprv_level', $req->level + 1)
                            ->get()
                            ->toArray();

                        foreach ($ceklagi as $key2 => $value2) {
                            $hasilsuccess[$key]['next_approver'][] = $this->outstandingApproval($ceklagi[$key + 1]['apprv_approver'], $value['doc_author'], $value2['apprv_level'], $nextid);

                            ApprovalNotification::create([
                                'apprv_user_from' => $req->user_apprv,
                                'apprv_user_to' => $value2['apprv_approver'],
                                'apprv_hist_from_id' => $nextid == "" ? $idhistory : $nextid,
                            ]);
                            // if (isset($ceklagi[$key + 1])) {
                            //     // if ($value2['apprv_level'] == $ceklagi[$key + 1]['apprv_level']) {
                            //     //     $hasilsuccess[$key]['next_approver'][] = $this->outstandingApproval($ceklagi[$key + 1]['apprv_approver'], $value['doc_author'], $value2['apprv_level'])[0];
                            //     // }
                            //     $hasilsuccess[$key]['next_approver'][] = $this->outstandingApproval($ceklagi[$key + 1]['apprv_approver'], $value['doc_author'], $value2['apprv_level'], $nextid);
                            // }
                        }
                    }
                }
            }

            if (count($hasil) > 0) {
                return response($hasil, 422);
            } else {
                return $hasilsuccess;
            }
        } else {
            return response([
                'errors' => [
                    'approver' => ['Please setup the approver of this user first !!']
                ]
            ], 422);
        }
    }

    public function outstandingApproval($apprv_user, $author, $level = null, $content_id = null)
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
            'content_creator_id'
        ];

        $cekmaster = ApprovalMaster::select($selectmstr)
            // ->where('apprv_approver', $apprv_user)
            ->where('apprv_author', $author)
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

    public function outstandingApprovalByApprover($approver, $author = null)
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
            'id_approval'
        ];

        $ceknotif = ApprovalNotification::with(['histFrom' => function ($q) use ($selecthist) {
            $q->select($selecthist);
            $q->with('doc.users');
            $q->with('contentVariable');
            $q->with('contentDefine.contentMstr');
            $q->where('apprv_hist_status', '1');
        }])
        ->with(['histTo' => function ($q) use ($selecthist) {
            $q->select($selecthist);
            $q->with('doc.users');
            $q->with('contentVariable');
            $q->with('contentDefine.contentMstr');
            $q->where('apprv_hist_status', '1');
        }])
        ->with(['userFrom','userTo'])
        ->where('apprv_user_to', $approver)
        ->orderBy('created_at','desc')
        ->get()
        ->toArray();


        return $ceknotif;
    }

    public function listDocSenttoApprover($user)
    {
        return ApprovalHist::where('apprv_hist_user', $user)
            ->where('apprv_parent', '0')
            ->with('doc.users')
            ->with('allApproverList')
            ->with('getallapprover.user')
            ->get()
            ->toArray();
    }

    public function ApprovalList($user)
    {
        $selectMaster = [
            'apprv_author',
            'apprv_title',
            'apprv_id'
        ];

        $cekMaster = ApprovalMaster::select($selectMaster)
            ->where('apprv_author', $user)
            ->with(['contentDef.contentDet','contentDef.contentMstr'])
            ->with('approvalDocSet.doc')
            ->groupBy($selectMaster)
            ->get()
            ->toArray();
        
        return $cekMaster;
    }

    public function ApprovalSentByContent(Request $req){

    }
}
