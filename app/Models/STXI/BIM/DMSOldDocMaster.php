<?php

namespace App\Models\STXI\BIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DMSOldDocMaster extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_bim_old';
    protected $table = 'dms_doc_mstr';
}
