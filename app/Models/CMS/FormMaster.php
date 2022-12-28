<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_mstr';

    protected $fillable = [
        'p_u_username',
        'cfm_type',
        'cfm_title',
        'cfm_seq_name',
        'cfm_content',
        'cfm_parent_id',
        'cfm_quiz_flag',
    ];
}
