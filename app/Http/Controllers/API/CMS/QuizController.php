<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMultiDet;
use Illuminate\Http\Request;
use App\Traits\CMS\FormsTraits;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;

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

        // return $request;
        FormAnswerUserDet::where('cfm_id', $request->id)
            ->where('p_u_username', $request->header('username'))
            ->delete();
        $created = [];
        foreach ($request->ans as $key => $value) {
            $getID = FormAnswerUserDet::where('cfm_id', $request->id)
                ->where('p_u_username', $request->header('username'))
                ->orderBy('created_at', 'desc')
                ->first();
            if (empty($getID)) {
                $getDeletedID = FormAnswerUserDet::withTrashed()->where('cfm_id', $request->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $nextID = empty($getDeletedID) ? 1 : $getDeletedID['cfaud_batch'] + 1;
            } else {
                $nextID = $getID['cfaud_batch'];
            }

            $created[] = FormAnswerUserDet::create([
                'p_u_username' => $request->header('username'),
                'cfaud_batch' => $nextID,
                'cfm_id' => $request->id,
                'cfmd_id' => $request->questId[$key],
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
    public function show(Request $request, $id, $idDet = '')
    {
        $data = FormAnswerUserDet::where('p_u_username', $request->header('username'))->where('cfm_id', $id)->get()->toArray();
        $dataAnswers = FormAnswerDet::where('cfm_id', $id)->get();
        // return $dataAnswers;

        $hasil = [];
        foreach ($dataAnswers as $key => $value) {
            $answers = is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : $value['cfm_val'];
            $answersUser = isset($data[$key])
                ? (is_array(json_decode($data[$key]['cfm_val'])) ? json_decode($data[$key]['cfm_val']) : $data[$key]['cfm_val'])
                : (is_array(json_decode($value['cfm_val'])) ? [] : "" );

            $getLabelCek = FormMultiDet::select('cfmd_label')
                ->where('cfm_id', $value->cfmd_id)
                ->whereIn('cfmd_value', is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : [$value['cfm_val']]);

            if (!empty($idDet)) {
                $getLabelCek->where('cfmd_id', $idDet);
            }

            $getLabel = $getLabelCek->pluck('cfmd_label');

            $hasil[$key] = [
                'status' => $answers === $answersUser,
                'users' => $answersUser,
                'ans' => $answers,
                'ans_value' => $getLabel,
                'exp' => $value['cfm_exp']
            ];
        }
        
        $getGrade = array_filter($hasil, function($f) {
            return $f['status'];
        });
        
        $totalGrade = round((count($getGrade) / count($dataAnswers)) * 100, 2);
        $cekStatGrade = FormSetupDet::where('cfmt_id', $id)->first();

        return response(['status' => true, 'data' => $hasil, 'grade' => $totalGrade, 'is_pass' => $totalGrade >= $cekStatGrade['cfsd_min_pass']]);
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