<?php

namespace App\Models\HRMS\Core\Form;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class FormPageMapping extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_form_page_mapping';

    protected $primaryKey = ['content_id'];

    protected $fillable = [
        'content_id',
        'menu_id',
        'publish_flag',
        'publish_token',
        'username',
        'active_end',
        'active_start',
        'revised_answer'
    ];

    public function contentDetail()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormContentMapping', 'div_id', 'content_id');
    }

    public function detail()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormPageMappingDet', 'mapping_id', 'id');
    }

    public function hist()
    {
        return $this->hasMany('App\Models\HRMS\Core\Form\FormHist', 'publish_token', 'publish_token');
    }
}
