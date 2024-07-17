<?php

namespace App\Models\STXI\PC;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AMAIL_DOPCK_LOG extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pc';
    protected $table = 'AMAIL_DOPCK_LOG';
    protected $fillable = [
        'AMDL_LOCCD',
        'AMDL_BSGRP',
        'AMDL_DONO',
        'AMDL_DELCD',
        'AMDL_STAT',
    ];
}
