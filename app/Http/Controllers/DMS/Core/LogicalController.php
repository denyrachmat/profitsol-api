<?php
namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\File;
use Spatie\PdfToText\Pdf;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;
use App\Models\DMS\Core\LogicMaster;
use App\Models\DMS\Core\ApprovalMaster;

class LogicalController extends Controller{
 
    public function saveLogical(Request $req) {
        $ceklastid = LogicMaster::latest('created_at')->first();
        if(empty($ceklastid)) 
            $id = 'LOG-'.date('ymd').'0001';
        else
            $id = 'LOG-'.date('ymd').sprintf("%04d", intval(substr($ceklastid->logic_id,-4)) + 1);
        
        foreach($req->data as $key => $value){
            foreach($value['approver'] as $keyApprover => $valueApprover) {
                foreach($value['data'] as $keyData => $valueData) {
                    $ceklastid = LogicMaster::where('logic_id', $id)->latest('created_at')->first();
                    if(empty($ceklastid)) 
                        $idData = 'COND-'.date('ymd').'0001';
                    else
                        $idData = 'COND-'.date('ymd').sprintf("%04d", intval(substr($ceklastid->comp_group_id,-4)) + 1);
    
                    LogicMaster::create([
                        'logic_id' => $id,
                        'comp_group_id' => $idData,
                        'apprv_id' => $valueApprover,
                        'comp_name' => $valueData['components'],
                        'logic_cond' => $valueData['conditions'],
                        'logic_value' => $valueData['value'],
                        'logic_apprv_flag' => $valueData['action']['approve'],
                        'logic_notif_flag' => $valueData['action']['notif'],
                        'logic_only_flag' => $valueData['action']['only']
                    ]);
                }
            }
        }
    }

    public function testingOCR() {
        $file = env('ROOT_DMS_UPLOADED').'Uploaded Docs/DMS/susi/Circular Ten/DMSDOC_5ee9bd730695c1166715669.pdf';
        $to = 'C:/Windows/temp/DEN-OCR-'.Str::random(50).'txt';
        $process = new Process(['pdftotext', $file, $to]);
        $process->run();

        $spliString = preg_split('/\r\n|\r|\n/',File::get($to));

        return $spliString;
        $find = '\f';
        $hasil = [];
        foreach ($spliString as $key => $value) {
            if(preg_match("/\b$find\b/i", $value)){
                $hasil[] = $spliString[$key + 1];
            }
        }

        return $hasil;
        // return $file;
        // return $file->text();
        // $tesseract = new TesseractOCR();
        // $tesseract->image($file)
        // ->whitelist(range('A', 'Z'));
        // return $tesseract->run();
    }

    public function TesterCo()
    {
        return ApprovalMaster::where('apprv_id','APPRV200707001')->with('logic')->get();
    }

}