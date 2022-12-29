<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMasterTitle;
class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->forms;

        $insertMaster = FormMasterTitle::create([
            'p_u_username' => $request->header('username'),
            'cfmt_title' => $request->title,
            'cfmt_quiz_flag' => $request->isQuiz == true ? 1 : 0,
        ]);

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = $this->storingForms(
                $value,
                'test',
                isset($request->ans) ? $request->ans : [],
                $insertMaster->id,
                0,
                $key
            );
        }

        return $hasil;
    }

    public function storingForms($data, $uname, $keyAnswer = [], $idTitle = '', $parent = 0, $masterKeys = 0, $hasil = [])
    {
        if ($data['type'] === 'row') {
            $content = '';

            $insert = FormMaster::create([
                'p_u_username' => $uname,
                'cfmt_id' => $idTitle,
                'cfm_type' => $data['type'],
                'cfm_seq_name' => $data['seq_name'],
                'cfm_content' => $content,
                'cfm_parent_id' => $parent,
            ]);

            if ($insert) {
                $dataCols = [];
                foreach ($data['content'] as $key => $value) {
                    $dataCols[] = $this->storingForms($value, $uname, $keyAnswer, $idTitle, $insert->id);
                }

                $hasil[] = [
                    'status' => true,
                    'data' => $dataCols
                ];
            } else {
                $hasil[] = [
                    'status' => false,
                    'data' => []
                ];
            }
        } else {
            if (is_array($data['content'])) {
                $content = json_encode($data['content']['component']);
            } else {                
                $content = $data['content'];
            }

            $insert = FormMaster::create([
                'p_u_username' => $uname,
                'cfmt_id' => $idTitle,
                'cfm_type' => $data['type'],
                'cfm_seq_name' => isset($data['seq_name']) ? $data['seq_name'] : '',
                'cfm_content' => $content,
                'cfm_parent_id' => $parent,
            ]);

            if ($insert) {
                $detail_data = [];
                if (isset($data['content']['detail_data']) && count($data['content']['detail_data']) > 0) {
                    foreach ($data['content']['detail_data'] as $key => $valueDet) {
                        $detail_data[] = FormMultiDet::create([
                            'cfm_id' => $insert->id,
                            'cfmd_value' => $valueDet['value'],
                            'cfmd_label' => $valueDet['label'],
                        ]);
                    }
                }

                $detail_data_key_ans = [];
                if (count($keyAnswer) > 0) {
                    foreach ($keyAnswer as $keyAns => $valueAns) {
                        if ($keyAns === $masterKeys) {
                            $getIDDetail = array_values(array_filter($detail_data, function ($f) use ($valueAns) {
                                return $f->cfmd_value == $valueAns;
                            }));

                            $detail_data_key_ans[] = FormAnswerDet::create([
                                'p_u_username' => $uname,
                                'cfm_id' => $insert->id,
                                'cfmd_id' => $getIDDetail[0]->id,
                                'cfm_val' => $valueAns,
                            ]);
                        }
                    }
                }

                $hasil[] = [
                    'status' => true,
                    'data' => [
                        'master' => $insert,
                        'detail_multiple' => $detail_data,
                        'detail_ans' => $detail_data_key_ans
                    ]
                ];
            } else {
                $hasil[] = [
                    'status' => false,
                    'data' => []
                ];
            }
        }

        return $hasil;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if ($id === 'quiz') {
            $data = formMasterTitle::with('formMaster.formDetail.formAnswer')->where('cfmt_quiz_flag', 1)->get();
        } else {
            $data = formMasterTitle::with('formMaster.formDetail.formAnswer')->where('cfmt_quiz_flag', 0)->get();
        }

        return $data;

        $hasil = $this->getHeaderAllForms($data->toArray());

        return response([
            'status' => count($hasil) > 0,
            'data' => $hasil
        ]);        
    }

    public function getHeaderAllForms($data)
    {
        $hasil = [];
        foreach ($data as $key => $value) {
            $answer = [];
            foreach ($value['form_master'] as $key => $valueAns) {
                $answer[] = FormAnswerDet::where('cfm_id', $valueAns['id'])->first()['cfm_val'];
            }

            $hasil[] = [
                'id' => $value['id'],
                'title' => $value['cfmt_title'],
                'isQuiz' => $value['cfmt_quiz_flag'],
                'forms' => $this->convertToFE($value['form_master']),
                'ans' => $answer
            ];
        }
        return $hasil;
    }

    public function convertToFE($data) : Array
    {
        try {
            $hasil = [];
            foreach ($data as $key => $value) {
                $hasilDetail = [];
                foreach ($value['form_detail'] as $keyDet => $valueDet) {
                    $hasilDetail[] = [
                        'col_det_id' => 'opt-'. $valueDet['id'],
                        'col_det_label' => '',
                        'value' => $valueDet['cfmd_value'],
                        'label' => $valueDet['cfmd_label'],
                    ];
                }
    
                $hasil[] = [
                    'type' => $value['cfm_type'],
                    'seq_name' => $value['cfm_seq_name'],
                    'content' => $value['cfm_type'] === 'row' 
                        ? $this->convertToFE($value['cfm_content']) 
                        : (
                            $value['cfm_type'] === 'html' 
                            ? $value['cfm_content']
                            : json_decode($value['cfm_content'])
                        ),
                    'detail_data' => $hasilDetail
                ];
            }
    
            return $hasil;
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data' => $data
            ];
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
