<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalMobileGencode extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mbl_gencode';
    protected $fillable = [
        'MBLG_SETTYPE',
        'MBLG_SETVALUE',
        'MBLG_SETDESC',
        'created_by',
    ];
}
