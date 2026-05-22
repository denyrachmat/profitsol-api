<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRPAMaster extends Model
{
    use HasFactory;
    protected $table = 'portal_rpa_mstr';
    protected $fillable = [
        'prm_name',
        'prm_type',
        'prm_host',
        'prm_port',
        'prm_isactive',
        'prm_desc'
    ];
    protected $casts = [
        'prm_isactive' => 'boolean',
    ];

    public function save(array $options = [])
    {
        $saved = parent::save($options);

        if ($saved && $this->relationLoaded('prmParameter')) {
            foreach ($this->prmParameter as $param) {
                $param->prpd_prmid = $this->id;
                $param->save();
            }
        }

        return $saved;
    }

    public function prmParameter()
    {
        return $this->hasMany(PortalRPAParamDet::class, 'prpd_prmid', 'id');
    }

    public function prmCommand()
    {
        return $this->hasMany(PortalRPACmdDet::class, 'prcd_prpdid', 'id');
    }

}
