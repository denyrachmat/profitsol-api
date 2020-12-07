<?php

namespace App\Models\HRMS\Core\Structure;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class occMaster extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_hrms';
    protected $table = 'hrms_occ_mstr';
    
    protected $primaryKey = [
        'occ_name',
        'division_id'
    ];
    
    protected $fillable = [
        'occ_name',
        'occ_parent_id',
        'division_id',
        'occ_emp_count',
        'domain_id',
    ];

    public function child()
    {
        return $this->hasMany('App\Models\HRMS\Core\Structure\occMaster','occ_parent_id','id');
    }

    public function children()
    {
        return $this->child()->with('children')->with('user')->with('division');
    }

    public function childrenWithoutDiv()
    {
        return $this->child()->with('children')->with('user');
    }

    public function parent()
    {
        return $this->hasMany('App\Models\HRMS\Core\Structure\occMaster','id','occ_parent_id');
    }

    public function parentList()
    {
        return $this->parent()->with('parentList')->with('user')->with('division');
    }

    public function parentListWithoutDiv()
    {
        return $this->parent()->with('parentList')->with('user');
    }

    public function user()
    {
        return $this->hasMany('App\Models\HRMS\Auth\UserMaster','occ_id','id');
    }

    public function division()
    {
        return $this->hasOne('App\Models\HRMS\Core\Structure\divisionMaster','id','division_id');
    }
}
