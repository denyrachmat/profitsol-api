<?php

namespace App\Http\Controllers\DMS\Core;

const TEMPIMGLOC = 'tempimg.png';

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DMS\Core\UploadDocsRequest;
use App\Models\DMS\Auth\UsersMaster;
use App\Models\DMS\Core\ApprovalHist;
use App\Models\DMS\Core\DocsMaster;
use App\Models\DMS\Core\VerMaster;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use PDF;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\DocApprovalSet;
use App\Models\DMS\Core\ApprovalNotification;
// use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use setasign\Fpdi\Fpdi;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\App;

use App\Jobs\DMS\UpdatedDocsEmailJobs;

class DocsManageController extends Controller
{
    public function getfiles($user, $idfolder = null)
    {
        if (empty($idfolder)) {
            return DocsMaster::where('doc_author', $user)
                ->with(['users','apprvhist'])
                ->with(['currentversion' => function($q){
                    $q->with(['doc.apprvhist']);
                    $q->with('prevVersion.doc.apprvhist');
                }])
                ->doesnthave('version')
                // ->doesnthave('docHist')
                // ->doesnthave('cirten')
                // ->where('doc_lapprv_flag', '<>', '1')
                ->get();
        } else {
            return DocsMaster::where('doc_path', $idfolder)
                ->where('doc_author', $user)
                ->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version')->get();
        }
    }

    public function uploadDocument(Request $req, $iddoc = null)
    {
        if (empty(DocsMaster::where('doc_real_name', 'like', '%' . $req->file->getClientOriginalName() . '%')->first())) {
            //Jika nama file tidak ada yang sama di server

            if ($iddoc !== null) {
                //Jika disisipkan id document berarti akan update version
                $cekhistory = ApprovalHist::where('apprv_hist_doc', $iddoc)->orderBy('created_at', 'desc')->first();

                if (empty($cekhistory) || $cekhistory['apprv_hist_status'] !== '2') {
                    //Jika tidak ada reject dari approver maka tidak boleh update
                    return response('Before the revision update, there must be a revision request from the approver !', 402);
                } else {
                    // Jika ada reject dari approver
                    $id = $this->storedocument($req);

                    $cekversionexist = VerMaster::where('ver_docnm', $iddoc)->orderBy('ver_code', 'desc')->first();
                    $nextversion = intval($cekversionexist['ver_code']) + 1;

                    VerMaster::create([
                        'id' => Str::random(50),
                        'ver_docnm' => $id,
                        'ver_docloc' => $iddoc == null ? '0' : $iddoc,
                        'ver_code' => $iddoc == null ? '0' : $nextversion,
                        'ver_comment' => $iddoc == null ? 'First Upload' : "Updated version document from version " . $cekversionexist['ver_code'] . " to " . (string) $nextversion,
                    ]);

                    // Update semua dokumen terkait update di approval hist
                    ApprovalHist::where('apprv_hist_doc', $iddoc)->update([
                        'apprv_hist_doc' => $id,
                        'apprv_hist_vwtime' => NULL
                    ]);

                    // delete history untuk meminta ulang approval di approval hist
                    $cekhist = ApprovalHist::where('apprv_hist_doc', $id)->where('apprv_hist_status', '2')->first();

                    $cekhistafterupdate = ApprovalMaster::where('apprv_id', $cekhist->id_approval)->where('apprv_level',strval(intval($cekhist->approver_level)))->get();
                    
                    foreach ($cekhistafterupdate as $key => $value) {
                        $this->emailsender($value->apprv_approver, $id);
                    }

                    ApprovalNotification::where('apprv_hist_to_id', $cekhist->id)->update([
                        'apprv_hist_to_id' => NULL,
                        'apprv_read_flag' => NULL
                    ]);

                    ApprovalNotification::where('apprv_hist_from_id', $cekhist->id)->delete();
        
                    ApprovalHist::where('apprv_hist_doc', $id)->where('apprv_hist_status', '2')->delete();

                    DocsMaster::where('doc_id', $id)->update([
                        'doc_stat_flag' => '1',
                        'doc_lapprv_flag' => '1'
                    ]);                    
                }
            } else {
                $id = $this->storedocument($req);

                VerMaster::create([
                    'id' => Str::random(50),
                    'ver_docnm' => $id,
                    'ver_docloc' => '0',
                    'ver_code' => '0',
                    'ver_comment' => 'First Upload',
                ]);

                // DocApprovalSet::create([
                //     'doc_id' => $id,
                //     'approval_id' => $req->approval_mapping
                // ]);

                return 'success';
            }
        } else {
            return response('File exists, please update your doc version !', 402);
        }
    }

