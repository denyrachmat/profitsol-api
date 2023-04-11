<?php

namespace App\Models\STXI\BIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularTenPathHtm extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_bim';
    protected $table = 'CIRTEN_HTM_READ';
    protected $fillable = [
        'CHR_NMPROP',
        'CHR_XPATH',
        'CHR_RETURN',
    ];
}
