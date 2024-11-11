<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalAttachSet extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_attch_set';
    protected $fillable = [
        'aasd_id',
        'aats_name',
        'aats_method',
        'aats_host',
        'aats_header',
        'aats_param',
    ];
}
