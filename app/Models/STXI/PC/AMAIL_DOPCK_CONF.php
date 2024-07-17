<?php

namespace App\Models\STXI\PC;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AMAIL_DOPCK_CONF extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pc';
    protected $table = 'AMAIL_DOPCK_CONF';
    protected $fillable = [
        'AMDC_BSGRP',
        'AMDC_DELCD',
        'AMDC_EMAIL',
        'AMDC_EMAILTYPE',
    ];
}
