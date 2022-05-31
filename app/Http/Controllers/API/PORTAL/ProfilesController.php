<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\PORTAL\UserDetRequest;

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
        $dataReqConvertToDB = [
            'u_username' => $id,
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
