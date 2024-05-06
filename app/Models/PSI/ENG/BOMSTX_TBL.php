<?php

namespace App\Models\PSI\ENG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BOMSTX_TBL extends Model
{
    use HasFactory;
    const CREATED_AT = 'UPDDT';
    const UPDATED_AT = 'UPDDT';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $connection = 'sqlsrv_psi_eng';
    protected $table = 'BOMSTX_TBL';

    protected $fillable = [
        'MODEL_CODE',
        'MODEL_DESC',
        'REVISION',
        'MAIN_PART_CODE',
        'MAIN_SPTNO',
        'MAIN_MAKERNM',
        'MS_NO',
        'MODEL_QTY',
        'PART_QTY',
        'MAIN_PA_PERCENT',
        'PO_FAILURE',
        'KO_FAILURE',
        'DETAIL_REMARK',
        'CONSIDER_PO_MRP',
        'CONSIDER_KO_MRP',
        'PROCESS_CODE',
        'EPSON_ORG_PART',
        'EPSON_SPTNO',
        'EPSON_MAKERNM',
        'BOM_REMARK',
        'SUB',
        'SUB_SPTNO',
        'SUB_MAKERNM',
        'SUB_PA_PERCENT',
        'SUB1',
        'SUB1_SPTNO',
        'SUB1_SPTNO2',
        'SUB2',
        'SUB2_SPTNO',
        'SUB2_SPTNO2',
        'IEI_TEN_NO',
        'SEC_TEN_NO',
        'TEN_RECEIVE_DATE',
        'CHANGE_OVERVIEW',
        'STOCK_SGL',
        'STOCK_CPO',
        'TEN_UPDATE_DATE',
        'APPROVED',
        'APRVDT',
        'APRVBY',
        'UPDDT',
        'UPDDT_STOCK',
    ];
}
