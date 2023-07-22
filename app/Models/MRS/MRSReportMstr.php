<?php

namespace App\Models\MRS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MRSReportMstr extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_mrs';
    protected $table = 'mrs_report_mstr';
    protected $fillable = [
        'p_u_username',
        'mdm_id',
        'mrm_name',
        'mrm_db',
        'mrm_table',
        'mrm_query',
        'created_at',
        'updated_at',
        'mrm_url_gen',
    ];
}
