<?php

namespace App\Models\AMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalDocSignBox extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_ams';
    protected $table = 'ams_apprv_docsign_boxes';

    protected $fillable = [
        'amsm_id',
        'amsmd_id',
        'dsbx_page_no',
        'dsbx_x',
        'dsbx_y',
        'dsbx_width',
        'dsbx_height',
        'dsbx_label',
    ];

    public function master()
    {
        return $this->belongsTo(ApprovalMaster::class, 'amsm_id', 'id');
    }

    public function mapdet()
    {
        return $this->belongsTo(ApprovalMapDetail::class, 'amsmd_id', 'id');
    }
}
