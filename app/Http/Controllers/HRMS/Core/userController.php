<?php

namespace App\Http\Controllers\HRMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRMS\Auth\UserMaster;

class userController extends Controller
{
    public function index($username = '')
    {
        if ($username) {
            return UserMaster::with('occ.division')->where('username', $username)->first();
        } else {
            return UserMaster::with('occ.division')->get();
        }
        
    }
}
