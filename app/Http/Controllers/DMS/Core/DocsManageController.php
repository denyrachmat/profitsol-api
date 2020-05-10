<?php

namespace App\Http\Controllers\DMS\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DMS\Core\UploadDocsRequest;
use App\Models\DMS\Core\ApprovalHist;
use App\Models\DMS\Core\DocsMaster;
use App\Models\DMS\Core\VerMaster;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use PDF;
use App\Models\DMS\Core\ApprovalMaster;
use App\Models\DMS\Core\DocApprovalSet;

class DocsManageController extends Controller
{
    public function getfiles($user, $idfolder = null)
    {
        if (empty($idfolder)) {
            return DocsMaster::where('doc_path', '0')
                ->where('doc_author', $user)
                ->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version')->get();
        } else {
            return DocsMaster::where('doc_path', $idfolder)
                ->where('doc_author', $user)
                ->with(['users', 'currentversion', 'apprvhist'])->doesnthave('version')->get();
        }
    }

    public function uploadDocument(UploadDocsRequest $req, $iddoc = null)
    {
        if (empty(DocsMaster::where('doc_real_name', 'like', '%' . $req->file->getClientOriginalName() . '%')->first())) {

            if ($iddoc !== null) {
                $cekhistory = ApprovalHist::where('apprv_hist_doc', $iddoc)->orderBy('created_at', 'desc')->first();

                if (empty($cekhistory) || $cekhistory['apprv_hist_status'] !== '2') {
                    return response('Before the revision update, there must be a revision request from the approver !', 402);
                } else {
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

                    DocApprovalSet::where('doc_id', $iddoc)->update([
                        'doc_id' => $id,
                    ]);

                    // Update semua dokumen terkait update di approval hist
                    ApprovalHist::where('apprv_hist_doc', $iddoc)->update([
                        'apprv_hist_doc' => $id,
                        'apprv_hist_vwtime' => NULL
                    ]);

                    // delete history untuk meminta ulang approval di approval hist
                    ApprovalHist::where('apprv_hist_doc', $id)->where('apprv_hist_status', '2')->orderBy('ver_code', 'desc')->delete();
                }
            } else {
                $id = $this->storedocument($req);
                $nama_file = uniqid('DMSDOC_') . rand() . '.' . $req->file->extension();
                $folderstore = empty($req->folder_id) ? env('DMS_DOC_LOC') . $req->username . '/' : env('DMS_DOC_LOC') . $req->username . '/' . $req->folder_name . '/';
                $req->file->storeAs($folderstore, $nama_file);

                VerMaster::create([
                    'id' => Str::random(50),
                    'ver_docnm' => $id,
                    'ver_docloc' => '0',
                    'ver_code' => '0',
                    'ver_comment' => 'First Upload',
                ]);

                DocApprovalSet::create([
                    'doc_id' => $id,
                    'approval_id' => $req->approval_mapping
                ]);

                return 'success';
            }
        } else {
            return response('File exists, please update your doc version !', 402);
        }
    }

    public function storedocument($req)
    {
        $nama_file = uniqid('DMSDOC_') . rand() . '.' . $req->file->extension();
        $folderstore = empty($req->folder_id) ? env('DMS_DOC_LOC') . $req->username . '/' : env('DMS_DOC_LOC') . $req->username . '/' . $req->folder_name . '/';
        $req->file->storeAs($folderstore, $nama_file);
        $id_document = Str::random(50);

        if (Storage::exists($folderstore . $nama_file)) {
            DocsMaster::create([
                'doc_id' => $id_document,
                'doc_name' => $nama_file,
                'doc_path' => empty($req->folder_id) ? '0' : $req->folder_id,
                'doc_real_path' => $folderstore,
                'doc_author' => $req->username,
                'doc_real_name' => $req->file->getClientOriginalName()
            ]);
        }

        return $id_document;
    }

    public function showpdf($user, $topdf, $full = null)
    {
        $getpath = DocsMaster::where('doc_id', $topdf)->first();
        $apprvdochist = ApprovalHist::where('apprv_hist_doc', $topdf)->first();
        if ($full === null) {
            if (!empty($apprvdochist)) {
                $pdfnya = $this->ApproveDoc($topdf, $apprvdochist->id_approval);
                // logger(base64_encode($pdfnya));
                return response()->json([
                    'pdf' => base64_encode($pdfnya),
                ]);
            } else {
                return response()->json([
                    'pdf' => base64_encode(File::get('D:/data/' . $getpath['doc_real_path'] . $getpath['doc_name'])),
                ]);
            }
        } else {
            if (empty($apprvdochist)) {
                return File::get('D:/data/' . $getpath['doc_real_path'] . $getpath['doc_name']);
            } else {
                return $this->ApproveDoc($topdf, $apprvdochist->id_approval);
            }
        }
    }

