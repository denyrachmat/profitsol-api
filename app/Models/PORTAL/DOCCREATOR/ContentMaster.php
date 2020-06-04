<?php

namespace App\Models\PORTAL\DOCCREATOR;

use Illuminate\Database\Eloquent\Model;

class ContentMaster extends Model
{
    protected $table = 'portal_content_mstr';

    protected $fillable = [
        'content_title',
        'content_html',
        'menu_id'
    ];

    public function contentDet()
    {
        return $this->hasMany('App\Models\PORTAL\DOCCREATOR\ContentDet','content_mstr_id','id');
    }

    public function contentDef()
    {
        return $this->hasMany('App\Models\PORTAL\DOCCREATOR\ContentDefine','apprv_mstr_id','id');
    }

    public function mappingApp(){
        return $this->hasOne('App\Models\PORTAL\MenusPortal','id','menu_id');
    }
}
