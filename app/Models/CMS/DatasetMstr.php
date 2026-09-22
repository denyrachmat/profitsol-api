<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatasetMstr extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sqlsrv_cms';
    protected $table = 'cms_dataset_mstr';

    protected $fillable = [
        'p_u_username',
        'cds_code',
        'cds_name',
        'cds_desc',
        'cds_type',
        'cds_connection',
        'cds_query',
        'cds_endpoint',
        'cds_method',
        'cds_payload',
        'cds_headers',
        'cds_params_schema',
        'cds_cache_ttl',
        'cds_roles',
        'cds_status',
    ];
}
