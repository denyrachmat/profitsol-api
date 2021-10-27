<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormHist extends Model
{
    use CompositeKey, SoftDeletes;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_hist';
    protected $primaryKey = [
        'form_id',
        'form_hist_id',
        'form_hist_username'
    ];

    protected $fillable = [
        'form_hist_id',
        'form_id',
        'publish_token',
        'publish_id',
        'form_hist_value',
        'form_hist_username',
    ];

    public function users()
    {
        return $this->hasOne('App\Models\HRMS\Auth\UserMaster','username','form_hist_username');
    }
}
