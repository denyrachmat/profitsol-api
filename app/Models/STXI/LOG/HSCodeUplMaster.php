<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HSCodeUplMaster extends Model
{
    use HasFactory,SoftDeletes;

    public $incrementing = false;
    protected $primaryKey = [
        'HSCD_BG', 'HSCD_ITMCD'
    ];
    protected $connection = 'sqlsrv_log';
    protected $table = 'HSCD_UPL_TBL';
    protected $fillable = [
        'HSCD_DOCNO',
        'HSCD_BG',
        'HSCD_ITMCD',
        'HSCD_SERIES',
        'HSCD_MKHSCD',
        'HSCD_STXICD',
        'HSCD_UPLTYFORM',
        'HSCD_ISSDT',
        'HSCD_REMARK',
        'HSCD_APRVSTAT',
        'HSCD_LASTAPPRV',
        'p_u_username'
    ];

    public function insw_reg() {
        return  $this->hasMany(INSWDataRegDet::class, 'ZID_HSCODE', 'HSCD_STXICD');
    }
}