    public function resendrejecteddoc($id)
    {
        $cekhist = ApprovalHist::where('apprv_hist_doc', $id)->where('apprv_hist_status', '2')->first();
        
        ApprovalNotification::where('apprv_hist_to_id', $cekhist->id)->update([
            'apprv_hist_to_id' => NULL,
            'apprv_read_flag' => NULL
        ]);

        ApprovalNotification::where('apprv_hist_from_id', $cekhist->id)->delete();

        ApprovalHist::where('apprv_hist_doc', $id)->where('apprv_hist_status', '2')->delete();    

        return 'success';
    }

    public function storedocument($req)
    {
        $nama_file = uniqid('DMSDOC_') . rand() . '.' . $req->file->getClientOriginalExtension();
        $rootfolder = 'Uploaded Docs/DMS/';
        $folderstore = empty($req->folder_id) ? $rootfolder . $req->username . '/' : $rootfolder . $req->username . '/' . $req->folder_name . '/';
        $req->file->storeAs($folderstore, $nama_file);
        $id_document = Str::random(50);

        if (Storage::exists($folderstore . $nama_file)) {
            DocsMaster::create([
                'doc_id' => $id_document,
                'doc_name' => $nama_file,
                'doc_path' => empty($req->folder_id) ? '0' : $req->folder_id,
                'doc_real_path' => $folderstore,
                'doc_author' => $req->username,
                'doc_real_name' => $req->file->getClientOriginalName(),
                'doc_size' => $req->file->getSize(),
                'doc_stat_flag' => '0'
            ]);
        }

        return $id_document;
    }

