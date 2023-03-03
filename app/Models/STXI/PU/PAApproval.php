<?php

namespace App\Models\STXI\PU;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PAApproval extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection = 'sqlsrv_pu';
    protected $table = 'PAINS_PRTCHG';

    protected $fillable = [
        'ACKG_DIR_DT',
        'ACKG_DIR'
    ];
}
