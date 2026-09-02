<?php

namespace App\Http\Controllers\API\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\AMS\ApprovalDocSignBox;
use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalMapDetail;
use Illuminate\Http\Request;
use DB;

class ApprovalDocSignController extends BaseController
{
    public function getSignBoxes($amsm_id)
    {
        $boxes = ApprovalDocSignBox::where('amsm_id', $amsm_id)
            ->with('mapdet.userDet')
            ->orderBy('dsbx_page_no')
            ->orderBy('amsmd_id')
            ->get();

        return $this->handleResponse($boxes, 'Signature boxes fetched');
    }

    public function saveSignBoxes(Request $request)
    {
        $request->validate([
            'amsm_id' => 'required|integer',
            'boxes' => 'required|array',
            'boxes.*.amsmd_id' => 'required|integer',
            'boxes.*.dsbx_page_no' => 'required|integer|min:1',
            'boxes.*.dsbx_x' => 'required|numeric',
            'boxes.*.dsbx_y' => 'required|numeric',
            'boxes.*.dsbx_width' => 'nullable|numeric',
            'boxes.*.dsbx_height' => 'nullable|numeric',
            'boxes.*.dsbx_label' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        ApprovalDocSignBox::where('amsm_id', $request->amsm_id)->delete();

        foreach ($request->boxes as $box) {
            ApprovalDocSignBox::create([
                'amsm_id' => $request->amsm_id,
                'amsmd_id' => $box['amsmd_id'],
                'dsbx_page_no' => $box['dsbx_page_no'],
                'dsbx_x' => $box['dsbx_x'],
                'dsbx_y' => $box['dsbx_y'],
                'dsbx_width' => $box['dsbx_width'] ?? 150,
                'dsbx_height' => $box['dsbx_height'] ?? 50,
                'dsbx_label' => $box['dsbx_label'] ?? null,
            ]);
        }

        DB::commit();

        return $this->handleResponse(
            ApprovalDocSignBox::where('amsm_id', $request->amsm_id)->get(),
            'Signature boxes saved successfully'
        );
    }

    public function deleteSignBoxes($amsm_id)
    {
        ApprovalDocSignBox::where('amsm_id', $amsm_id)->delete();

        return $this->handleResponse([], 'All signature boxes deleted');
    }

    public function uploadDocumentForSigning(Request $request)
    {
        $request->validate([
            'amsm_id' => 'required|integer',
            'file' => 'required|file|mimes:pdf',
        ]);

        $approval = ApprovalMaster::with('apprvSet')->find($request->amsm_id);

        if (!$approval || !$approval->apprvSet || !$approval->apprvSet->amssd_is_docsign) {
            return $this->handleError('This approval workflow does not support document signing');
        }

        return $this->handleResponse([
            'message' => 'Document upload endpoint ready. Integrate with your DMS storage.'
        ], 'Upload endpoint');
    }
}
