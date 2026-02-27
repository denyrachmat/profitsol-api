<?php

namespace App\Models\STXI\PU;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartInfoChange extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection = 'sqlsrv_pu';
    protected $table = 'PRTINFOCHNG';

    protected $fillable = [
        'APPROVED_DT',
        'APPROVED_USR',
        'PIC_STATUS'
    ];
}
