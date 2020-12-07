<?php

namespace App\Models\HRMS\Core\eLetter;

use Illuminate\Database\Eloquent\Model;
// use App\Helpers\CompositeKey;

class letterMaster extends Model
{
    // use CompositeKey;

    protected $primaryKey = "id";
    public $incrementing = true;
    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_content_mstr';
    protected $fillable = [
        'page_mapping_id',
        'menu_url',
        'content_username',
        'publish_methods'
    ];

    public function menuList()
    {
        return $this->hasOne('App\Models\HRMS\Auth\MenuMaster', 'menu_url', 'content_type');
    }
}
