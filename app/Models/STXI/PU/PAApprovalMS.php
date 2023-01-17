<?php

namespace App\Models\STXI\PU;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PAApprovalMS extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pu';
    protected $table = 'PAINS_PRTCHG_MS';

    protected $fillable = [
        'ACKG_DIR_DT',
        'ACKG_DIR'
    ];
}
