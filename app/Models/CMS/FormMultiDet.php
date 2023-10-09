<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Awobaz\Compoships\Compoships;

class FormMultiDet extends Model
{
    use HasFactory, Compoships;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_multi_det';

    protected $fillable = [
        'cfm_id',
        'cfmd_value',
        'cfmd_label',
    ];
    
    public function formAnswer()
    {
        return $this->hasOne(FormAnswerDet::class, ['cfmd_id', 'cfm_val'], ['id', 'cfmd_value']);
    }
}
