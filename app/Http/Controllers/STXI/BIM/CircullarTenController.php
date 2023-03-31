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
        $tenNo = '';
        if ($extNya === 'htm') {
            $splitName = explode('.', $realName);
            $tenNo = trim($splitName[1]);

            CircularTenMstr::updateOrCreate([
                'CIRTEN_NO' => $req->ten_no
            ], [
                    'CIRTEN_NO' => $req->ten_no,
                    'CIRTEN_MAILDT' => $req->emailDate
                ]);
        }

        $nama_file = $realName . '.' . $extNya;

        $req->file->storeAs('/public/circular_ten/' . $req->ten_no . '/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $realName . '.xlsx';
            $writer->save('/public/circular_ten/' . $req->ten_no . '/' . $nama_file);
        }

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function generateDocument($ten)
    {
        $data = CircularTenMstr::where('CIRTEN_NO', $ten)->first();
        $files = '';
        $filesData = Storage::disk('local')->files('public/circular_ten/' . $ten);
        foreach ( $filesData as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'htm') {
                $files = $file;
                break;
            }
        }

        $getModel = $this->extractCirtenCover($files);

        // return $getModel;
        $hasil = [];
        if(count($getModel['list_item'])) {
            foreach ($getModel['list_item'] as $key => $value) {
                $getDataItem = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')
                    ->where('MITM_ITMCD', $value)
                    ->first();

                if (!empty($getDataItem)) {
                    $hasil[substr($getDataItem->MITM_SUPCD, 0, 3)] = substr($getDataItem->MITM_SUPCD, 0, 3);
                }
            }
        }

        $pdf = Pdf::loadView('STXI/BIM/circularTenLayout', [
            'ten' => $ten,
            'mail_date' => $data,
            'model' => array_values($hasil),
            'content' => str_replace(["\n","\r","\\"],"",$getModel['list_content'][0]),
            'subject' => count($getModel['subject']) > 1 ? $getModel['subject'][1] : $getModel['subject'][0],
            'list_files' => $filesData,
            'exec_sch' => count($getModel['exec_sch']) > 2 ? $getModel['exec_sch'][2] : '',
            'reason' => count($getModel['reason']) > 1 ? $getModel['reason'][1] : ''
        ]);

        return $pdf->download($ten.'.pdf');
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
                        $itemCode = $itemCodeFixStrip[0].$itemCodeFixStrip[1];

                        $getModel[] = $itemCode;
                    }
                }
            }
        }

        $getContent = $crawler->filterXPath('//*[@class="NaiyoTblE2"]')->each(function ($value) {
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
            'list_content' => count($getContent) > 0 ? $getContent : $getRevisedDoc,
            'subject' => $getSubject,
            'exec_sch' => $getExecSchedule,
            'reason' => $getReason
        ];
    }

    public function checkTrial()
    {
        $process = (new ChromeProcess)->toProcess();
        //$process->start();
        $process->start(null, [
            'SystemRoot' => 'C:\\WINDOWS',
            'TEMP' => 'C:\Users\MAHAVIR\AppData\Local\Temp',
        ]);
        $options = new ChromeOptions;
        $options->setBinary("C:\Program Files\Google\Chrome\Application\chrome.exe");
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = retry(5, function () use ($capabilities) {
            return RemoteWebDriver::create('http://localhost:9515', $capabilities);
        }, 50);
        $browser = new Browser($driver);
        $browser->visit('https://www.google.com');
        $browser->quit();
        $process->stop();
    }
}