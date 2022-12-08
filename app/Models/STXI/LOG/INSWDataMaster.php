<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class INSWDataMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_DATA_MSTR';
    protected $fillable = [
        'ZID_HSCODE',
        'ZID_BAGIAN',
        'ZID_BAB',
        'ZID_HSPRNT',
        'ZID_HSPRNT_DESC_ID',
        'ZID_HSPRNT_DESC_EN',
        'ZID_HSPRNT_FRMT',
        'ZID_HSPRNT_FRMT_DESC_ID',
        'ZID_HSPRNT_FRMT_DESC_END',
        'ZID_MFN_BM',
        'ZID_MFN_PPN',
        'ZID_MFN_PPH',
        'ZID_KOND',
    ];
}
