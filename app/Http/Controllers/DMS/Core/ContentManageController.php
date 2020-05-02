<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DMS\Core\ContentMaster;
use App\Models\DMS\Core\ContentDet;
use App\Models\DMS\Core\ApprovalMaster;

class ContentManageController extends Controller
{
    public function index()
    {
        return ContentMaster::with('contentDet')->get()->toArray();
    }

    public function store(Request $r)
    {
        if ($r->has('id')) {
            $hasilStoreMaster = ContentMaster::where('id', $r->id)->update([
                'content_title' => $r->title,
                'content_html' => $r->hasil
            ]);

            ContentDet::where('content_mstr_id', $r->id)->delete();
            foreach ($r->var as $key => $value) {
                ContentDet::create([
                    'content_mstr_id' => $r->id,
                    'content_var' => $value
                ]);
            }
        } else {
            $hasilStoreMaster = ContentMaster::create([
                'content_title' => $r->title,
                'content_html' => $r->hasil
            ]);

            foreach ($r->var as $key => $value) {
                ContentDet::create([
                    'content_mstr_id' => $hasilStoreMaster['id'],
                    'content_var' => $value
                ]);
            }
        }

        return $hasilStoreMaster;
    }

    public function contentDefineData()
    {
        $select = [
            'apprv_author',
            'apprv_title',
            'apprv_id'
        ];

        $apprvMaster = ApprovalMaster::select($select)->with('userAuthor')->groupBy($select)->get()->toArray();
        $contentMstr = ContentMaster::with('contentDet')->get()->toArray();

        return [
            'apprv' => $apprvMaster,
            'content' => $contentMstr
        ];
    }
}
