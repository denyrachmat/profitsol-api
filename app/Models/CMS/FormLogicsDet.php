<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormLogicsDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_logics_det';
    protected $fillable = [
        'cfm_id',
        'cfld_opr',
        'cfld_val',
        'cfld_opr_ctrl',
        'cfld_res',
        'cfld_actions',
        'cfld_seq_name',
        'cfld_seq_desc'
    ];
}
