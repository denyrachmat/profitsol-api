<?php

namespace App\Models\DMS\Auth;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CompositeKey;

class SignatureMaster extends Model
{
    use CompositeKey;

    protected $connection = 'sqlsrv_dms';
    protected $table = 'dms_signature_mstr';

    protected $primaryKey = [
        'username'
    ];

    protected $fillable = [
        'username',
        'image_signature'
    ];
}
