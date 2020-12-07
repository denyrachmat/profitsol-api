<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\eLetter\letterMaster;
use App\Models\HRMS\Core\eLetter\letterFormDetail;
use App\Models\HRMS\Core\eLetter\letterFormHist;
use App\Models\HRMS\Core\Form\FormPageMapping;

class eLetterController extends Controller
{
    public function getlistform()
    {
        return FormPageMapping::with(['contentDetail', 'detail'])->where('publish_flag', 'add_to_menu')->get();
    }

    public function save(Request $r)
    {
        // return $r;
        if ($r->has('id')) {
            $master = letterMaster::where('id', $r->id)->update([
                'content_title' => $r->title,
                'content_html' => $r->hasil,
                'content_type' => $r->menu,
                'content_username' => $r->header('username')
            ]);

            $idMaster = $r->id;
    
            if (count($r->var) > 0) {
                foreach ($r->var as $key => $value) {
                    $det = letterFormDetail::updateOrCreate([
                        'content_id' => $idMaster,
                        'form_label' => $value['form_label']
                    ],
                    [
                        'content_id' => $idMaster, 
                        'form_label' => $value['form_label'],
                        'form_type' => $value['form_type'],
                        'form_comp' => $value['form_comp'],
                        'form_req' => $value['form_req'],
                        'username' => $r->header('username'),
                        'form_parent' => '0'
                    ]);
    
                    logger($det->getKey());
                    
                    $idDetail = letterFormDetail::where('form_parent','0')->latest()->first();
    
                    if (count($value['children']) > 0) {
                        $this->storeChildForm($value, $idDetail->id, $idMaster, $r->header('username'));
                    }
                }
            }
    
            return json_encode($master);
        } else {
            $master = letterMaster::create([
                'content_title' => $r->title,
                'content_html' => $r->hasil,
                'content_type' => $r->menu,
                'content_username' => $r->header('username')
            ]);
            
            $idMaster = $master->getKey();
    
            if (count($r->var) > 0) {
                foreach ($r->var as $key => $value) {
                    $det = letterFormDetail::updateOrCreate([
                        'content_id' => $idMaster,
                        'form_label' => $value['form_label']
                    ],
                    [
                        'content_id' => $idMaster, 
                        'form_label' => $value['form_label'],
                        'form_type' => $value['form_type'],
                        'form_comp' => $value['form_comp'],
                        'form_req' => $value['form_req'],
                        'username' => $r->header('username'),
                        'form_parent' => '0'
                    ]);
    
                    logger($det->getKey());
                    
                    $idDetail = letterFormDetail::where('form_parent','0')->latest()->first();
    
                    if (count($value['children']) > 0) {
                        $this->storeChildForm($value, $idDetail->id, $idMaster, $r->header('username'));
                    }
                }
            }
    
            return json_encode($master);
        }
    }

    public function storeChildForm($data, $parent, $idMaster, $uname)
    {
        letterFormDetail::where('content_id', $idMaster)->where('form_parent', $parent)->delete();
        foreach ($data['children'] as $key => $value) {
            letterFormDetail::create([
                'content_id' => $idMaster, 
                'form_label' => $value,
                'form_type' => $data['form_type'],
                'form_comp' => $data['form_comp'],
                'form_req' => $data['form_req'],
                'username' => $uname,
                'form_parent' => $parent
            ]);
        }
    }

    public function index($id = null)
    {
        if (!$id) {
            return letterMaster::with(['letterDetail' => function ($q){
                $q->where('form_parent', '0');
                $q->with('childForm');
            }])->with('menuList')->get();
        } else {
            return letterMaster::with(['letterDetail' => function ($q){
                $q->where('form_parent', '0');
                $q->with('childForm');
            }])->with('menuList')->where('id', $id)->get();
        }
    }
}
