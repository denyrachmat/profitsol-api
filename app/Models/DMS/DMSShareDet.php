<?php

namespace App\Models\DMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DMSShareDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_doc_folder_user_share_map';

    protected $fillable = [
        'p_u_username',
        'ddm_id',
        'dfm_id',
        'ddfus_p_u_username',
        'ddfus_read',
        'ddfus_write',
        'ddfus_token',
    ];

    public function folder() {
        return $this->belongsTo(DMSFolderMstr::class, 'dfm_id', 'id');
    }
    public function file() {
        return $this->belongsTo(DMSDocMstr::class, 'ddm_id', 'id');
    }
}