    public function deleteDocument($id)
    {
        $cek = DocsMaster::where('doc_id', $id)->doesnthave('apprvhist')->first();
        if (!empty($cek)) {
            $hasil = $cek;
            Storage::delete($hasil['doc_real_path'] . $hasil['doc_name']);

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
        $pdf = new \setasign\Fpdi\Fpdi();
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

            $pdf->useTemplate($templateId);

            $pdf->SetFont('Helvetica');
            $pdf->SetXY(5, 5);

            $cekreject = ApprovalHist::where('apprv_hist_doc', $iddoc)->where('apprv_hist_status', '<>', '1')->first();

            if (empty($cekreject)) {
                $cekjumlahapprover = ApprovalMaster::where('apprv_id', $author)->count();
                $cekjumlahyangapprove = ApprovalHist::where('apprv_hist_doc', $iddoc)->where('apprv_parent', '<>', '0')->count();

                if ($cekjumlahapprover == $cekjumlahyangapprove && $cekjumlahapprover > 0) {
                    $pdf->Write(8, 'This document fully approved');
                } else {
                    $pdf->Write(8, 'This document partially approved');
                }
            } else {
                $pdf->SetTextColor(255, 0, 0);
                $pdf->Write(8, 'This document has been rejected');
            }

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
                ->where('apprv_parent','<>','0')
                ->with('users')
                ->orderBy('created_at', 'asc')
                ->get();

                foreach ($cekhist as $key => $value) {
                    $pdf->SetXY(10, (1 + $key) * 20);

                    $cellWidth = $pdf->GetStringWidth($value['apprv_hist_comment']) < 100 ? 100 : $pdf->GetStringWidth($value['apprv_hist_comment']) + 25;

                    $pdf->Cell(40, 5, ' ', 'LTR', 0, 'L', 0);   // empty cell with left,top, and right borders
                    if ($value['apprv_hist_status'] == '1') {
                        $pdf->Cell($cellWidth, 5, 'Digitally signed by @' . $value['users']['username'], 'LTR', 0, 'L', 0);
                    } else {
                        $pdf->Cell($cellWidth, 5, 'Rejected by @' . $value['users']['username'], 'LTR', 0, 'L', 0);
                    }

                    $pdf->Ln();

                    $pdf->SetFont('Times', 'BIU');
                    if ($value['apprv_hist_status'] == '1') {
                        $pdf->Cell(40, 5, $value['users']["first_name"] . ' ' . $value['users']["last_name"], 'LR', 0, 'C', 0);  // cell with left and right borders
                    } else {
                        $pdf->SetFont('Helvetica');
                        $pdf->SetTextColor(255, 0, 0);
                        $pdf->Cell(40, 5, 'REJECTED', 'LR', 0, 'C', 0);  // cell with left and right borders
                        $pdf->SetTextColor(0, 0, 0);
                    }
                    $pdf->SetFont('Helvetica');
                    $pdf->Cell($cellWidth, 5, 'Email : ' . $value['users']["email"], 'LR', 0, 'L', 0);
                    // $pdf->Cell(50, 5, '[ x ] che2', 'LR', 0, 'L', 0);

                    $pdf->Ln();
                    $pdf->Cell(40, 5, '', 'LR', 0, 'LR', 0);   // empty cell with left,bottom, and right borders
                    $pdf->Cell($cellWidth, 5, 'Reason : ' . $value['apprv_hist_comment'], 'LR', 0, 'L', 0);
                    // $pdf->Cell(50, 5, '[ o ] def4', 'LRB', 0, 'L', 0);

                    $pdf->Ln();

                    $pdf->Cell(40, 5, '', 'LBR', 0, 'LR', 0);   // empty cell with left,bottom, and right borders
                    $pdf->Cell($cellWidth, 5, 'Date : ' . $value['created_at'], 'LRB', 0, 'L', 0);
                    // $pdf->Cell(50, 5, '[ o ] def4', 'LRB', 0, 'L', 0);

                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->Ln();
                }
            }
        }

        return $pdf->Output("", "S");

        return $pdf;
    }
}
