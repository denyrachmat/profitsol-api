<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalAttachHist extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_attch_hist_det';
    protected $fillable = [
        'amshd_id',
        'amaad_source',
        'amaad_filename',
        'amaad_path',
        'amaad_size',
        'amaad_dl_link',
        'amaad_signed_by',
        'amaad_signed_at',
        'amaad_signatures',
    ];

    protected $casts = [
        'amaad_signatures' => 'array',
        'amaad_signed_at' => 'datetime',
    ];
}
