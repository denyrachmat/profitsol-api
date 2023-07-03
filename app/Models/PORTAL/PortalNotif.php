<?php

namespace App\Models\PORTAL;

use App\Models\CMS\FormShareDet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalNotif extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'portal_notif_mstr';
    protected $fillable = [
        'p_u_username',
        'pnm_to_users',
        'pnm_title',
        'pnm_content',
        'pnm_action_url',
        'pnm_hash_id_location',
        'pnm_start_date',
        'pnm_end_date',
        'pnm_is_read',
    ];

    public function shared()
    {
        return $this->hasOne(FormShareDet::class, 'cfsd_gen_link', 'pnm_hash_id_location');
    }
}
