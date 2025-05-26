<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ITINVIncoming extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $connection = 'sqlsrv_itinv';
    protected $table = 'CR1_INC_CR7';
    const CREATED_AT = 'LUPDT';
    const UPDATED_AT = 'LUPDT';

    protected $fillable = [
        'LOCCD',
        'BCTYPE',
        'BCDOCNO',
        'BCDOCDT',
        'BSGRP',
        'DOCCD',
        'DOCNO',
        'HHEINVNO',
        'ISUDT',
        'ITMCD',
        'ITMD1',
        'SPTNO',
        'UOM',
        'TTLQTY',
        'CURCD',
        'PRICE',
        'TTLAMOUNT',
        'TAXINV',
        'SUPNM',
        'PENGIRIM',
        'WMSLOC',
        'HSCODE',
        'LUPDT',
    ];
    public function scopeNoLock($query)
    {
        return $query->from(DB::raw(self::getTable() . ' with (nolock)'));
    }
}
