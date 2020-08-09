<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Core\Bio\UserPersonalDet;
use App\Models\HRMS\Core\Bio\UserEduDet;
use App\Models\HRMS\Auth\UserMaster;
use App\Models\HRMS\Core\Bio\UserExpDet;
use App\Models\HRMS\Core\Bio\UserChildrenDet;
use App\Models\HRMS\Core\Bio\UserEmergDet;

class PersonalController extends Controller
{
    public function storeBio(Request $r)
    {
        // return $r;
        if ($r->addressInformation) {
            if ($r->addressInformation['idcardinitlist']) {
                $idCard = $r->addressInformation['idcardinitlist'];
                UserPersonalDet::updateOrCreate(
                    [
                        'username' => $r->header('username')
                    ],
                    [
                        'user_nat' => $idCard['user_nat'],
                        'user_province1' => $idCard['user_province'],
                        'user_city1' => $idCard['user_city'],
                        'user_district1' => $idCard['user_district'],
                        'user_subdistrict1' => $idCard['user_subdistrict'],
                        'user_detaddr1' => $idCard['user_detaddr']
                    ]
                );
            }

            if ($r->addressInformation['currentinitlist']) {
                $current = $r->addressInformation['currentinitlist'];
                UserPersonalDet::updateOrCreate(
                    [
                        'username' => $r->header('username')
                    ],
                    [
                        'user_nat' => $current['user_nat'],
                        'user_province2' => $current['user_province'],
                        'user_city2' => $current['user_city'],
                        'user_district2' => $current['user_district'],
                        'user_subdistrict2' => $current['user_subdistrict'],
                        'user_detaddr2' => $current['user_detaddr']
                    ]
                );
            }
        }

        if ($r->personalInformation) {
            $personale = $r->personalInformation;
            UserPersonalDet::updateOrCreate(
                [
                    'username' => $r->header('username')
                ],
                [
                    'user_phone' => $personale['user_phone'],
                    'user_handphone' => $personale['user_handphone'],
                    'user_religion' => $personale['user_religion'],
                    'user_bloodtype' => $personale['user_bloodtype'],
                    'user_height' => $personale['user_height'],
                    'user_weight' => $personale['user_weight'],
                    'user_gender' => $personale['user_gender'],
                    'user_marital_status' => $personale['user_marital_status'],
                ]
            );
        }

        if ($r->educationInformation) {
            $education = $r->educationInformation;
            UserEduDet::where('username', $r->header('username'))->delete();
            foreach ($education as $key => $value) {
                UserEduDet::updateOrCreate(
                    [
                        'username' => $r->header('username'),
                        'user_edu_level' => $value['user_edu_level']
                    ],
                    [
                        'user_edu_level' => $value['user_edu_level'],
                        'user_edu_level_desc' => $value['user_edu_level_desc'],
                        'user_edu_sc_name' => $value['user_edu_sc_name'],
                        'user_edu_major' => $value['user_edu_major'],
                        'user_edu_city' => $value['user_edu_city'],
                        'user_edu_month_from' => $value['user_edu_month_from'],
                        'user_edu_year_from' => $value['user_edu_year_from'],
                        'user_edu_month_to' => $value['user_edu_month_to'],
                        'user_edu_year_to' => $value['user_edu_year_to'],
                    ]
                );
            }
        }

        if ($r->expInformation) {
            $exp = $r->expInformation;
            UserExpDet::where('username', $r->header('username'))->delete();
            foreach ($exp as $key => $value) {
                UserExpDet::updateOrCreate(
                    [
                        'username' => $r->header('username'),
                        'company_name' => $value['company_name']
                    ],
                    [
                        'username' => $r->header('username'),
                        'company_name' => $value['company_name'],
                        'company_address' => $value['company_address'],
                        'company_start_work' => $value['company_start_work'],
                        'company_end_work' => $value['company_end_work'],
                        'company_resign_reason' => $value['company_resign_reason'],
                    ]
                );
            }
        }

        if ($r->childrenInformation) {
            $child = $r->childrenInformation;
            UserChildrenDet::where('username', $r->header('username'))->delete();
            foreach ($child as $key => $value) {
                UserChildrenDet::updateOrCreate(
                    [
                        'username' => $r->header('username'),
                        'child_name' => $value['child_name']
                    ],
                    [
                        'username' => $r->header('username'),
                        'child_name' => $value['child_name'],
                        'child_birthdate' => $value['child_birthdate'],
                        'child_birthplace' => $value['child_birthplace'],
                        'child_gender' => $value['child_gender'],
                        'child_last_edu' => $value['child_last_edu'],
                    ]
                );
            }
        }

        if ($r->emergInformation) {
            $emerg = $r->emergInformation;
            UserEmergDet::where('username', $r->header('username'))->delete();
            foreach ($emerg as $key => $value) {
                UserEmergDet::updateOrCreate(
                    [
                        'username' => $r->header('username'),
                        'emerg_first_name' => $value['emerg_first_name'],
                        'emerg_last_name' => $value['emerg_last_name']
                    ],
                    [
                        'username' => $r->header('username'),
                        'emerg_first_name' => $value['emerg_first_name'],
                        'emerg_last_name' => $value['emerg_last_name'],
                        'emerg_email' => $value['emerg_email'],
                        'emerg_phone' => $value['emerg_phone'],
                        'emerg_handphone' => $value['emerg_handphone'],
                        'emerg_address' => $value['emerg_address'],
                        'emerg_relation' => $value['emerg_relation'],
                    ]
                );
            }
        }

        $user = UserMaster::where('username', $r->header('username'))->with(['roleMaster.mappingMenu' => function ($q) {
            $q->where('parent_menu_id', 0);
            $q->with('menu');
            $q->with(['childRoleMenu' => function ($qdet) {
                $qdet->with('menu');
            }]);
            $q->orderBy('menu_id');
        }])
            ->with(['personalDetail', 'educationDetail', 'childrenDetail', 'expDetail'])
            ->first();

        return $user;
    }
}
