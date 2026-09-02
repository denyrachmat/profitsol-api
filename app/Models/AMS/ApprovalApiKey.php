<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalApiKey extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_api_keys';

    protected $fillable = [
        'p_u_username',
        'ak_name',
        'ak_key_hash',
        'ak_allowed_amsm_ids',
        'ak_active',
    ];

    protected $casts = [
        'ak_allowed_amsm_ids' => 'array',
        'ak_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('ak_active', true);
    }
}
