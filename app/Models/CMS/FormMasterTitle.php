<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormMasterTitle extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_mstr_title';

    protected $fillable = [
        'p_u_username',
        'cfmt_title',
        'cfmt_quiz_flag',
    ];
    public static function boot() {
        parent::boot();

        static::deleting(function($f) { // before delete() method call this
             $f->formMaster()->delete();
             // do the rest of the cleanup...
        });
    }
    public function formMaster()
    {
        return $this->hasMany(FormMaster::class, 'cfmt_id', 'id');
    }

    public function quizSetup()
    {
        return $this->hasMany(FormSetupDet::class, 'cfmt_id', 'id');
    }
}
