<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CeisaToken extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'ceisa_auth_token';

    protected $fillable = [
        'access_token',
        'refresh_token',
    ];
}
