<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Form\FormMaster;
use App\Models\HRMS\Core\Form\FormDet;
use App\Models\HRMS\Core\Form\FormContentMapping;
use App\Models\HRMS\Core\Form\FormAnswersDet;
use App\Models\HRMS\Core\Form\FormLogicsMstr;
use App\Models\HRMS\Core\Form\FormPageMapping;
use App\Models\HRMS\Core\Form\FormPageMappingDet;
use App\Models\HRMS\Core\Form\FormHist;
use App\Models\HRMS\Auth\UserMaster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function storeForm(Request $req)
    {
        $ceklastid = FormMaster::where('form_id', 'like', '%' . date('ymd') . '%')->latest('created_at')->first();
        if (empty($ceklastid))
            $id = 'FRM-' . date('ymd') . '0001';
        else
            $id = 'FRM-' . date('ymd') . sprintf("%04d", intval(substr($ceklastid->form_id, -4)) + 1);
        foreach ($req->data as $key => $data) {
            foreach ($data['forms'] as $keyforms => $dataforms) {
                FormMaster::create([
                    'form_id' => $id,
                    'form_type' => isset($data['type']) ? json_encode($data['type']) : '',
                    'form_var' => $keyforms,
                    'form_label' => $dataforms,
                    'form_req' => $data['required'],
                    'form_username' => $req->header('username')
                ]);

                if (isset($data['data'])) {
                    foreach ($data['data'] as $keydata => $valuedata) {
                        FormDet::create([
                            'form_mstr_id' => $id,
                            'form_var_id' => $keyforms,
                            'form_option_var' => $keydata,
                            'form_option_label' => $valuedata
                        ]);
                    }
                }
            }
        }

        return 'success';
    }

    public function getFormByID($id = null)
    {
        if (empty($id))
            $sourceData = FormMaster::with('getDetail')->orderBy('form_id')->get();
        else
            $sourceData = FormMaster::where('form_id', $id)->with('getDetail')->orderBy('form_id')->get();

        $hasil = [];
        $count = 0;
        $countdata = 0;
        foreach ($sourceData as $key => $value) {
            if ($key !== 0 && $value['form_id'] !== $sourceData[$key - 1]['form_id']) {
                $count++;
                $countdata = 0;
            }
            $hasil[$count]['id'] = $value['form_id'];
            $hasil[$count]['data'][$countdata++] = $value;
        }

        return $hasil;
    }

    public function storeFormMappingContent(Request $req)
    {
        // return $req->all();
        return $this->storingContent($req, $req->header('username'));
    }

    public function storingContent($req, $username = null, $parent = null, $strCh = '')
    {
        // Check if form edited
        if (isset($req->div_id) && !empty($req->div_id)) {
            $id = $req->div_id;
        } else {
            $ceklastid = FormContentMapping::whereDate('created_at', Carbon::today())->latest('created_at')->first();
            if (empty($ceklastid))
                $id = 'FRM' . $strCh . '-' . date('ymd') . '0001';
            else
                $id = 'FRM' . $strCh . '-' . date('ymd') . sprintf("%04d", intval(substr($ceklastid['div_id'], -4)) + 1);
        }

        // Fetching Rows
        foreach ($req->rows as $key => $value) {
            // Check if form row edited
            if (isset($value['row_id']) && !empty($value['row_id'])) {
                $idRow = $value['row_id'];
            } else {
                $checklastrow = FormContentMapping::where('div_id', $id)->latest('created_at')->first();
                if (empty($checklastrow))
                    $idRow = 'FRM-ROW' . $strCh . '-' . date('ymd') . '0001';
                else
                    $idRow = 'FRM-ROW' . $strCh . '-' . date('ymd') . sprintf("%04d", intval(substr($checklastrow['row_id'], -4)) + 1);
            }

            // Fetching Columns
            foreach ($value['data'] as $keyContent => $valueCol) {
                if (isset($valueCol['col_id'])) {
                    $idCol = $valueCol['col_id'];
                } else {
                    $checklastcol = FormContentMapping::where('div_id', $id)->where('row_id', $idRow)->latest('created_at')->first();
                    if (empty($checklastcol))
                        $idCol = 'FRM-COL' . $strCh . '-' . date('ymd') . '0001';
                    else
                        $idCol = 'FRM-COL' . $strCh . '-' . date('ymd') . sprintf("%04d", intval(substr($checklastcol['col_id'], -4)) + 1);
                }

                if ($valueCol['content']['type'] === 'html') {
                    $content = $valueCol['content']['data'];
                } else {
                    // IF form_id is found or if form edited
                    if (isset($valueCol['content']['data']['form_id'])) {
                        $idForm = $valueCol['content']['data']['form_id'];
                    } else { // IF new form
                        $checklastform = FormMaster::whereDate('created_at', Carbon::today())->latest('created_at')->first();

                        if (empty($checklastform))
                            $idForm = 'FRM-MSTR-' . date('ymd') . '0001';
                        else
                            $idForm = 'FRM-MSTR-' . date('ymd') . sprintf("%04d", intval(substr($checklastform->form_id, -4)) + 1);
                    }

                    FormMaster::where('form_id', $idForm)->delete();

                    FormMaster::updateOrCreate([
                        'form_id' => $idForm,
                    ], [
                        'form_id' => $idForm,
                        'form_type' => json_encode($valueCol['content']['data']['form_type']),
                        'form_var' => $valueCol['content']['data']['form_var'],
                        'form_req' => $valueCol['content']['data']['form_req'],
                        'form_username' => $username,
                        'form_label' => $valueCol['content']['data']['form_label']
                    ]);

                    if (count($valueCol['content']['data']['form_det']) > 0) {
                        FormDet::where('form_mstr_id', $idForm)->delete();
                        foreach ($valueCol['content']['data']['form_det'] as $keyFormDet => $valueFormDet) {
                            FormDet::updateOrCreate([
                                'form_mstr_id' => $idForm,
                                'form_var_id' => $valueFormDet['form_var_id'],
                            ], [
                                'form_mstr_id' => $idForm,
                                'form_var_id' => $valueFormDet['form_var_id'],
                                'form_option_var' => isset($valueFormDet['form_option_var']) ? $valueFormDet['form_option_var'] : $valueFormDet['comp'],
                                'form_option_label' => $valueFormDet['form_option_label']
                            ]);
                        }
                    }

                    if (count($valueCol['content']['data']['form_answers_det']) > 0) {
                        if (!empty($idForm)) {
                            FormAnswersDet::where('form_id', $idForm)->delete();
                        }
                        foreach ($valueCol['content']['data']['form_answers_det'] as $keyAns => $valueAns) {
                            FormAnswersDet::create([
                                'form_id' => $idForm,
                                'ans_key' => $valueAns['ans_key'],
                                'ans_val' => $valueAns['ans_val'],
                                'ans_remark' => $valueAns['ans_remark'],
                                'ans_creator' => $username
                            ]);
                        }
                    }

                    $content = $idForm;
                }

                FormContentMapping::where('div_id', $id)
                    ->where('row_id', $idRow)
                    ->where('col_id', $idCol)
                    ->delete();

                FormContentMapping::updateOrCreate([
                    'div_id' => $id,
                    'row_id' => $idRow,
                    'col_id' => $idCol
                ], [
                    'div_id' => $id,
                    'form_name' => $req->title,
                    'row_id' => $idRow,
                    'col_id' => $idCol,
                    'div_content' => $content,
                    'div_type' => isset($valueCol['multipleInput']) && $valueCol['multipleInput'] === true ? 'MULTIPLE' : 'SINGLE',
                    'div_username' => $username,
                    'page_id' => $value['pages'],
                    'content_parent' => !empty($parent) ? $parent : null
                ]);

                if (isset($valueCol['child']) && count($valueCol['child']) > 0) {
                    $this->storingContent(
                        (object)[
                            'rows' => $valueCol['child'],
                            'div_id' => $req->div_id,
                            'title' => $req->title
                        ],
                        $username,
                        $idCol,
                        '-C'
                    );
                }
            }
        }

        return 'success';
    }

    public function getFormMapping($id = null, $data = null, $usernameHist = null)
    {
        // divRelation is Form Master

        if ($data === null) {
            if (empty($id))
                $sourceData = FormContentMapping::whereNull('content_parent')
                    ->with('child.formLogics.formChild')
                    ->with('child.divRelation.formDet')
                    ->with('child.divRelation.formLogics')
                    ->with('child.divRelation.formAnswersDet')
                    ->with('formLogics.formChild')
                    ->with('divRelation.formDet')
                    ->with('divRelation.formLogics')
                    ->with('divRelation.formAnswersDet')
                    ->with('divRelation.formHist')
                    ->with('pageMapping.detail')
                    ->orderBy('div_id')
                    ->orderBy('row_id')
                    ->orderBy('col_id')
                    ->get();
            else
                $sourceData = FormContentMapping::whereNull('content_parent')
                    ->with('child.formLogics.formChild')
                    ->with('child.divRelation.formDet')
                    ->with('child.divRelation.formLogics')
                    ->with('child.divRelation.formAnswersDet')
                    ->with('formLogics.formChild')
                    ->with('divRelation.formDet')
                    ->with('divRelation.formLogics')
                    ->with('divRelation.formAnswersDet')
                    ->with('divRelation.formHist')
                    ->with('pageMapping.detail')
                    ->where('div_id', $id)
                    ->orderBy('row_id')
                    ->orderBy('col_id')
                    ->get();
        } else {
            $sourceData = $data;
        }
        // return $sourceData;

        $countRow = $countArr = $countCol = 0;
        $hasil = [];
        foreach ($sourceData as $key => $value) {
            if ($key !== 0 && $value['col_id'] !== $sourceData[$key - 1]['col_id']) {
                $countCol++;
            }

            if ($key !== 0 && $value['row_id'] !== $sourceData[$key - 1]['row_id']) {
                $countRow++;
                $countCol = 0;
            }

            if ($key !== 0 && $value['div_id'] !== $sourceData[$key - 1]['div_id']) {
                $countArr++;
                $countRow = 0;
            }

            if ($data === null) {
                $hasil[$countArr]['div_id'] = $value['div_id'];
                $hasil[$countArr]['title'] = $value['form_name'];
                $hasil[$countArr]['logics'] = $value['formLogics'];
                $hasil[$countArr]['page_mapping'] = $value['pageMapping'];

                $hasil[$countArr]['rows'][$countRow]['row_id'] = $value['row_id'];
                $hasil[$countArr]['rows'][$countRow]['rows'] = $countRow + 1;
                $hasil[$countArr]['rows'][$countRow]['pages'] = $value['page_id'];
                $hasil[$countArr]['rows'][$countRow]['samePage'] = $countRow > 0 && isset($hasil[$countArr]['rows'][$countRow - 1]) && $value['page_id'] === $hasil[$countArr]['rows'][$countRow - 1]['pages'] ? true : false;
                $hasil[$countArr]['rows'][$countRow]['data'][$countCol]['cols'] = $countCol + 1;
                $hasil[$countArr]['rows'][$countRow]['data'][$countCol]['col_id'] = $value['col_id'];
                $hasil[$countArr]['rows'][$countRow]['data'][$countCol]['multipleInput'] = $value['div_type'] === 'SINGLE' ? false : true;
                $hasil[$countArr]['rows'][$countRow]['data'][$countCol]['content'] = isset($value->divRelation)
                    ? [
                        'type' => 'form',
                        'data' => [
                            "form_id" => $value->divRelation['form_id'],
                            "form_type" => json_decode($value->divRelation['form_type']),
                            "form_var" => $value->divRelation['form_var'],
                            "form_label" => $value->divRelation['form_label'],
                            "form_req" => $value->divRelation['form_req'],
                            "form_det" => $value->divRelation->formDet,
                            "form_hist" => empty($usernameHist)
                                ? []
                                : array_values(array_filter($value->divRelation->formHist->toArray(), function ($h) use ($usernameHist) {
                                    return $h['form_hist_username'] === $usernameHist;
                                })),
                            "form_answers_det" => $value->divRelation->formAnswersDet,
                            "form_logics" => $value->divRelation->formLogics,
                            "comp" => json_decode($value->divRelation['form_type'])
                        ]
                    ]
                    : [
                        'type' => 'html',
                        'data' => $value['div_content']
                    ];

                $hasil[$countArr]['rows'][$countRow]['data'][$countCol]['child'] = $this->getFormMapping(null, $value->formMappingChild);
            } else {
                $hasil[$countRow]['row_id'] = $value['row_id'];
                $hasil[$countRow]['rows'] = $countRow + 1;
                $hasil[$countRow]['pages'] = $value['page_id'];
                $hasil[$countRow]['samePage'] = $countRow > 0 && isset($hasil[$countArr]['rows'][$countRow - 1]) && $value['page_id'] === $hasil[$countArr]['rows'][$countRow - 1]['pages'] ? true : false;

                $hasil[$countRow]['child'] = $this->getFormMapping(null, $value->formMappingChild);

                $hasil[$countRow]['data'][$countCol]['cols'] = $countCol + 1;
                $hasil[$countRow]['data'][$countCol]['col_id'] = $value['col_id'];
                $hasil[$countRow]['data'][$countCol]['multipleInput'] = $value['div_type'] === 'SINGLE' ? false : true;
                $hasil[$countRow]['data'][$countCol]['content'] = isset($value->divRelation)
                    ? [
                        'type' => 'form',
                        'data' => [
                            "form_id" => $value->divRelation['form_id'],
                            "form_type" => json_decode($value->divRelation['form_type']),
                            "form_var" => $value->divRelation['form_var'],
                            "form_label" => $value->divRelation['form_label'],
                            "form_req" => $value->divRelation['form_req'],
                            "form_det" => $value->divRelation->formDet,
                            "form_hist" => empty($usernameHist)
                                ? []
                                : array_values(array_filter($value->divRelation->formHist->toArray(), function ($h) use ($usernameHist) {
                                    return $h['form_hist_username'] === $usernameHist;
                                })),
                            "form_answers_det" => $value->divRelation->formAnswersDet,
                            "form_logics" => $value->divRelation->formLogics,
                            "comp" => json_decode($value->divRelation['form_type'])
                        ]
                    ]
                    : [
                        'type' => 'html',
                        'data' => $value['div_content']
                    ];
            }
        }

        return $hasil;
    }

    public function storeLogics(Request $req)
    {
        return $this->recurStoreLogics($req->data);
    }

    public function recurStoreLogics($arr, $hasil = [], $parent = '0')
    {
        foreach ($arr as $key => $valDet) {
            if (isset($valDet['form_logics_id']) || !empty($valDet['form_logics_id'])) {
                $id = $valDet['form_logics_id'];
            } else {
                $ceklastid = FormLogicsMstr::whereDate('created_at', Carbon::today())->latest('created_at')->first();
                if (empty($ceklastid))
                    $id = 'LOGICS-' . date('ymd') . '0001';
                else
                    $id = 'LOGICS-' . date('ymd') . sprintf("%04d", intval(substr($ceklastid['form_logics_id'], -4)) + 1);
            }

            $insertnya = FormLogicsMstr::create([
                'content_id' => isset($valDet['content_id']) || !empty($valDet['content_id']) ? $valDet['content_id'] : '',
                'form_logics_id' => $id,
                'form_trigger' => $valDet['form_trigger'],
                'form_logics' => $valDet['form_logics'],
                'form_content_id' => $valDet['form_content_id'],
                'form_cond' => $valDet['form_cond'],
                'form_val' => is_array($valDet['form_val']) ? json_encode($valDet['form_val']) : $valDet['form_val'],
                'form_actions' => isset($valDet['form_actions']) || !empty($valDet['form_actions']) ? $valDet['form_actions'] : '',
                'form_order' => $key + 1,
                'form_parent_logics_id' => $parent,
            ]);

            $ret = array_merge($hasil, [$insertnya]);

            if (isset($valDet['form_child'])) {
                $hasil[] = $this->recurStoreLogics($valDet['form_child'], $ret, $id);
            } else {
                $hasil[] = $ret;
            }
        }

        return $hasil;
    }

    public function storeFormMapping(Request $req)
    {
        $randToken = Str::random(50);
        $master = FormPageMapping::updateOrCreate([
            'content_id' => $req->content_id
        ], [
            'content_id' => $req->content_id,
            'menu_id' => $req->menu_id,
            'publish_flag' => $req->publish_flag,
            'publish_token' => $randToken,
            'active_start' => $req->active_start,
            'active_end' => $req->active_end,
            'revised_answer' => $req->revised_answer,
            'username' => $req->header('username'),
        ]);
        
        FormPageMappingDet::where('mapping_id', $master->id)->delete();
        if (count($req->detail['domain']) > 0) {
            foreach ($req->detail['domain'] as $key => $value) {
                FormPageMappingDet::create([
                    'mapping_id' => $master->id,
                    'domain_id' => $value
                ]);
            }
        }
        
        if (count($req->detail['div']) > 0) {
            foreach ($req->detail['div'] as $key => $value) {
                FormPageMappingDet::create([
                    'mapping_id' => $master->id,
                    'division_id' => $value
                ]);
            }
        }

        
        if (count($req->detail['user']) > 0) {
            foreach ($req->detail['user'] as $key => $value) {
                FormPageMappingDet::create([
                    'mapping_id' => $master->id,
                    'username' => $value['username']
                ]);
            }
        }

        return $master;
    }

    public function getFormDataByToken($token, $ansid = '')
    {
        # code...contentDetail
        $getID = FormPageMapping::with('contentDetail')->with('detail')->where('publish_token', $token)->first();
        if (!empty($getID)) {
            return array_merge(['mapping' => $getID], empty($ansid) ? $this->getFormMapping($getID->content_id)[0] : $this->getFormMapping($getID->content_id, null, $ansid)[0]);
        } else {
            return false;
        }
    }

    public function getAllForm($username, $met = 'info')
    {
        $unameDetail = UserMaster::with('occ.division')->where('username', $username)->first();
        $getID = FormPageMapping::with('contentDetail')
            ->with('hist')
            ->with(['detail' => function ($q) use ($unameDetail) {
                $q->where('username', $unameDetail->username);
                if (isset($unameDetail->occ->division)) {
                    $q->orWhere('division_id', $unameDetail->occ->division->id);
                    $q->orWhere('domain_id', $unameDetail->occ->domain_id);
                }
            }])
            ->whereHas('detail', function ($q) use ($unameDetail) {
                $q->where('username', $unameDetail->username);
                if (isset($unameDetail->occ->division)) {
                    $q->orWhere('division_id', $unameDetail->occ->division->id);
                    $q->orWhere('domain_id', $unameDetail->occ->domain_id);
                }
            })
            ->where('active_flag', 1)
            ->get()
            ->toArray();
            
        if ($met === 'info') {
            $hasil = [];
            foreach ($getID as $key => $val) {
                if (!empty($val['active_end'])) {
                    if (date('Y-m-d H:i:s') <= $val['active_end']) {
                        $hasil[] = $val;
                    }
                } else {
                    $hasil[] = $val;
                }
            }
    
            return $hasil;
        } else {
            return $getID;
        }
    }

    public function storeFormHist(Request $req)
    {
        $cekHasil = [];
        if ($req->has('creator')) {
            $username = $req->creator;
        } else {
            $username = $req->header('username') !== 'undefined' ? $req->header('username') : Str::random(50);
        }
        FormHist::where('form_hist_username', $username)->delete();
        foreach ($req->data as $key => $value) {
            foreach ($value['value'] as $keyDet => $valueDet) {
                $cekHasil[] = FormHist::updateOrCreate([
                    'form_id' => $value['formid'],
                    'form_hist_id' => $valueDet['id'],
                    'form_hist_username' => $username,
                ], [
                    'form_hist_id' => $valueDet['id'],
                    'form_id' => $value['formid'],
                    'form_hist_value' => json_encode($valueDet['val']),
                    'form_hist_username' => $username,
                    'publish_token' => $req->public_api,
                ]);
            }
        }

        return $cekHasil;
    }

    public function getTrainingForm()
    {
        $getID = FormPageMapping::with(['contentDetail.divRelation.formAnswersDet', 'contentDetail.divRelation.formHist'])
            ->with('hist')
            ->with(['detail.users'])
            ->whereHas('detail')
            ->where('publish_flag', 'prv_form')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
        
        return $getID;
    }

    public function tester()
    {
        function g($str)
        {
            $i = 0;
            $new = '';

            while ($i < strlen($str) - 1) {
                $new .= $new . (string)$i;

                $i++;
            }

            return $new;
        }

        function f($str)
        {
            if (strlen($str) === 0) {
                return "";
            } elseif (strlen($str) === 1) {
                return $str;
            } else {
                return f(g($str)) . $str[0];
            }
        }

        function h($n, $str)
        {
            while ($n != 1) {
                if ($n % 2 === 0) {
                    $n = $n / 2;
                } else {
                    $n = 3 * $n + 1;
                }

                $str = f($str);
            }

            return $str;
        }

        function pow($x, $y)
        {
            if ($y === 0) {
                return 1;
            } else {
                return $x * pow($x, $y - 1);
            }
        }

        return h(2, "fruits");
    }
}
