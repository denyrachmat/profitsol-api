<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FormMasterTitle extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_mstr_title';

    protected $fillable = [
        'p_u_username',
        'cfmt_title',
        'cfmt_quiz_flag',
        'cfmt_status',
        'cfmt_year',
    ];
    public static function boot() {
        parent::boot();

        static::deleting(function($f) {
            if (!$f->isForceDeleting()) {
                return;
            }
            $f->formMaster()->delete();
        });
    }
    public function formMaster()
    {
        return $this->hasMany(FormMaster::class, 'cfmt_id', 'id')->orderBy(DB::raw('CAST(cfm_seq_name AS INT)'), 'asc');
    }

    public function quizSetup()
    {
        return $this->hasOne(FormSetupDet::class, 'cfmt_id', 'id');
    }

    public function shared()
    {
        return $this->hasMany(FormShareDet::class, 'cfmt_id', 'id');
    }

    public function answers()
    {
        return $this->hasMany(FormAnswerUserDet::class, 'cfm_id', 'id');
    }
}
