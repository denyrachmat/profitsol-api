<?php
namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\File;

class LogicalController extends Controller{
 
    public function saveLogical(Request $req) {

    }

    public function testingOCR() {
        $file = File::get(env('DMS_DOC_LOC').'text.png');
        return $file;
        $tesseract = new TesseractOCR();
        $tesseract->image($file);
        return $tesseract->run();
    }

}