<?php

namespace App\Models\DMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DMSDocMstr extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_doc_mstr';

    protected $fillable = [
        'p_u_username',
        'dfm_id',
        'ddm_doc_name',
        'ddm_doc_real_name',
        'ddm_doc_size',
        'ddm_doc_flag',
    ];

    public function folder()
    {
        return $this->hasOne('App\Models\DMS\DMSFolderMstr','id','dfm_id');
    }
}
