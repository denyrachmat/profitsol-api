<?php

namespace App\Models\STXI\LOG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class INSWDataRulesMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_log';
    protected $table = 'Z_INTR_RULES_MSTR';

    protected $fillable = [
        'ZIRM_NO',
        'ZIRM_TYPE',
        'ZIRM_ISSDT',
        'ZIRM_FILE',
        'ZIRM_INSTANCE',
        'ZIRM_TITLEHEAD',
        'ZIRM_TITLE',
        'ZIRM_STARTDT',
        'ZIRM_RULEDT',
    ];
}
