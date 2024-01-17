<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BCMega extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_itinv';
    protected $table = 'BCMG_TBL';
    protected $fillable = [
        'BCMG_TYPE',
        'BCMG_BCDOCNO',
        'BCMG_BCDOCDT',
    ];
}
