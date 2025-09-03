<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;

use App\Http\Requests\PORTAL\UserDetRequest;

use App\Models\PORTAL\PortalUserDet;
use App\Models\PORTAL\PortalEduDet;
use App\Models\PORTAL\PortalFamDet;
use App\Models\User;

class ProfilesController extends BaseController
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
    public function update(UserDetRequest $request, $idDet)
    {
        // return 'masuk sini';
        // $dataReqConvertToDB = [
        //     'u_username' => $id,
        //     'pud_id_card' => $request->IDNum,
        //     'pud_first_name' => $request->firstName,
        //     'pud_last_name' => $request->lastName,
        //     'pud_photo' => $request->ava,
        //     'pud_phone' => $request->phoneNum,
        //     'pud_country' => $request->country,
        //     'pud_states' => $request->province,
        //     'pud_cities' => $request->cities,
        //     'pud_district' => $request->district,
        //     'pud_subdistrict' => $request->subdistrict,
        //     'pud_addr1' => $request->detLoc,
        //     'pud_addr2' => '',
        //     'pud_id_type' => $request->IDType,
        //     'pud_birth_place' => $request->birthplace,
        //     'pud_birth_date' => $request->birthday,
        //     'pud_country_rsdn' => $request->countryCurrent,
        //     'pud_states_rsdn' => $request->provinceCurrent,
        //     'pud_cities_rsdn' => $request->citiesCurrent,
        //     'pud_district_rsdn' => $request->districtCurrent,
        //     'pud_subdistrict_rsdn' => $request->subdistrictCurrent,
        //     'pud_addr1_rsdn' => $request->detLocCurrent,
        //     'pud_addr2_rsdn' => ''
        // ];

        $id = base64_decode($idDet);

        if (isset($request->form['email'])) {
            $users = User::updateOrCreate(['username' => $id], [
                'email' => $request->form['email'],
                'email_verified_at' => $request->form['email_verified_at'],
            ]);
        }

        if (isset($request->form['is_mobileacc'])) {
            $users = User::updateOrCreate(['username' => $id], [
                'is_mobileacc' => $request->form['is_mobileacc'],
            ]);
        }

        $userDet = PortalUserDet::updateOrCreate(['u_username' => $id], $request->form);

        $userEdu = [];
        $userFam = [];
        if ($request->has('educations') && count($request->educations) > 0) {
            $edu = $request->educations;
            // return $edu;
            // PortalEduDet::where('u_username', $id)->delete();
            foreach ($edu as $key => $value) {
                $userEdu[] = PortalEduDet::updateOrCreate([
                    'u_username' => $id,
                    'pusd_level' => $value['pusd_level'],
                ], [
                    'u_username' => $id,
                    'pusd_level' => $value['pusd_level'],
                    'pusd_sch_name' => $value['pusd_sch_name'],
                    'pusd_sch_majors' => $value['pusd_sch_majors'],
                    'pusd_sch_minors' => $value['pusd_sch_minors'],
                    'pusd_sch_end' => $value['pusd_sch_end'],
                    'pusd_grade' => $value['pusd_grade'],
                    'pusd_sch_passed' => $value['pusd_sch_passed'] ? 1 : 0,
                ]);
            }
        }

        if ($request->has('families') && count($request->families) > 0) {
            $fam = $request->families;

            foreach ($fam as $key => $value) {
                $userFam[] = PortalFamDet::updateOrcreate([
                    'u_username' => $id,
                    'pufd_relation' => $value['pufd_relation'],
                    'pufd_phone' => $value['pufd_phone'],
                ], [
                    'u_username' => $id,
                    'pufd_phone' => $value['pufd_phone'],
                    'pufd_first_name' => $value['pufd_first_name'],
                    'pufd_last_name' => $value['pufd_last_name'],
                    'pufd_birthday' => $value['pufd_birthday'],
                    'pufd_relation' => $value['pufd_relation']
                ]);
            }
        }

        return $this->handleResponse([
            'detail' => $userDet,
            'edu' => $userEdu,
            'fam' => $userFam
        ], 'Data updated !');
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
