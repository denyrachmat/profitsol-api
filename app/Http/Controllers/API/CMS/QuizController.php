<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\CMS\FormsTraits;
use App\Models\CMS\FormAnswerUserDet;

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
                'cfmd_id' => '',
                'cfm_val' => $value,
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

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = $value['cfm_val'];
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