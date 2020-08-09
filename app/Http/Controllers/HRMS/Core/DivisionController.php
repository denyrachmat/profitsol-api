<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Structure\divisionMaster;

class DivisionController extends Controller
{
    public function index()
    {
        return divisionMaster::with(['occ' => function($q) {
            $q->with('children');
            $q->with('user');
        }])->with('children')->get();
    }

    public function save(Request $r)
    {
        divisionMaster::where('domain_id', $r->domain['id'])->delete();
        $this->recursionDivision(0, $r->data, $r->domain['id']);
        return 'success';
    }

    public function recursionDivision($parent, $data, $domain)
    {
        foreach ($data as $key => $value) {
            divisionMaster::updateOrCreate([
                'division_name' => $value['label'],
                'domain_id' => $domain
            ],[
                'division_name' => $value['label'],
                'division_parent' => $parent,
                'domain_id' => $domain
            ]);

            if (count($value['children']) > 0) {
                $cekParent = divisionMaster::latest('created_at')->first();
                $this->recursionDivision($cekParent->id, $value['children'], $domain);
            }
        }
    }
}
