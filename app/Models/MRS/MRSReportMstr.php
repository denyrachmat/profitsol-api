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
        'mrm_filter_flg'
    ];

    public function database()
    {
        // Assuming the foreign key is 'mdm_id' on mrs_report_mstr
        // and the primary key is 'id' on mrs_db_mstr
        return $this->belongsTo(MRSDBConnMstr::class, 'mdm_id', 'id');
    }
}
