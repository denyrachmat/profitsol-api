<?php

namespace App\Traits\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\AMS\ApprovalTokenDetail;
use Illuminate\Http\Request;
use App\Http\Requests\AMS\ApprovalRunningApproveActionRequest;

use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalHistDetail;
use App\Models\AMS\ApprovalSetDetail;

trait ApprovalActionTraits
{
    public function approveAction(ApprovalRunningApproveActionRequest $request)
    {
        $useToken = ApprovalTokenDetail::where('amsm_id', $request->amsm_id)->first();

        if (empty($useToken)) {
            return $this->handleError('Your quota is empty, please consult administrator !!');
        }

        $hist = ApprovalHistDetail::create([
            'p_u_username' => $request->header('username'),
            'amsm_id' => $request->amsm_id,
            'amshd_token' => $useToken->amstd_token,
            'amshd_username_apprv' => $request->header('username'),
            'amshd_stat' => $request->stat,
            'amshd_remarks' => $request->remarks,
        ]);

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

    public function approveListForNotif($uname) {
        return ApprovalHistDetail::where('amshd_username_apprv', $uname)->get();
    }
}
