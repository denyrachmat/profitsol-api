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
use App\Models\PORTAL\PortalUserDet;

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

        // Check if there is any content using variable on recepient
        preg_match_all("/\{{(.*?)\}}/", $dataMaster->apprvSet->amssd_content, $matches);
        $listVariable = array_values(array_filter($matches[1], function ($fc) {
            return !str_contains($fc, 'fullname');
        }));

        if (count($listVariable) > 0) {
            if ($request->has('data')) {
                $checkFil = array_values(array_filter($listVariable, function ($f) use ($request) {
                    return !in_array($f, array_keys($request->data));
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

            // Get Only latest order
            if ($valueDet['amsmd_order'] === $getfirstOrder) {
                $checkLatest = ApprovalHistDetail::where('amsm_id', $request->amsm_id)
                    ->with('mapdet')
                    // ->where('amsmd_id', $valueDet['id'])
                    // ->where('p_u_username', $request->username)
                    ->orderBy('id', 'desc')
                    ->first();

                $nextStat = 'sent';
                if (!empty($checkLatest)) {
                    $nextStat = $valueDet['amsmd_order'] != $checkLatest->mapdet->amsmd_order && $request->stat === 1
                        ? 'approve'
                        : (
                            $valueDet['amsmd_order'] != $checkLatest->mapdet->amsmd_order && $request->stat === 0
                            ? 'reject'
                            : 'sent'
                        );
                }

                $histToken = Str::random(50);
                // Sent Notif
                $hist = ApprovalHistDetail::create([
                    'p_u_username' => $request->username,
                    'amsm_id' => $request->amsm_id,
                    'amsmd_id' => $valueDet['id'],
                    'amshd_token' => $histToken,
                    'amstd_token' => $useToken,
                    'amshd_username_apprv' => $valueDet['amsmd_username'],
                    'amshd_stat' => $nextStat,
                    'amshd_remarks' => $request->remarks,
                    'amshd_paramstore' => json_encode($request->data)
                ]);

                // Receive Notif
                $hist = ApprovalHistDetail::create([
                    'p_u_username' => $valueDet['amsmd_username'],
                    'amsm_id' => $request->amsm_id,
                    'amsmd_id' => $valueDet['id'],
                    'amshd_token' => $histToken,
                    'amstd_token' => $useToken,
                    'amshd_username_apprv' => '',
                    'amshd_stat' => 'receive',
                    'amshd_remarks' => $request->remarks,
                    'amshd_paramstore' => json_encode($request->data)
                ]);

                if (!isset($dataMaster->det[$keyDet + 1])) {
                    $useTokenCreate = ApprovalTokenDetail::where('amstd_token', $useToken)->first();
                    // Delete used token
                    ApprovalTokenDetail::where('id', $useTokenCreate->id)->delete();
                }

                // If Email notification is on
                if ($dataMaster->apprvSet->amssd_isemail) {
                    $queueSet = new EmailNotificationQueue(
                        $request->username,
                        'deny-rachmat@sumitronics.co.jp',
                        'AMS Approval & Notification',
                        $valueDet->amsmd_reqaprv,
                        $dataMaster->ams_content,
                        $useToken.'/'.$histToken
                    );

                    // dispatch($queueSet)->onQueue('sendEmailQueue');
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
                if (isset($value['step']) && $value['step'] === 'or') {
                    $hist = (clone $hist)->orwhere($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                } else {
                    $hist = (clone $hist)->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)->with('senderUser', 'receiveUser')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            return $this->handleResponse($datanya, 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
    }

    public function approveListForNotif($uname)
    {
        return ApprovalHistDetail::where('amshd_username_apprv', $uname)->get();
    }

    public function getMasterApprovalByToken($token, $tokenHist)
    {
        $cekToken = ApprovalTokenDetail::with(['hist' => function ($f) use ($tokenHist) {
                $f->where('amshd_token', $tokenHist)
                ->orderBy('id', 'asc')
                ->get()
                ->toArray();
            }])
            ->where('amstd_token', $token)
            ->first();

        // return $cekToken;

        if (!empty($cekToken) && !empty($cekToken->hist)) {
            $getSender = array_values(array_filter((clone $cekToken)->toArray()['hist'], function($f) { return $f['amshd_stat'] !== 'receive';}))[0];
            $getReceiver = array_values(array_filter((clone $cekToken)->toArray()['hist'], function($f) { return $f['amshd_stat'] === 'receive';}))[0];

            $hasil = ApprovalMaster::where('id', $cekToken['amsm_id'])->with('det')->with('apprvSet', function ($f) {
                $f->get();
            })->first();

            if (!empty($hasil->apprvSet->amssd_content)) {
                $hasilnya = $hasil->apprvSet->amssd_content;

                $hasil->apprvSet->amssd_content = $this->convertValuetoContent(
                    $hasil->apprvSet->amssd_content,
                    $getSender['p_u_username'],
                    $getReceiver['p_u_username'],
                    json_decode($getReceiver['amshd_paramstore']),
                );
                $hasilnya = $hasil->toArray();
            } else {
                $hasilnya = $hasil->toArray();
            }

            return $this->handleResponse(array_merge($hasilnya, ['token' => $cekToken]), 'Token found !!');
        }

        return $this->handleError('Token not found !! please check again !!');
    }

    public function convertValuetoContent($content, $fromUname, $toUname, $param = [], $token = '')
    {
        $getUsersFrom = PortalUserDet::where('u_username', $fromUname)->first();
        $getUsers = PortalUserDet::where('u_username', $toUname)->first();

        // Convert fullname Recepient variable
        $convertContent = str_replace(search: "{{recipient_fullname}}", replace: $toUname, subject: $content);

        // If user exists then use fullname instead
        if (!empty($getUsers)) {
            $convertContent = str_replace(search: "{{recipient_fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_last_name}", subject: $content);
        }

        // Convert fullname sender variable

        // If user exists then use fullname instead
        if (!empty($getUsersFrom)) {
            $convertContent = str_replace(search: "{{fullname}}", replace: "{$getUsersFrom->pud_first_name} {$getUsersFrom->pud_last_name}", subject: $convertContent);
        } else {
            $convertContent = str_replace(search: "{{fullname}}", replace: $fromUname, subject: $convertContent);
        }

        $convertContent = str_replace(search: "{{linkapproval}}", replace: env('FE_URL') . "/ams/approvalAction/{$token}", subject: $convertContent);

        foreach ($param as $keyVar => $valueVar) {
            $convertContent = str_replace(search: "{{" . $keyVar . "}}", replace: $valueVar, subject: $convertContent);
        }

        return $convertContent;
    }
}
