<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\PORTAL\UserDetRequest;

use App\Models\PORTAL\PortalUserDet;
use App\Models\PORTAL\PortalEduDet;
use App\Models\PORTAL\PortalFamDet;

class ProfilesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserDetRequest $request, $id)
    {
        // return 'masuk sini';
        $dataReqConvertToDB = [
            'u_username' => $id,
            'pud_id_card' => $request->IDNum,
            'pud_first_name' => $request->firstName,
            'pud_last_name' => $request->lastName,
            'pud_photo' => $request->ava,
            'pud_phone' => $request->phoneNum,
            'pud_country' => $request->country,
            'pud_states' => $request->province,
            'pud_cities' => $request->cities,
            'pud_district' => $request->district,
            'pud_subdistrict' => $request->subdistrict,
            'pud_addr1' => $request->detLoc,
            'pud_addr2' => '',
            'pud_id_type' => $request->IDType,
            'pud_birth_place' => $request->birthplace,
            'pud_birth_date' => $request->birthday,
            'pud_country_rsdn' => $request->countryCurrent,
            'pud_states_rsdn' => $request->provinceCurrent,
            'pud_cities_rsdn' => $request->citiesCurrent,
            'pud_district_rsdn' => $request->districtCurrent,
            'pud_subdistrict_rsdn' => $request->subdistrictCurrent,
            'pud_addr1_rsdn' => $request->detLocCurrent,
            'pud_addr2_rsdn' => ''
        ];

        // return $dataReqConvertToDB;

        $userDet = PortalUserDet::updateOrCreate(['u_username' => $id], $dataReqConvertToDB);

        $userEdu = null;
        $userFam = null;
        if ($request->has('educations') && count(json_decode($request->educations)) > 0) {
            $edu = json_decode($request->educations);
            // return $edu;
            PortalEduDet::where('u_username', $id)->delete();
            foreach ($edu as $key => $value) {
                PortalEduDet::create([
                    'u_username' => $id,
                    'pusd_level' => $value->sch_type,
                    'pusd_sch_name' => $value->sch_name,
                    'pusd_sch_majors' => $value->sch_major,
                    'pusd_sch_minors' => $value->sch_minor,
                    'pusd_sch_end' => $value->sch_grade_years,
                    'pusd_grade' => $value->sch_grade,
                    'pusd_sch_passed' => $value->sch_grade_years ? 1 : 0,
                ]);
            }
        }

        if ($request->has('families') && count(json_decode($request->families)) > 0) {
            $fam = json_decode($request->families);

            PortalFamDet::where('u_username', $id)->delete();
            foreach ($fam as $key => $value) {
                PortalFamDet::create([
                    'pufd_first_name' => $value->fam_f_name,
                    'pufd_last_name' => $value->fam_l_name,
                    'pufd_relation' => $value->fam_rel
                ]);
            }
        }

        return [
            'status' => true,
            'data' => [
                'detail' => $userDet,
                'edu' => $userEdu,
                'fam' => $userFam
            ]
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
