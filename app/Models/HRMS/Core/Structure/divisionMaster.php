<?php

namespace App\Models\HRMS\Core\Structure;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class divisionMaster extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_division_mstr';
    
    protected $primaryKey = [
        'division_name',
        'domain_id'
    ];
    
    protected $fillable = [
        'division_name',
        'division_parent',
        'domain_id',
    ];

    public function occ()
    {
        return $this->hasMany('App\Models\HRMS\Core\Structure\occMaster','division_id','id')->where('occ_parent_id', 0);
    }

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Core\Structure\divisionMaster','division_parent','id');
    }

    public function children()
    {
        return $this->child()->with('children');
    }
}
