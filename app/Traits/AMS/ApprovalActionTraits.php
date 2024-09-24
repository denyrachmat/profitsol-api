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

        preg_match_all("/\{{(.*?)\}}/", $dataMaster->apprvSet->amssd_content, $matches);
        $listVariable = array_values(array_filter($matches[1], function ($fc) {
            return !str_contains($fc, 'fullname');
        }));

        if (count($listVariable) > 0) {
            if ($request->has('data')) {
                $checkFil = array_values(array_filter($listVariable, function($f) use ($request) {
                    return !in_array($f, $request->data);
                }));

                if (count($checkFil) > 0) {
                    return $this->handleError("you hasn't provide all data keys on request!!", $checkFil);
                }
            } else {
                return $this->handleError("you hasn't provide data keys on request!!", $listVariable);
            }
        }

        // Check if quota more than 0 then using quota
        if ($dataMaster->apprvSet->amssd_quotkn > 0) {
            $useToken = ApprovalTokenDetail::where('amsm_id', $request->amsm_id)->first()->amstd_token;

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
        $getfirstOrder = 0;
        foreach ($dataMaster->det as $keyDet => $valueDet) {
            if ($keyDet === 0) {
                $getfirstOrder = $valueDet['amsmd_order'];
            }

            if ($valueDet['amsmd_order'] === $getfirstOrder) {
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
                    'amshd_token' => $useToken,
                    'amshd_username_apprv' => $valueDet['amsmd_username'],
                    'amshd_stat' => $nextStat,
                    'amshd_remarks' => $request->remarks,
                ]);

                // If Email notification is on
                if ($dataMaster->apprvSet->amssd_isemail) {
                    $queueSet = new EmailNotificationQueue(
                        'deny-rachmat@sumitronics.co.jp',
                        'AMS Approval & Notification',
                        $valueDet->amsmd_reqaprv,
                        $dataMaster->ams_content,
                        $useToken
                    );

                    dispatch($queueSet)->onQueue('sendEmailQueue');
                }
            }
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

    public function getMasterApprovalByToken($token)
    {
        $cekToken = ApprovalTokenDetail::where('amstd_token', $token)->first();

        if (!empty($cekToken)) {
            return $this->handleResponse(ApprovalMaster::where('id', $cekToken->amsm_id)->with('det')->first(), 'Token found !!');
        }

        return $this->handleError('Token not found !! please check again !!');
    }
}
