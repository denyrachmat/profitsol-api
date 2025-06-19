<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalRPAHist extends Model
{
    use HasFactory;
    protected $table = 'portal_rpa_hist';
    protected $fillable = [
        'prh_prmid',
        'prh_robotnm',
        'prh_command',
        'prh_flag',
        'prh_result',
        'prh_cfaud_id',
        'prh_cfaud_batch_id'
    ];
    protected $casts = [
        'prh_flag' => 'integer',
        'prh_cfaud_id' => 'integer',
        'prh_cfaud_batch_id' => 'integer',
    ];
}
