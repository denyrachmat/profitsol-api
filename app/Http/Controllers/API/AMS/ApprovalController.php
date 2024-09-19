<?php

namespace App\Http\Controllers\API\AMS;

// use App\Http\Controllers\Controller;
use App\Http\Controllers\API\PORTAL\BaseController;
use DB;
use Illuminate\Http\Request;

use App\Http\Requests\AMS\ApprovalCreateRequest;

use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalMapDetail;

class ApprovalController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ApprovalMaster::with(['det.userDet' => function($f) {
            $f->select('portal_users_det.*', DB::raw("CONCAT(pud_first_name, ' ', pud_last_name) AS fullname"));
        }])->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ApprovalCreateRequest $request)
    {
        $cekLastApprv = ApprovalMaster::where('created_at', date('Y-m-d'))->orderBy('created_at', 'desc')->first();

        $id = '';
        if (empty($cekLastApprv)) {
            $id = "APV{date('Ymd')}0001";
        } else {
            $getLastNumAdd = substr($cekLastApprv->ams_idapv, -4);
            $id = "APV".date('ymd')."{sprintf('%04d', ((int)$getLastNumAdd + 1))}";
        }

        $createMaster = ApprovalMaster::create([
            'p_u_username' => $request->header('username'),
            'ams_idapv' => $id,
            'ams_title' => $request->title,
        ]);

        foreach ($request->det as $key => $value) {
            ApprovalMapDetail::create([
                'p_u_username' => $request->header('username'),
                'amsm_id' => $createMaster->id,
                'amsmd_username' => $value['amsmd_username'],
                'amsmd_order' => $value['amsmd_order'],
                'amsmd_reqaprv' => $value['amsmd_reqaprv'],
            ]);
        }

        return $this->handleResponse($createMaster, 'Approval submited !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $createMaster = ApprovalMaster::where('id', $id)->update([
            'p_u_username' => $request->header('username'),
            'ams_idapv' => $request->ams_idapv,
            'ams_title' => $request->title,
        ]);

        ApprovalMapDetail::where('amsm_id', $id)->delete();
        foreach ($request->det as $key => $value) {
            ApprovalMapDetail::create([
                'p_u_username' => $request->header('username'),
                'amsm_id' => $id,
                'amsmd_username' => $value['amsmd_username'],
                'amsmd_order' => $value['amsmd_order'],
                'amsmd_reqaprv' => $value['amsmd_reqaprv'],
            ]);
        }

        return $this->handleResponse($createMaster, 'Approval has been updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delete = ApprovalMaster::where('id', $id)->delete();
        return $this->handleResponse($delete, 'Approval deleted !');
    }
}
