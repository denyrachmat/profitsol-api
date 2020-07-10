<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Form\FormMaster;
use App\Models\HRMS\Core\Form\FormDet;
use App\Models\HRMS\Core\Form\FormContentMapping;

class FormController extends Controller
{
    public function storeForm(Request $req)
    {
        $ceklastid = FormMaster::where('form_id', 'like', '%'.date('ymd').'%')->latest('created_at')->first();
        if(empty($ceklastid)) 
            $id = 'FRM-'.date('ymd').'0001';
        else
            $id = 'FRM-'.date('ymd').sprintf("%04d", intval(substr($ceklastid->form_id,-4)) + 1);
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

                if(isset($data['data'])) {
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
            if($key !== 0 && $value['form_id'] !== $sourceData[$key - 1]['form_id']){
                $count++;
                $countdata = 0;
            }
            $hasil[$count]['id'] = $value['form_id'];
            $hasil[$count]['data'][$countdata++] = $value;
        }

        return $hasil;
    }

    public function storeFormMappingContent(Request $req) {
        $ceklastid = FormContentMapping::latest('created_at')->first();
        if(empty($ceklastid)) 
            $id = 'FRM-DIV-'.date('ymd').'0001';
        else
            $id = 'FRM-DIV-'.date('ymd').sprintf("%04d", intval(substr($ceklastid->div_id,-4)) + 1);

        foreach ($req->data as $key => $value) {
            foreach ($value as $keyContent => $valueContent) {
                $ceklastcontentid = FormContentMapping::where('div_id', $id)->latest('created_at')->first();
                if(empty($ceklastcontentid)) 
                    $id_content = 'FRM-CNTN-'.date('ymd').'0001';
                else
                    $id_content = 'FRM-CNTN-'.date('ymd').sprintf("%04d", intval(substr($ceklastcontentid->content_id,-4)) + 1);
                    
                FormContentMapping::create([
                    'div_id' => $id,
                    'form_name' => $req->title,
                    'content_id' => $id_content,
                    'div_content' => isset($valueContent['data']['id']) ? $valueContent['data']['id'] : $valueContent['data'],
                    'div_type' => $valueContent['multipleInput'] === true ? 'MULTIPLE' : 'SINGLE',
                    'div_username' => $req->header('username')
                ]);
            }
        }

        return 'success';
    }
}
