<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HSCodeFilesDet extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_log';
    protected $table = 'HSCD_FILES_DET';
    
    protected $fillable = [
        'p_u_username',
        'HSCD_DOCNO',
        'HSCD_ITMCD',
        'HFD_FILENAME',
        'HFD_FILEPATH',
    ];
}
