<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class ContentMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_content_mstr';

    protected $fillable = [
        'content_title',
        'content_html'
    ];

    public function contentDet()
    {
        return $this->hasMany('App\Models\DMS\Core\ContentDet','content_mstr_id','id');
    }
}
