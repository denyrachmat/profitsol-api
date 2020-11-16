<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class FormHist extends Model
{
    use CompositeKey;

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
}
