<?php

namespace App\Http\Controllers\API\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\AMS\ApprovalSettingsRequest;
use App\Models\AMS\ApprovalSetDetail;
use App\Models\AMS\ApprovalTokenDetail;
use App\Models\AMS\ApprovalAttachSet;
use Illuminate\Support\Str;

class ApprovalSettingsController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(ApprovalSettingsRequest $request)
    {
        $createMaster = ApprovalSetDetail::updateOrCreate([
            'amsm_id' => $request->id,
        ], [
            'p_u_username' => $request->header('username'),
            'amsm_id' => $request->id,
            'amssd_quotkn' => $request->amssd_quotkn,
            'amssd_isemail' => $request->amssd_isemail,
            'amssd_iswa' => $request->amssd_iswa,
            'amssd_issms' => $request->amssd_issms,
            'amssd_is_docsign' => $request->amssd_is_docsign,
            'amssd_unread_autonotif' => $request->amssd_unread_autonotif,
            'amssd_unread_chktime' => $request->amssd_unread_chktime,
            'amssd_autorun' => $request->amssd_autorun,
            'amssd_autorun_chktime' => $request->amssd_autorun_chktime,
            'amssd_content' => $request->amssd_content,
            'amssd_attachment' => $request->amssd_attachment
        ]);

        if ($request->has('amssd_quotkn') && $request->amssd_quotkn > 0) {
            $cekTokenTotNow = ApprovalTokenDetail::where('amsm_id', $request->id)->count();

            if ($cekTokenTotNow === 0) {
                ApprovalTokenDetail::where('amsm_id', $request->id)->forceDelete();
            }

            for ($i = 0; $i < ($request->amssd_quotkn - $cekTokenTotNow); $i++) {
                ApprovalTokenDetail::create([
                    'p_u_username' => $request->header('username'),
                    'amsm_id' => $request->id,
                    'amstd_token' => Str::random(50)
                ]);
            }
        }

        if ($request->has('attch') && count($request->attch) > 0) {
            foreach ($request->attch as $key => $valueAttch) {
                ApprovalAttachSet::updateOrCreate([
                    'aasd_id' => $request->id,
                    'aats_name' => $valueAttch['aats_name'],
                ], [
                    'aasd_id' => $request->id,
                    'aats_name' => $valueAttch['aats_name'],
                    'aats_method' => $valueAttch['aats_method'],
                    'aats_host' => $valueAttch['aats_host'],
                    'aats_header' => $valueAttch['aats_header'],
                    'aats_param' => $valueAttch['aats_param'],
                ]);
            }
        }

        return $this->handleResponse($createMaster, 'Approval setting, setted up !');
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
