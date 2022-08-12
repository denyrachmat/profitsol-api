<?php

namespace App\Models\DMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DMSFolderRootMstr extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_users_doc_root_mstr';

    protected $fillable = [
        'p_u_username',
        'dudrm_path'
    ];
}
