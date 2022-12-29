<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormMultiDet;
use App\Models\CMS\FormAnswerDet;

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

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = $this->storingForms(
                $value,
                'test',
                isset($request->ans) ? $request->ans : [],
                $request->title,
                0,
                $request->isQuiz == true ? 1 : 0,
                $key
            );
        }

        return $hasil;
    }

    public function storingForms($data, $uname, $keyAnswer = [], $title = '', $parent = 0, $isQuiz = 0, $masterKeys = 0, $hasil = [])
    {
        if ($data['type'] === 'row') {
            $content = '';

            $insert = FormMaster::create([
                'p_u_username' => $uname,
                'cfm_type' => $data['type'],
                'cfm_title' => $title,
                'cfm_seq_name' => $data['seq_name'],
                'cfm_content' => $content,
                'cfm_parent_id' => $parent,
                'cfm_quiz_flag' => $isQuiz,
            ]);

            if ($insert) {
                $dataCols = [];
                foreach ($data['content'] as $key => $value) {
                    $dataCols[] = $this->storingForms($value, $uname, $keyAnswer, '', $insert->id, $isQuiz);
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
            $content = json_encode($data['content']['component']);

            $insert = FormMaster::create([
                'p_u_username' => $uname,
                'cfm_type' => $data['type'],
                'cfm_title' => $title,
                'cfm_seq_name' => isset($data['seq_name']) ? $data['seq_name'] : '',
                'cfm_content' => $content,
                'cfm_parent_id' => $parent,
                'cfm_quiz_flag' => $isQuiz,
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
        //
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
