<?php

namespace App\Models\DMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DMSDocRootMstr extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_doc_root_mstr';

    protected $fillable = [
        "ddrm_name",
        "ddrm_driver",
        "ddrm_root",
        "ddrm_desc",
        "ddrm_host",
        "ddrm_username",
        "ddrm_password",
        "ddrm_url",
    ];
}
