<?php

namespace App\Models\HRMS\Core\Structure;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class domainMaster extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_domain_mstr';
    
    protected $primaryKey = [
        'domain_name'
    ];
    
    protected $fillable = [
        'domain_name',
        'domain_parent',
    ];

    public function division()
    {
        return $this->hasMany('App\Models\HRMS\Core\Structure\divisionMaster','domain_id','id')->where('division_parent', 0);
    }

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Core\Structure\domainMaster','domain_parent','id');
    }

    public function children()
    {
        return $this->child()->with('children');
    }
}
