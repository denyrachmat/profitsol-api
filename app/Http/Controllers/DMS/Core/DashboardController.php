<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\ApprovalHist;
use Illuminate\Support\Str;
use App\Models\DMS\Core\DocsMaster;
use App\Models\PORTAL\DivisisPortal;

class DashboardController extends Controller
{
    public function listnotif($user)
    {
        
    }

    public function readnotif($idhist)
    {
        return ApprovalHist::where('id', $idhist)->update([
            'apprv_hist_vwtime' => date('Y-m-d H:i:s')
        ]);
    }

    public function getalldocumentbyrole($div = null)
    {
        $selectdiv = [
            'division_name',
            'role_id'
        ];
        if (empty($div)) {
            return DivisisPortal::select($selectdiv)
                ->where('ROLE_ID','not like', '%ROOT%')
                ->where('ROLE_ID','like','%DMS')
                ->with(['userdms' => function ($q1)
                {
                    $q1->select(
                        'username',
                        'first_name',
                        'last_name',
                        'email',
                        'role_id'
                    );
                    $q1->has('doc');
                    $q1->with(['doc' => function ($q2){
                        $q2->select(
                            'doc_id',
                            'doc_name',
                            'doc_author',
                            'doc_real_name',
                            'doc_path',
                            'created_at'
                        );
                        $q2->doesnthave('version');
                        $q2->with('apprvhist.allApproverList');
                    }]);
                    $q1->with('docLocation.allChildFolder');
                    $q1->with('approver');
                }])
                ->orderBy('division_name')
                ->get()
                ->toArray();
        } else {
            return DivisisPortal::select($selectdiv)
                ->where('ROLE_ID','not like', '%ROOT%')
                ->where('ROLE_ID','like','%DMS')
                ->where('id',$div)
                ->orderBy('division_name')
                ->first()
                ->toArray();
        }
    }
}