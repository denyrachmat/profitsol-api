<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HSCodeGroupBeaDetail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'HSCD_BEAGRP_DET';
    protected $fillable = [
        'HSCD_BEADOCNM',
        'HSCD_ZIBD_DOCCD',
        'HSCD_BEAGRP_PRNT',
    ];

    public function child()
    {
        return $this->hasMany('App\Models\STXI\LOG\HSCodeGroupBeaDetail','HSCD_BEAGRP_PRNT','id');
    }

    public function childGroup()
    {
        return $this->child()->with('childGroup')->with('intrDocBea');
    }

    public function intrDocBea() {
        return $this->hasOne(INSWDataDocBeaMaster::class, 'ZIDBD_DOCCD', 'HSCD_BEADOCNM');
    }
}
