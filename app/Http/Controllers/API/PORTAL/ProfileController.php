<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\Storage;
use App\Models\PORTAL\PortalUserDet;

use App\Http\Requests\PORTAL\UserDetRequest;

class ProfileController extends BaseController
{
    public function getCountryList()
    {
        $data = Storage::disk('public')->get('countries+states+cities.json');

        return $data;
    }

    public function store(UserDetRequest $request) {
        $dataReqConvertToDB = [
            'u_username' => $request->header('Username'),
            'pud_id_card' => $request->IDNum,
            'pud_photo' => $request->ava,
            'pud_country' => '',
            'pud_states' => '',
            'pud_district' => '',
            'pud_subdistrict' => '',
            'pud_addr1' => '',
            'pud_addr2' => '',
            'pud_id_type' => '',
            'pud_birth_place' => '',
            'pud_birth_date' => '',
            'pud_country_rsdn' => '',
            'pud_district_rsdn' => '',
            'pud_subdistrict_rsdn' => '',
            'pud_addr1_rsdn' => '',
            'pud_addr2_rsdn' => ''
        ];
        return $request;
        return PortalUserDet::create($request);
    }
}
