<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormShareDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_share_det';

    protected $fillable = [
        'cfmt_id',
        'p_u_username',
        'cfsd_to',
        'cfsd_gen_link',
        'cfsd_role_id',
        'cfsd_is_menu'
    ];

    public function forms()
    {
        return $this->hasOne(FormMasterTitle::class, 'id', 'cfmt_id');
    }
}
