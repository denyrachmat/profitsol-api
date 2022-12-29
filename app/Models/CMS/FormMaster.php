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
        'cfmt_id',
        'cfm_type',
        'cfm_seq_name',
        'cfm_content',
        'cfm_parent_id',
    ];

    public function formDetail()
    {
        return $this->hasMany(FormMultiDet::class, 'cfm_id', 'id');
    }

    public function formAnswer()
    {
        return $this->hasMany(FormAnswerDet::class, 'cfm_id', 'id');
    }
}
