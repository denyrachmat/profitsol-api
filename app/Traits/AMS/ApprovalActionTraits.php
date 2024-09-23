<?php

namespace App\Traits\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\AMS\ApprovalTokenDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Http\Requests\AMS\ApprovalRunningApproveActionRequest;

use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalHistDetail;

use App\Jobs\AMS\EmailNotificationQueue;

trait ApprovalActionTraits
{
    public function approveAction(ApprovalRunningApproveActionRequest $request)
    {
        $dataMaster = ApprovalMaster::where('id', $request->amsm_id)->with(
            'det',
            function ($f) {
                $f->orderBy('amsmd_order');
                $f->whereDoesntHave('hist');
            }
        )
            ->with('apprvSet')
            ->first();

        // Check if quota more than 0 then using quota
        if ($dataMaster->apprvSet->amssd_quotkn > 0) {
            $useToken = ApprovalTokenDetail::where('amsm_id', $request->amsm_id)->first();

            if (empty($useToken)) {
                return $this->handleError('Your quota is empty, please consult administrator !!');
            }
        } else {
            $useToken = Str::random(50);
            ApprovalTokenDetail::create([
                'p_u_username' => $request->username,
                'amsm_id' => $request->amsm_id,
                'amstd_token' => $useToken,
            ]);
        }
        $hist = [];
        foreach ($dataMaster->det as $keyDet => $valueDet) {
            $checkLatest = ApprovalHistDetail::where('amsm_id', $request->amsm_id)
                ->where('amsmd_id', $valueDet['id'])
                ->orderBy('created_at', 'desc')
                ->withTrashed()
                ->first();

            $nextStat = 'sent';
            if (!empty($checkLatest)) {
                $nextStat = match ($checkLatest->amshd_stat && $request->stat === 1) {
                    'sent' && $request->stat === 1 => 'approve',
                    'sent' && $request->stat === 0 => 'reject',
                    'approve', 'reject' => 'sent'
                };
            }

            $hist = ApprovalHistDetail::create([
                'p_u_username' => $request->username,
                'amsm_id' => $request->amsm_id,
                'amsmd_id' => $valueDet['id'],
                'amshd_token' => $dataMaster->apprvSet->amssd_quotkn > 0 ? $useToken->amstd_token : $useToken,
                'amshd_username_apprv' => $valueDet['amsmd_username'],
                'amshd_stat' => $nextStat,
                'amshd_remarks' => $request->remarks,
            ]);
        }

        // If Email notification is on
        if ($dataMaster->apprvSet->amssd_isemail) {
            $queueSet = new EmailNotificationQueue(
                to: 'deny-rachmat@sumitronics.co.jp',
                content: $dataMaster->ams_content
            );

            dispatch($queueSet)->onQueue('sendEmailQueue');
        }

        return $this->handleResponse($hist, 'Success');
    }

    public function approveHist(Request $request)
    {
        $hist = new ApprovalHistDetail;

        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $key => $value) {
                $hist->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
            }
        }

        if ((clone $hist)->count() > 0) {
            return $this->handleResponse((clone $hist)->get(), 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
    }

    public function approveListForNotif($uname)
    {
        return ApprovalHistDetail::where('amshd_username_apprv', $uname)->get();
    }
}
