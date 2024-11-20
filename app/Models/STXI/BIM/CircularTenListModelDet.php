<?php

namespace App\Models\STXI\BIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularTenListModelDet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_bim';
    protected $table = 'CIRTEN_TENLIST_ITEM_DET';
    protected $fillable = [
        'CTT_ID',
        'CTID_ITEMCDOLD',
        'CTID_ITEMCDNEW',
    ];
}
