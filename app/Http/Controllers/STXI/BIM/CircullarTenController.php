<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\File;

use App\Models\STXI\BIM\CircularTenMstr;
use App\Models\STXI\BIM\CircularTenModelDet;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Laravel\Dusk\ElementResolver;
use Symfony\Component\DomCrawler\Crawler;

class CircullarTenController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Storage::disk('local')->allDirectories('public/circular_ten');
        $hasil = [];
        foreach ($data as $key => $value) {
            $path = $value;

            $totalSize = 0;
            $allFiles = Storage::disk('local')->allFiles($path);
            foreach ($allFiles as $key => $value) {
                $totalSize += Storage::disk('local')->getSize($value);
            }

            $getTenNo = explode('/', $path)[2];
            $cekCreator = CircularTenMstr::where('CIRTEN_NO', $getTenNo)->whereNotNull('CIRTEN_GENDT')->first();

            if (empty($cekCreator)) {
                $hasil[] = [
                    'ten_no' => $getTenNo,
                    'path' => $path,
                    'size' => (($totalSize / 1000) > 1024 ? number_format((float) (($totalSize / 1000) / 1000), 2, '.', '') . ' MB' : (($totalSize / 1000)) . ' KB'),
                    'files' => $allFiles
                ];
            }
        }
        // return $cekCreator;

        if (count($hasil) > 0) {
            return $this->handleResponse($hasil, 'Data found !');
        }

        return $this->handleError('Data not found !', []);
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
        return response(Storage::disk('ten_bim')->allFiles());
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
    public function update(Request $request, $id)
    {
        //
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

    public function uploadCirTenFolder(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();
        $realName = $req->file('file')->getClientOriginalName();
        $filenameOnly = pathinfo($realName, PATHINFO_FILENAME);

        $nama_file = $filenameOnly . '.' . $extNya;
        $req->file->storeAs('/public/circular_ten/' . $req->ten_no . '/', $nama_file);

        if ($extNya === 'htm') {
            $storedTen = CircularTenMstr::updateOrCreate([
                'CIRTEN_NO' => $req->ten_no
            ], [
                    'CIRTEN_NO' => $req->ten_no,
                    'CIRTEN_MAILDT' => $req->emailDate
                ]);

            $getModelList = $this->generateDocument($req->ten_no, false)['list_model'];
            foreach ($getModelList as $key => $value) {
                CircularTenModelDet::create([
                    'CM_ID' => $storedTen->id,
                    'CIM_ITMCD' => $value['MDLCD'],
                ]);
            }
        }

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function generateDocument($ten, $isExport = true)
    {
        $data = CircularTenMstr::where('CIRTEN_NO', $ten)->first();
        $files = '';
        $filesData = Storage::disk('local')->files('public/circular_ten/' . $ten);
        foreach ($filesData as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'htm') {
                $files = $file;
                break;
            }
        }

        $getModel = $this->extractCirtenCover($files);
        $hasil = $this->listModelFromHTM($ten)['SUBCONT'];

        $cekDataModel = CircularTenModelDet::where('CM_ID', $data->id)->get();
        if (count($cekDataModel) > 0) {
            $listModel = [];
            foreach ($cekDataModel as $keyMdl => $valueMdl) {
                $getDataItem = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')
                    ->where('MITM_ITMCD', 'like', $valueMdl->CIM_ITMCD . '%')
                    ->first();

                $listModel[] = [
                    'MDLCD' => $valueMdl->CIM_ITMCD,
                    'DESC' => $getDataItem->MITM_ITMD1,
                    'PARTNO' => $getDataItem->MITM_SPTNO,
                ];
            }
        } else {
            $listModel = $this->listModelFromHTM($ten)['ITEM'];
        }

        $data = [
            'ten' => $ten,
            'mail_date' => $data,
            'model' => array_values($hasil),
            'list_model' => array_values($listModel),
            'content' => isset($getModel['list_content'][0]) ? str_replace(["\n", "\r", "\\"], "", $getModel['list_content'][0]) : '',
            'subject' => count($getModel['subject']) > 1 ? $getModel['subject'][1] : $getModel['subject'][0],
            'list_files' => $filesData,
            'exec_sch' => count($getModel['exec_sch']) > 2 ? $getModel['exec_sch'][2] : '',
            'reason' => count($getModel['reason']) > 1 ? $getModel['reason'][1] : ''
        ];

        if ($isExport) {
            // return view('STXI/BIM/circularTenLayout', $data);

            $pdf = Pdf::loadView('STXI/BIM/circularTenLayout', $data);

            return $pdf->download($ten . '.pdf');
        }

        return $data;
    }

    public function extractCirtenCover($path)
    {
        $filenya = Storage::disk('local')->get($path);
        $crawler = new Crawler($filenya);

        $listItem = $crawler->filterXPath('//*[@class="NaiyoTblE1"]/tbody/tr/td/font')->extract(['_text']);

        $getModel = [];
        foreach ($listItem as $key => $value) {
            if (!empty($value)) {
                $itemCodeFixRemoveArrow = explode(" -> ", $value);
                if (count($itemCodeFixRemoveArrow) > 0) {
                    $itemCodeFixStrip = explode("-", $itemCodeFixRemoveArrow[0]);
                    if (count($itemCodeFixStrip) > 1) {
                        $itemCode = $itemCodeFixStrip[0] . $itemCodeFixStrip[1];

                        $getModel[] = $itemCode;
                    }
                }
            }
        }

        $getContent = $crawler->filterXPath('//*[@class="NaiyoTblE2"]')->each(function ($value) {
            return $value->html();
        });

        $getContentWoTable = $crawler->filterXPath("//*[text()[contains(.,'1.Contents')]]")->each(function ($value) {
            return $value->html();
        });

        $getSubject = $crawler->filterXPath('//table/tbody/tr[@valign="top"]/td[@width="64%"]/b/*')->each(function ($value) {
            return $value->text();
        });

        $getRevisedDoc = $crawler->filterXPath('//*[@class="NaiyoTblCmt"]')->each(function ($value) {
            return $value->html();
        });

        $getExecSchedule = $crawler->filterXPath('//table[@style="border:1px solid #333;"]/tbody/tr[@valign="top"]/td[@width="100%"]/*')->each(function ($value) {
            return $value->text();
        });

        $getReason = $crawler->filterXPath('//table[@style="border:1px solid #333;border-top-style: hidden;"]/tbody/tr[@valign="top"]/td[@width="100%"]/*')->each(function ($value) {
            return $value->text();
        });

        return [
            'list_item' => $getModel,
            'list_content' => count($getContent) > 0 ? $getContent : (
                count($getRevisedDoc) > 0
                ? $getRevisedDoc
                : $getContentWoTable
            ),
            'subject' => $getSubject,
            'exec_sch' => $getExecSchedule,
            'reason' => $getReason
        ];
    }

    public function listModelFromHTM($ten)
    {
        $data = CircularTenMstr::where('CIRTEN_NO', $ten)->first();
        $files = '';
        $filesData = Storage::disk('local')->files('public/circular_ten/' . $ten);
        foreach ($filesData as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'htm') {
                $files = $file;
                break;
            }
        }

        $getModel = $this->extractCirtenCover($files);

        $hasil = [];
        $hasilItem = [];
        if (count($getModel['list_item'])) {
            foreach ($getModel['list_item'] as $key => $value) {
                $getDataItem = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')
                    ->where('MITM_ITMCD', 'like', $value . '%')
                    ->first();

                if (!empty($getDataItem)) {
                    $hasilItem[] = [
                        'MDLCD' => $value,
                        'DESC' => trim($getDataItem->MITM_ITMD1),
                        'PARTNO' => trim($getDataItem->MITM_SPTNO)
                    ];
                    $hasil[substr($getDataItem->MITM_SUPCD, 0, 3)] = substr($getDataItem->MITM_SUPCD, 0, 3);
                }
            }
        } else {
            $cekDataModel = CircularTenModelDet::where('CM_ID', $data->id)->get();
            foreach ($cekDataModel as $keyMdl => $valueMdl) {
                $getDataItem = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')
                    ->where('MITM_ITMCD', $valueMdl->CIM_ITMCD)
                    ->first();
                if (!empty($getDataItem)) {
                    $hasil[substr($getDataItem->MITM_SUPCD, 0, 3)] = substr($getDataItem->MITM_SUPCD, 0, 3);
                    $hasilItem[] = [
                        'MDLCD' => $valueMdl->CIM_ITMCD,
                        'DESC' => trim($getDataItem->MITM_ITMD1),
                        'PARTNO' => trim($getDataItem->MITM_SPTNO)
                    ];
                }
            }
        }

        return ['SUBCONT' => $hasil, 'ITEM' => $hasilItem];
    }

    public function findItem($item)
    {
        $getDataItem = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')
            ->where('MITM_ITMCD', 'like', $item . '%')
            ->orwhere('MITM_ITMD1', 'like', $item . '%')
            ->get();

        $hasil = [];
        foreach ($getDataItem as $key => $value) {
            $hasil[] = [
                'label' => trim($value->MITM_ITMCD) . ' - ' . trim($value->MITM_ITMD1),
                'value' => trim($value->MITM_ITMCD)
            ];
        }

        return $hasil;
    }

    public function addModelDetail($ten, $item)
    {
        $mainTen = CircularTenMstr::where('CIRTEN_NO', $ten)->first();
        $cekDataModel = CircularTenModelDet::updateOrCreate([
            'CM_ID' => $mainTen->id,
            'CIM_ITMCD' => $item
        ], [
                'CM_ID' => $mainTen->id,
                'CIM_ITMCD' => $item
            ]);

        return $this->handleResponse($cekDataModel, 'Update data Sukses ' . $item . ' on TEN ' . $ten);
    }

    public function savePDFtoLocal($ten)
    {
        
    }

    public function sendToDMS($ten)
    {  
        $pdf = $this->generateDocument($ten, true);

        $target_url = 'http://192.168.100.32:8081/stx_api/public/api/dms/docsupload'; // Write your URL here
        $dir = '/var/www/html/storage/test.zip'; // full directory of the file

        // $cFile = curl_file_create($pdf);
        $post = [
            'file'=> $pdf,
            'username' => 'susi',
            'folder_id' => '2vxtcJxq4YDBmS5v23cKaWRU4o01LXsUtBPtU9jWm2x9NklzyD',
            'folder_name' => "New System Cirten (Don't Delete)"
        ]; // Parameter to be sent

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result=json_decode(curl_exec($ch));
        curl_close ($ch);

        return $result;
    }
}