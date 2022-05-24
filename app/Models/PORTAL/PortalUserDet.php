<?php

namespace App\Models\PORTAL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalUserDet extends Model
{
    use HasFactory;
    protected $table = 'portal_users_det';
    protected $fillable = [
        'u_username',
        'pud_is_verified',
        'pud_is_active',
        'pud_first_name',
        'pud_last_name',
        'pud_id_card',
        'pud_photo',
        'pud_country',
        'pud_states',
        'pud_district',
        'pud_subdistrict',
        'pud_addr1',
        'pud_addr2',
        'pud_id_type',
        'pud_birth_place',
        'pud_birth_date',
        'pud_country_rsdn',
        'pud_district_rsdn',
        'pud_subdistrict_rsdn',
        'pud_addr1_rsdn',
        'pud_addr2_rsdn'
    ];
}
