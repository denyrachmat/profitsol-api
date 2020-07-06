<?php

namespace App\Models\PORTAL\DOCCREATOR;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class ContentDefine extends Model
{
    use CompositeKey;

    protected $table = 'portal_content_def';
    protected $primaryKey = [
        'content_mstr_id',
        'apprv_mstr_id'
    ];
    
    protected $fillable = [
        'content_mstr_id',
        'apprv_mstr_id'
    ];

    public function contentDet()
    {
        return $this->hasMany('App\Models\PORTAL\DOCCREATOR\ContentDet','content_mstr_id','content_mstr_id');
    }

    public function contentMstr()
    {
        return $this->hasOne('App\Models\PORTAL\DOCCREATOR\ContentMaster','id','apprv_mstr_id');
    }

    public function mappingApp()
    {
        return $this->hasOne('App\Models\DMS\Custom\ContentMappingApp','content_id','content_mstr_id');
    }

    public function contentCreator()
    {
        return $this->hasMany('App\Models\PORTAL\DOCCREATOR\ContentCreator','content_creator_id','content_mstr_id');
    }
}
