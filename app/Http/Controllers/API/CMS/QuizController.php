<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMultiDet;
use Illuminate\Http\Request;
use App\Traits\CMS\FormsTraits;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormMasterTitle;

class QuizController extends Controller
{
    use FormsTraits;
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
        FormAnswerUserDet::where('cfm_id', $request->id)->delete();

        $created = [];
        foreach ($request->ans as $key => $value) {
            $created[] = FormAnswerUserDet::create([
                'p_u_username' => $request->header('username'),
                'cfm_id' => $request->id,
                'cfmd_id' => $request->questId,
                'cfm_val' => is_array($value) ? json_encode($value) : $value,
            ]);
        }

        return response([
            'status' => true,
            'message' => 'Answers successfully submited !',
            'data' => $created
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $data = FormAnswerUserDet::where('p_u_username', $request->header('username'))->where('cfm_id', $id)->get();
        $dataAnswers = FormAnswerDet::where('cfm_id', $id)->get();
        // return $dataAnswers;

        $hasil = [];
        foreach ($dataAnswers as $key => $value) {
            $answers = is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : $value['cfm_val'];
            $answersUser = is_array(json_decode($data[$key]['cfm_val'])) ? json_decode($data[$key]['cfm_val']) : $data[$key]['cfm_val'];

            $getLabel = FormMultiDet::select('cfmd_label')
                ->where('cfm_id', $value->cfmd_id)
                ->whereIn('cfmd_value', is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : [$value['cfm_val']])
                ->pluck('cfmd_label');

            $hasil[$key] = [
                'status' => $answers === $answersUser,
                'users' => $answersUser,
                'ans' => $answers,
                'ans_value' => $getLabel,
                'exp' => $value['cfm_exp']
            ];
        }

        return response(['status' => true, 'data' => $hasil]);
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