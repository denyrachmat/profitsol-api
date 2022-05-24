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
        return $request;
        return PortalUserDet::create($request);
    }
}
