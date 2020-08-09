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
        'id',
        'content_title',
        'content_html',
        'content_type',
        'content_username',
    ];

    public function letterDetail()
    {
        return $this->hasMany('App\Models\HRMS\Core\eLetter\letterFormDetail','content_id','id');
    }

    public function menuList()
    {
        return $this->hasOne('App\Models\HRMS\Auth\MenuMaster', 'menu_url', 'content_type');
    }
}
