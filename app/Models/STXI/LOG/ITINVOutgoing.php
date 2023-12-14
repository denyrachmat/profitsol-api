<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ITINVOutgoing extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_itinv';
    protected $table = 'CR2_OUT_CR8';
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
        'INVNO',
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
        'BC23DOCNO',
        'BC23DOCDT',
        'CUSNM',
        'WMSLOC',
        'HSCODE',
        'LUPDT',
        'BC33DOCNO',
        'BC33DOCDT',
        'BC33EXBCTYPE',
        'BC33EXBCDOCNO',
        'BC33EXBCDOCDT',
        'BC23BCTYPE',
    ];
}
