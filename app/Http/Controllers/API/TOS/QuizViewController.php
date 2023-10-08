<?php

namespace App\Http\Controllers\API\TOS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\CMS\FormShareDet;
use App\Models\PORTAL\PortalNotif;
use App\Traits\CMS\FormsTraits;
use App\Traits\TOS\TrainingTraits;
class QuizViewController extends BaseController
{
    use FormsTraits, TrainingTraits;
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        // getAnswersComparation
        $cekID = PortalNotif::where('pnm_to_users', $request->header('username'))
            ->with([
                'shared.forms' => function ($f) {
                    $f->with(['formMaster' => function ($f2) {
                        $f2->where('cfm_parent_id', 0);
                        $f2->with('formDetail.formAnswer');
                        $f2->with('allChildrenContent');
                    }]);

                    $f->with('quizSetup');
            }])
            ->where('pnm_hash_id_location', $id)
            ->first();
        
        if(empty($cekID)) {
            return $this->handleError('Data Not Found !', $cekID);
        }

        // return $cekID->shared->forms->formMaster;
        // return [$cekID->shared->forms];

        $hasilHeader = $this->getHeaderAllForms([$cekID->shared->forms->toArray()]);

        // return $hasilHeader;
        // $hasil = [];
        // foreach ($hasilHeader as $key => $value) {
        //     $hasil[] = [
        //         'label' => $value['title'] . ' (' . count($value['forms']) . ' Rows Content)',
        //         'value' => $value
        //     ];
        // }

        // return $this->handleResponse($this->getAnswersComparationWithNotif($id, $request->header('username'))[0], 'Data Found !');
        return $this->handleResponse($hasilHeader[0], 'Data Found !');
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
