<?php

namespace App\Models\STXI\PC;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BPSM_MSTR extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pc';
    protected $table = 'BOM_PA100_SYNC_MSTR';
    protected $fillable = [
        'BPSM_ITMCD',
        'BPSM_MDLCD',
        'BPSM_REV',
        'BPSM_STAT',
        'BPSM_REMARKS',
        'BPSM_RUNTIME',
    ];

    public function scopeNoLock($query)
{
    return $query->from(DB::raw(self::getTable() . ' with (nolock)'));
}
}
