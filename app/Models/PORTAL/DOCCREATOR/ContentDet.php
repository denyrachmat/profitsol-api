<?php

namespace App\Models\PORTAL\DOCCREATOR;

use Illuminate\Database\Eloquent\Model;

class ContentDet extends Model
{
    protected $table = 'portal_content_det';

    protected $fillable = [
        'content_mstr_id',
        'content_var'
    ];
}
