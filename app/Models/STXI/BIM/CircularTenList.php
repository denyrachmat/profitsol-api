<?php

namespace App\Models\STXI\BIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularTenList extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_bim';
    protected $table = 'CIRTEN_TENLIST_TBL';
    protected $fillable = [
        'CTT_SECTENNO',
        'CTT_IEITENNO',
        'CTT_EMLDT',
        'CTT_EXCUPDT',
        'CTT_ITMUPDT',
        'CTT_BOMUPDT',
        'CTT_SUBJECT',
    ];

    public function models() {
        return $this->hasMany(CircularTenListModelDet::class, 'CTT_ID', 'id');
    }
}
