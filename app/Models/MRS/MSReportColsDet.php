<?php

namespace App\Models\MRS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MSReportColsDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mrs';
    protected $table = 'mrs_report_cols_det';

    protected $fillable = [
        'mrm_id',
        'mrcd_field',
        'mrcd_label',
        'mrcd_isActive',
        'mrcd_sortable',
        'mrcd_isFiltered',
        'mrcd_isExported',
    ];
}
