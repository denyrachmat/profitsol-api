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
            'pud_country' => $request->country,
            'pud_states' => $request->province,
            'pud_district' => $request->district,
            'pud_subdistrict' => $request->subdistrict,
            'pud_addr1' => $request->detLoc,
            'pud_addr2' => '',
            'pud_id_type' => $request->IDType,
            'pud_birth_place' => $request->birthplace,
            'pud_birth_date' => $request->birthday,
            'pud_country_rsdn' => $request->countryCurrent,
            'pud_states_rsdn' => $request->provinceCurrent,
            'pud_district_rsdn' => $request->districtCurent,
            'pud_subdistrict_rsdn' => $request->subdistrictCurrent,
            'pud_addr1_rsdn' => $request->detLocCurrent,
            'pud_addr2_rsdn' => ''
        ];

        if ($request->has('educations') && count(json_decode($request->educations)) > 0) {
            # code...
        }

        if ($request->has('families') && count(json_decode($request->families)) > 0) {
            # code...
        }

        return json_decode($request->educations);

        return $dataReqConvertToDB;
        return PortalUserDet::create($request);
    }
}
