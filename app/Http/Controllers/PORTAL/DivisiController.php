<?php

namespace App\Http\Controllers\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PORTAL\DivisisPortal;

class DivisiController extends Controller
{
    public function index($div = null)
    {
        if (empty($div)) {
            return DivisisPortal::where('ROLE_ID','not like', '%ROOT%')->orderBy('division_name')->get()->toArray();
        } else {
            return DivisisPortal::where('ROLE_ID','not like', '%ROOT%')->where('id',$div)->orderBy('division_name')->first()->toArray();
        }
    }
}
