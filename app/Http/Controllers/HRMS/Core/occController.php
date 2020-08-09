<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Structure\occMaster;
use App\Models\HRMS\Core\Structure\divisionMaster;
use App\Models\HRMS\Auth\UserMaster;

class occController extends Controller
{
    public function index()
    {
        return occMaster::with('children.user')->with('user')->get();
    }

    public function save(Request $r)
    {
        $cekPrevOcc = occMaster::where('division_id', $r->division['id'])->get();

        occMaster::where('division_id', $r->division['id'])->delete();
        $this->recursionOcc(0, $r->data, $r->division['id']);
        
        $cekNowOcc = occMaster::where('division_id', $r->division['id'])->get();
        foreach ($cekNowOcc as $key => $value) {
            if (isset($cekPrevOcc[$key])) {
                UserMaster::where('occ_id', $cekPrevOcc[$key])->update([
                    'occ_id' => $value['id']
                ]);
            }
        }
        return 'success';
    }

    public function recursionOcc($parent, $data, $division)
    {
        foreach ($data as $key => $value) {
            $domaindiv = divisionMaster::where('id', $division)->first();
            occMaster::create([
                'occ_name' => $value['label'],
                'occ_emp_count' => $value['emp'],
                'occ_parent_id' => $parent,
                'division_id' => $division,
                'domain_id' => $domaindiv->domain_id
            ]);

            if (count($value['children']) > 0) {
                $cekParent = occMaster::latest('created_at')->first();
                $this->recursionOcc($cekParent->id, $value['children'], $division);
            }
        }
    }
}
