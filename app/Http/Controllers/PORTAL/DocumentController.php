<?php

namespace App\Http\Controllers\PORTAL;

use App\Http\Controllers\Controller;
use App\Models\PORTAL\CUSTOMS\CircularTen;
use App\Models\PORTAL\DOCCREATOR\ContentCreator;
use App\Models\PORTAL\DOCCREATOR\ContentDefine;
use Illuminate\Http\Request;

use App\Models\PORTAL\DOCCREATOR\ContentMaster;
use App\Models\PORTAL\DOCCREATOR\ContentDet;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;

class DocumentController extends Controller
{
    public function index($id_menu = null)
    {
        if (!empty($id_menu)) {
            return ContentMaster::with(['contentDet','contentDef'])->whereHas('mappingApp')
            ->with('mappingApp')
            ->get()
            ->toArray();
        }

        return ContentMaster::with(['contentDet','contentDef'])->get()->toArray();
    }

    public function store(Request $r)
    {
        if ($r->has('id')) {
            $hasilStoreMaster = ContentMaster::where('id', $r->id)->update([
                'content_title' => $r->title,
                'content_html' => $r->hasil,
                'menu_id' => $r->menu_id
            ]);

            ContentDet::where('content_mstr_id', $r->id)->delete();

            if ($r->var) {
                foreach ($r->var as $key => $value) {
                    ContentDet::create([
                        'content_mstr_id' => $r->id,
                        'content_var' => $value
                    ]);
                }
            }

            $id = $r->id;
        } else {
            $hasilStoreMaster = ContentMaster::create([
                'content_title' => $r->title,
                'content_html' => $r->hasil,
                'menu_id' => $r->menu_id
            ]);

            if ($r->var) {
                foreach ($r->var as $key => $value) {
                    ContentDet::create([
                        'content_mstr_id' => $hasilStoreMaster['id'],
                        'content_var' => $value
                    ]);
                }
            }

            $id = $hasilStoreMaster['id'];
        }

        return $hasilStoreMaster;
    }

    public function printcover(Request $r)
    {
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->loadHTML($r->html);

        return  base64_encode($pdf->inline());

        // PDF::SetTitle($r->title);
        // PDF::AddPage();
        // PDF::writeHTML($r->html, true, false, false, false, '');

        // return base64_encode(PDF::Output($r->title.'.pdf', 'S'));
    }

    public function deletecontent($id)
    {
        ContentMaster::where('id', $id)->delete();
        ContentDet::where('content_mstr_id', $id)->delete();
        
        $cekcontent = ContentDefine::where('apprv_mstr_id',$id)->get();

        foreach ($cekcontent as $key => $value) {
            ContentCreator::where('content_creator_id', $value['content_mstr_id'])->delete();
            CircularTen::where('creator_id', $value['content_mstr_id'])->delete();
        }

        ContentDefine::where('apprv_mstr_id',$id)->delete();

        return 'success';
    }
}