    public function showpdf($user, $topdf, $full = null, $showapprv = null)
    {
        $getpath = DocsMaster::where('doc_id', $topdf)->first();
        $apprvdochist = ApprovalHist::where('apprv_hist_doc', $topdf)->first();
        if ($full === null) {
            if (!empty($apprvdochist)) {
                if ($showapprv == null) {
                    logger(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name']);
                    $pdfnya = $this->ApproveDoc($topdf, $apprvdochist->id_approval);
                } else {
                    // logger(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name']);
                    $getpdf = $this->checkExtension(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], explode('.', $getpath['doc_name'])[0], $full);
                    $pdfnya = File::get($getpdf);
                }

                return response()->json([
                    'pdf' => base64_encode($pdfnya),
                ]);
            } else {
                logger(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name']);
                $filePDF = $this->checkExtension(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], explode('.', $getpath['doc_name'])[0], $full);

                return response()->json([
                    'pdf' => base64_encode(File::get($filePDF)),
                ]);
            }
        } else {
            if ($full == 'full') {
                $filePDF = $this->checkExtension(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], explode('.', $getpath['doc_name'])[0], $full);
                if (empty($apprvdochist)) {
                    logger($topdf);
                    return File::get($filePDF);
                } else {
                    if ($showapprv == null) {
                        return $this->ApproveDoc($topdf, $apprvdochist->id_approval);
                    } else {
                        return File::get($filePDF);
                    }
                }
            } else {
                $filePDF = $this->checkExtension(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], explode('.', $getpath['doc_name'])[0], $full);

                $headers = array(
                    'Content-Description: File Transfer',
                    'Content-Type: application/octet-stream',
                    'Content-Disposition: attachment; filename="' . $getpath['doc_real_name'] . '"',
                );
                if (empty($apprvdochist)) {
                    return response()->file(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], $headers);
                    return File::get($filePDF);
                } else {
                    if ($full == 'original') {
                        return response()->file(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], $headers);
                    } else {
                        if ($showapprv == null) {
                            return $this->ApproveDoc($topdf, $apprvdochist->id_approval);
                        } else {
                            return response()->file(env('ROOT_DMS_UPLOADED') . $getpath['doc_real_path'] . $getpath['doc_name'], $headers);
                        }
                    }
                }
            }
        }
    }

    public function showpdforiginal($topdf, $showapprv = null, $full = null)
    {
        logger([null, $topdf, $full, $showapprv]);
        return $this->showpdf(null, $topdf, $full, $showapprv);
    }

    public function checkExtension($file, $filename, $met)
    {
        $cekExtension = File::extension($file);
        if ($met !== null) {
            return $file;
        } else {
            if ($cekExtension == 'pdf') {
                return $file;
            } elseif ($cekExtension == 'xls' || $cekExtension == 'xlsx') {
                return $file;
                return $this->convertExcelToPdf($file, $filename);
            }
        }
    }

    public function convertExcelToPdf($file, $filename)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf::class;
        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf($spreadsheet);
        $writer->save($filename . ".pdf");

        return $filename . '.pdf';
    }

    public function deleteDocument($id)
    {
        $cek = DocsMaster::where('doc_id', $id)->doesnthave('apprvhist')->first();
        if (!empty($cek)) {
            $hasil = $cek;
            if (Storage::exists($hasil['doc_real_path'] . $hasil['doc_name'])) {
                Storage::disk('data_folder')->delete($hasil['doc_real_path'] . $hasil['doc_name']);
            } else {
                return response([
                    'message' => "The given data was invalid.",
                    'errors' => [
                        'menu_id' => ["Error: File not found, cannot delete it!!"]
                    ]
                ], 422);
            }
            // Storage::delete($hasil['doc_real_path'] . $hasil['doc_name']);

            $cek->delete();
            VerMaster::where('ver_docnm', $id)->delete();

            return 'success';
        } else {
            return response([
                'message' => "The given data was invalid.",
                'errors' => [
                    'menu_id' => ["Error: You cant delete it because the document is already sent to get the approval!!"]
                ]
            ], 422);
        }
    }

    public function ApproveDoc($iddoc, $author)
    {
        $getpath = DocsMaster::where('doc_id', $iddoc)->first();
        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile('D:/data/' . $getpath['doc_real_path'] . $getpath['doc_name']);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            // get the size of the imported page
            $size = $pdf->getTemplateSize($templateId);

            // create a page (landscape or portrait depending on the imported page size)
            if ($size[0] > $size[1]) {
                $pdf->AddPage('L', array($size[0], $size[1]));
            } else {
                $pdf->AddPage('P', array($size[0], $size[1]));
            }

            $arrApprover = ApprovalMaster::where('apprv_id', $author)->orderBy('apprv_level', 'desc')->first();
            $lastApprove = ApprovalHist::where('apprv_hist_doc', $iddoc)->orderBy('approver_level', 'desc')->first();

            if ($arrApprover['apprv_level'] == $lastApprove['approver_level']) {
                $pdf->SetFont('Arial', 'B', 30);
                $pdf->SetTextColor(255, 192, 203);
                $pdf->Text(($pdf->GetPageWidth() / 2) - $pdf->GetStringWidth('A P P R O V E D') / 2, $pdf->GetPageHeight() / 1.1, 'A P P R O V E D', 45);
            } else {
                if ($lastApprove->apprv_hist_status == 1) {
                    $pdf->SetFont('Arial', 'B', 30);
                    $pdf->SetTextColor(255, 192, 203);
                    $pdf->Text(($pdf->GetPageWidth() / 2) - $pdf->GetStringWidth('D R A F T') / 2, $pdf->GetPageHeight() / 1.1, 'D R A F T', 45);
                } else {
                    $pdf->SetFont('Arial', 'B', 30);
                    $pdf->SetTextColor(255, 192, 203);
                    $pdf->Text(($pdf->GetPageWidth() / 2) - $pdf->GetStringWidth('R E J E C T E D') / 2, $pdf->GetPageHeight() / 1.1, 'R E J E C T E D', 45);
                }
            }

            $pdf->useTemplate($templateId);

            $pdf->SetFont('Helvetica');
            $pdf->SetXY(5, 5);

            $pdf->SetFont('Helvetica', '', 12);

            $pdf->SetTextColor(0, 0, 0);

            if ($pageNo === $pageCount) {
                if ($size[0] > $size[1]) {
                    $pdf->AddPage('L', array($size[0], $size[1]));
                } else {
                    $pdf->AddPage('P', array($size[0], $size[1]));
                }

                $pdf->SetXY(10, 15);

                $pdf->Write(0, 'Approval Status');

                // $cekapprvset = ApprovalMaster::where('apprv_id', $author)->with('user')->get()->toArray();
                $cekhist = ApprovalHist::where('apprv_hist_doc', $iddoc)
                    ->where('apprv_parent', '<>', '0')
                    ->with('users.signature')
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($cekhist as $key => $value) {
                    $pdf->SetXY(10, (1 + $key) * 20);

                    // $cellWidth = $pdf->GetStringWidth($value['apprv_hist_comment']) < 100 ? 100 : $pdf->GetStringWidth($value['apprv_hist_comment']) + 25;
                    $cellWidth = $pdf->GetPageWidth() / 1.4;

                    $pdf->Cell(40, 5, ' ', 'LTR', 0, 'L', 0);   // empty cell with left,top, and right borders
                    if ($value['apprv_hist_status'] == '1') {
                        $pdf->Cell($cellWidth, 5, 'Digitally signed by @' . $value['users']['username'], 'LTR', 0, 'L', 0);
                    } else {
                        $pdf->Cell($cellWidth, 5, 'Rejected by @' . $value['users']['username'], 'LTR', 0, 'L', 0);
                    }

                    $pdf->Ln();

                    $pdf->SetFont('Times', 'BIU');
                    if ($value['apprv_hist_status'] == '1') {
                        $pdf->Cell(40, 5, '', 'LR', 0, 'C', 0);  // cell with left and right borders                       
                        
                        if ($value['users']->signature !== null && $value['users']->signature->image_signature !== '') {
                            $datauri = base64_decode($value['users']->signature->image_signature);
                            $img  = explode(',',$datauri,2);
                            $pic = 'data://text/plain;base64,'. $img[1];
                            
                            $pdf->Image($pic, 20, (1 + $key) * 20 - 2,20,20,'png');
                        }
                    } else {
                        $pdf->SetFont('Helvetica');
                        $pdf->SetTextColor(255, 0, 0);
                        $pdf->Cell(40, 5, 'REJECTED', 'LR', 0, 'C', 0);  // cell with left and right borders
                        $pdf->SetTextColor(0, 0, 0);
                    }
                    $pdf->SetFont('Helvetica');
                    $pdf->Cell(20, 5, 'Email', 'L', 0, 'L', 0);
                    $pdf->Cell($cellWidth - 20, 5, ': ' . $value['users']["email"], 'R', 0, 'L', 0);
                    // $pdf->Cell(50, 5, '[ x ] che2', 'LR', 0, 'L', 0);

                    $pdf->Ln();
                    $pdf->Cell(40, 5, '', 'LR', 0, 'LR', 0);   // empty cell with left,bottom, and right borders
                    $pdf->Cell(20, 5, 'Reason', 'L', 0, 'L', 0);
                    $pdf->Cell($cellWidth - 20, 5, ': ' . $value['apprv_hist_comment'], 'R', 0, 'L', 0);
                    // $pdf->Cell(50, 5, '[ o ] def4', 'LRB', 0, 'L', 0);

                    $pdf->Ln();

                    $pdf->SetFont('Times', 'BIU');
                    // $pdf->Cell(40, 5, $value['users']["first_name"] . ' ' . $value['users']["last_name"], 'LR', 0, 'C', 0);
                    $pdf->Cell(40, 5, $value['apprv_hist_status'] == '1' ? $value['users']["first_name"] . ' ' . $value['users']["last_name"] : '', 'LBR', 0, 'C', 0);   // empty cell with left,bottom, and right borders
                    $pdf->SetFont('Helvetica');
                    $pdf->Cell(20, 5, 'Date', 'LB', 0, 'L', 0);
                    $pdf->Cell($cellWidth - 20, 5, ': ' . $value['created_at'], 'BR', 0, 'L', 0);
                    // $pdf->Cell(50, 5, '[ o ] def4', 'LRB', 0, 'L', 0);

                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->Ln();
                }
            }
        }

        return $pdf->Output("", "S");

        $ceklastapprover = ApprovalMaster::where('apprv_id', $author)->orderBy('apprv_level', 'desc')->first();
        $cekjumlahyangapprove = ApprovalHist::where('apprv_hist_doc', $iddoc)->where('apprv_parent', '<>', '0')->orderBy('approver_level', 'desc')->first();

        return $this->waterMark($pdf->Output("", "S"), $ceklastapprover, $cekjumlahyangapprove);
    }

    public function waterMark($pdf, $arrApprover, $lastApprove)
    {
        $mpdf = new \Mpdf\Mpdf();
        if ($arrApprover['apprv_level'] == $lastApprove['approver_level']) {
            $katakata = 'APPROVED';
        } else {
            if ($lastApprove->apprv_hist_status == 1) {
                $katakata = 'DRAFT';
            } else {
                $katakata = 'REJECTED';
            }
        }
        $pagecount = $mpdf->SetSourceFile('[path]');

        $tplId = $mpdf->ImportPage(1);
        $size = $mpdf->getTemplateSize($tplId);
        $mpdf->SetSourceFile($pdf);

        //Write into the instance and output it
        for ($i = 1; $i <= $pagecount; $i++) {
            $tplId = $mpdf->ImportPage($i);
            $mpdf->addPage();
            $mpdf->UseTemplate($tplId);
            $mpdf->SetWatermarkText($katakata);
            $mpdf->showWatermarkText = true;
        }
        $mpdf->Output();
    }

    public function printcover(Request $r)
    {
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->loadHTML($r->html);

        return  base64_encode($pdf->inline());

        // PDF::SetTitle($r->title);
        // PDF::AddPage();
        // PDF::writeHTML($r->html, true, false, false, false, '');

        // return base64_encode(PDF::Output($r->title.'.pdf', 'S'));
    }

    public function updateflagapprvdoc($iddoc, $flag)
    {
        DocsMaster::where('doc_id', $iddoc)->update([
            'doc_lapprv_flag' => $flag
        ]);

        return 'success';
    }

    public function parsingdocver($arr, $passdata)
    {
        if ($arr->prevVersion !== null) {
            return $this->parsingdocver($arr->prevVersion, array_merge($passdata, [$arr]));
        } else {                
            return array_merge($passdata, [$arr]);
        }
    }

    public function emailsender($user, $id_docnew)
    {
        $user = UsersMaster::where('username', $user)->first();
        $datanya = VerMaster::where('ver_docnm',$id_docnew)
            ->with(['doc.apprvhist'])
            ->with('prevVersion.doc.apprvhist')
            ->first();

        try {
            logger([$user, $this->parsingdocver($datanya, [])]);
            $insertJob = (new UpdatedDocsEmailJobs($user, $this->parsingdocver($datanya, [])));

            dispatch($insertJob);            

            return 'Email success';
        } catch (\Exception $th) {
            return 'Email error';
        }
    }

    public function sharedoc($iddoc)
    {
        $cekdata = DocsMaster::where('doc_id',$iddoc)->first();
        
        return DocsMaster::where('doc_id',$iddoc)->update([
            'doc_stat_flag' => $cekdata->doc_stat_flag == '3' ? '2' : '3'
        ]);
    }
}
