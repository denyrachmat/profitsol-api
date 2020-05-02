<?php

namespace App\Models\DMS\Core;

use Illuminate\Database\Eloquent\Model;

class VerMaster extends Model
{
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_ver_mstr';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ver_docnm',
        'ver_docloc',
        'ver_code',
        'ver_comment'
    ];
}
