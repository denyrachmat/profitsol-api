<?php

namespace App\Http\Controllers\API\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Traits\AMS\ApprovalActionTraits;
use App\Models\AMS\ApprovalApiKey;
use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalDocSignBox;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApprovalExternalController extends BaseController
{
    use ApprovalActionTraits;

    public function initialize(Request $request)
    {
        $key = ApprovalApiKey::active()
            ->where('ak_key_hash', hash('sha256', $this->extractApiKey($request)))
            ->first();

        if (!$key) {
            return $this->handleError('Invalid or inactive API key', [], 401);
        }

        $request->validate([
            'amsm_id' => 'required|integer',
            'username' => 'required|string',
            'data' => 'required',
            'remarks' => 'nullable|string',
            'file' => 'nullable|file',
            'onApproval' => 'nullable|array',
            'onDone' => 'nullable|array',
        ]);

        $approval = ApprovalMaster::with('apprvSet')->find($request->amsm_id);

        if (!$approval) {
            return $this->handleError('Approval workflow not found');
        }

        if (!$this->keyCanUseApproval($key, $request->amsm_id)) {
            return $this->handleError('This API key is not allowed to use this approval workflow', [], 403);
        }

        // Enforce document upload when "Sign uploaded doc ?" is on
        if (
            $approval->apprvSet &&
            $approval->apprvSet->amssd_is_docsign &&
            $approval->apprvSet->amssd_sign_uploaded_doc
        ) {
            if (!$request->hasFile('file')) {
                return $this->handleError('This approval requires an uploaded document. Please attach the "file" to submit.');
            }
        }

        $getApproval = $this->approveAction(new \App\Http\Requests\AMS\ApprovalRunningApproveActionRequest([
            'username' => $request->username,
            'amsm_id' => $request->amsm_id,
            'stat' => 1,
            'remarks' => $request->remarks ?? 'Approval sent from external app',
            'data' => $request->data,
            'onApproval' => $request->onApproval ?? [],
            'onDone' => $request->onDone ?? [],
        ]));

        $response = json_decode($getApproval->getContent(), true);

        if (($response['status'] ?? false) === true) {
            $signBoxes = ApprovalDocSignBox::where('amsm_id', $request->amsm_id)->get();
            if ($signBoxes->isNotEmpty()) {
                $response['sign_boxes'] = $signBoxes;
            }
        }

        return $response;
    }

    public function listApprovals(Request $request)
    {
        $key = ApprovalApiKey::active()
            ->where('ak_key_hash', hash('sha256', $this->extractApiKey($request)))
            ->first();

        if (!$key) {
            return $this->handleError('Invalid or inactive API key', [], 401);
        }

        $approvals = ApprovalMaster::with('det')
            ->where('ams_active', 1)
            ->get();

        if (!empty($key->ak_allowed_amsm_ids)) {
            $approvals = $approvals->whereIn('id', $key->ak_allowed_amsm_ids);
        }

        return $this->handleResponse(
            $approvals->map(function ($a) {
                return [
                    'id' => $a->id,
                    'ams_idapv' => $a->ams_idapv,
                    'ams_title' => $a->ams_title,
                    'steps' => $a->det->pluck('amsmd_order')->unique()->values(),
                ];
            }),
            'Available approvals'
        );
    }

    public function registerKey(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'name' => 'required|string|max:255',
            'allowed_amsm_ids' => 'nullable|array',
            'allowed_amsm_ids.*' => 'integer',
        ]);

        $rawKey = Str::random(48);
        $hash = hash('sha256', $rawKey);

        ApprovalApiKey::create([
            'p_u_username' => $request->username,
            'ak_name' => $request->name,
            'ak_key_hash' => $hash,
            'ak_allowed_amsm_ids' => $request->allowed_amsm_ids ?? null,
            'ak_active' => true,
        ]);

        return $this->handleResponse([
            'api_key' => 'ams_' . $rawKey,
            'warning' => 'Save this key now. It will only be shown once.',
        ], 'API key created successfully');
    }

    private function extractApiKey(Request $request)
    {
        if ($request->header('X-Api-Key')) {
            return $request->header('X-Api-Key');
        }
        $key = $request->input('api_key');
        return str_starts_with((string) $key, 'ams_') ? substr((string) $key, 4) : $key;
    }

    private function keyCanUseApproval(ApprovalApiKey $key, $amsm_id)
    {
        if (empty($key->ak_allowed_amsm_ids)) {
            return true;
        }
        return in_array((int) $amsm_id, $key->ak_allowed_amsm_ids);
    }
}
