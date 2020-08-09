<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Structure\occMaster;
use App\Models\HRMS\Auth\UserMaster;

class organizationController extends Controller
{
    function save(Request $r)
    {
        foreach ($r->data as $key => $value) {
            UserMaster::where('username', $value['username'])->update([
                'occ_id' => $value['id']
            ]);
        }

        return 'success';
    }
}
