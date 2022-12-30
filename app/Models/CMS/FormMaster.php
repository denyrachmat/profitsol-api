<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_form_mstr';

    protected $fillable = [
        'p_u_username',
        'cfmt_id',
        'cfm_type',
        'cfm_seq_name',
        'cfm_content',
        'cfm_parent_id',
        'cfm_required',
    ];

    public static function boot() {
        parent::boot();

        static::deleting(function($f) { // before delete() method call this
             $f->formDetail()->delete();
             $f->formAnswer()->delete();
             $f->allChildrenContent()->delete();
             // do the rest of the cleanup...
        });
    }

    public function formDetail()
    {
        return $this->hasMany(FormMultiDet::class, 'cfm_id', 'id');
    }

    public function formAnswer()
    {
        return $this->hasMany(FormAnswerDet::class, 'cfm_id', 'id');
    }

    public function childrenContent()
    {
        return $this->hasMany(FormMaster::class, 'cfm_parent_id', 'id');
    }

    public function allChildrenContent()
    {
        return $this->childrenContent()->with('allChildrenContent');
    }
}