<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRInc extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_wis';
    protected $table = 'QR_INC_TBL';
    protected $primaryKey = 'ID';
    protected $keyType = 'string';
    public $timestamps = false;
}
