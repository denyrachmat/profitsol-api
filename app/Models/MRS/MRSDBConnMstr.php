<?php

namespace App\Models\MRS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MRSDBConnMstr extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_mrs';
    protected $table = 'mrs_db_mstr';

    protected $fillable = [
        'p_u_username',
        'mdm_host',
        'mdm_name',
        'mdm_username',
        'mdm_password',
    ];
}
