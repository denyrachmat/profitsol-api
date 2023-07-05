<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use App\Models\PORTAL\PortalNotif;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\DB;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormShareDet;
use App\Traits\TOS\TrainingTraits;
use App\Traits\CMS\FormsTraits;

class NotifController extends BaseController
{
    use TrainingTraits, FormsTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = PortalNotif::where('pnm_to_users', $request->header('username'))
            ->where(DB::raw("(
                CASE WHEN pnm_end_date IS NULL OR pnm_end_date = '1900-01-01 00:00:00'
                    THEN 1
                    ELSE CASE WHEN GETDATE() <= pnm_end_date
                        THEN 1
                        ELSE 0
                    END
                END
            )"), 1)
            ->with('shared.forms.formMaster')
            ->get()
            ->toArray();

        $hasil = [];
        foreach ($data as $key => $value) {
            // $hasil[] = $value->shared->forms->id;
            if (isset($value['shared'])) {
                if ($value['shared']['forms']['cfmt_quiz_flag'] == 1) {
                    $cekJawaban = FormAnswerUserDet::where('cfm_id', $value['shared']['forms']['id'])->get()->toArray();
                    $cekListHasil = $this->getTrainingList($request->header('username'), $value['shared']['forms']['id'])[0];
                    
                    $hasil[] = array_merge($value, ['answers' => $cekJawaban, 'listHasil' => $cekListHasil]);
                } else {
                    $hasil[] = $value;
                }
            }
        }

        return $this->handleResponse($hasil, 'Data Found !');
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
    public function destroy(Request $req, $id)
    {
        $hasil = PortalNotif::where('id', $id)->first();
        $deleteShare = FormShareDet::where('cfsd_gen_link', $hasil->pnm_hash_id_location)->where('p_u_username', $req->header('username'))->delete();
        $deleteNotif = PortalNotif::where('id', $id)->delete();
        return $this->handleResponse([
            'dataNotif' => $hasil,
            'delete_share' => $deleteShare,
            'delete_notif' => $deleteNotif
        ], 'Data Deleted !');
    }
}
