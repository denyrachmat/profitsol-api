<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Structure\domainMaster;

class domainController extends Controller
{
    public function index()
    {
        return domainMaster::with(['division.children', 'division.domain', 'division.occ.user', 'division.occ.children', 'children'])->where('domain_parent', 0)->get();
    }

    public function save(Request $r)
    {
        $this->recursionDomain(0, $r->data);
        return 'success';
    }

    public function recursionDomain($parent, $data)
    {
        foreach ($data as $key => $value) {
            domainMaster::updateOrCreate([
                'domain_name' => $value['label']
            ],[
                'domain_name' => $value['label'],
                'domain_parent' => 0
            ]);

            if (count($value['children']) > 0) {
                $cekParent = domainMaster::latest('created_at')->first();
                $this->recursionDomain($cekParent->id, $value['children']);
            }
        }
    }
}
