<?php

namespace App\Models\MRS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MRSReportActionDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mrs';
    protected $table = 'mrs_report_act_det';
    protected $fillable = [
        'mrm_id',
        'mrad_action',
        'mrad_label',
        'mrad_url',
        'mrad_target',
        'mrad_icon',
        'mrad_parent_id'
    ];
}
