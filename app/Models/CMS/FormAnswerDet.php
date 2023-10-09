<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;

class FormAnswerDet extends Model
{
    use HasFactory, Compoships;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_ans_det';

    protected $fillable = [
        'p_u_username',
        'cfm_id',
        'cfmd_id',
        'cfm_val',
        'cfm_exp',
    ];

    public function answers()
    {
        return $this->belongsTo(FormMultiDet::class, ['cfmd_id', 'cfm_val'], ['cfm_id', 'cfmd_value']);
    }
}
