<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalGencode extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'portal_gencode_mstr';
    protected $fillable = [
        'pgm_code',
        'pgm_value',
        'pgm_value2',
        'pgm_value3',
        'pgm_desc',
        'pgm_desc2',
        'pgm_desc3',
        'pgm_created_by',
        'pgm_parent',
    ];

    public function setPgmValue2Attribute($value)
    {
        $this->attributes['pgm_value2'] = (string) $value;
    }
}
