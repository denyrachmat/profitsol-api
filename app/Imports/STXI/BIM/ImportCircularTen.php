<?php

namespace App\Imports\STXI\BIM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Excel;

use Redis;

use App\Imports\STXI\BIM\ImportTENList;
use App\Models\STXI\BIM\CircularTenList;
use App\Models\STXI\BIM\CircularTenMstr;
use App\Models\STXI\BIM\CircularTenModelDet;

class ImportCircularTen implements ToModel
{
    public $data, $pdf, $dataForPDF, $tenNo, $tenEpsonNo, $options, $filepathExcel, $username;
    /**
     * @param Collection $collection
     */
    public function __construct($tenNo, $htmlEpson, $tenEpsonNo, $options = 0, $filepathExcel = '', $username = '', $year = '', $month = '')
    {
        $this->nowRows = -1;
        $this->statusGetData = '';
        $this->getColsForStart = 0;
        $this->getRowsForModel = 0;
        $this->data = [
            'model' => [],
            'content' => '',
            'send_data' => []
        ];
        $this->tenNo = $tenNo;
        $this->tenEpsonNo = $tenEpsonNo;
        $this->htmlEpson = $htmlEpson;
        $this->tableBuild = "<table><tbody>";
        $this->contentArray = [];
        $this->options = $options;
        $this->filepathExcel = $filepathExcel;
        $this->username = $username;
    }

    public function model(array $row)
    {
        // $cekKosong = array_filter($row, function ($f) {
        //     if (!empty($f)) {
        //         return $f;
        //     }
        // });

        // if (count($cekKosong) > 0) {
        // }


        $this->nowRows = $this->nowRows + 1;

        foreach ($row as $key => $value) {
            if (!empty($value)) {
                // $this->titleData = $value;

                if (str_contains(strtolower($value), 'applicable')) {
                    $this->statusGetData = 'getModel';
                    $this->getColsForStart = $key;
                    $this->getRowsForModel = $this->nowRows + 2;
                }

                if (str_contains(strtolower($value), 'contents')) {
                    $this->statusGetData = 'getContent';
                    $this->getColsForStart = $key;
                    $this->getRowsForModel = $this->nowRows + 1;
                }

                if (str_contains(strtolower($value), 'revised')) {
                    $this->statusGetData = 'getRevised';
                    $this->getColsForStart = $key;
                    $this->getRowsForModel = $this->nowRows + 1;
                }
            }
        }

        // For get List model & Subcon Code
        if ($this->nowRows >= $this->getRowsForModel && $this->statusGetData == 'getModel') {
            if (!empty($row[$this->getColsForStart])) {
                foreach ($row as $keyModel => $valueModel) {
                    $cekItem = str_contains($valueModel, '-') ? explode('-', $valueModel)[0] : $valueModel;
                    $implodeItem = implode('', explode('-', $valueModel));
                    $cekItemExists = array_filter($this->data['model'], function ($f) use ($cekItem, $implodeItem) {
                        if ($f == $cekItem || str_contains(strtolower($implodeItem), $f)) {
                            return $f;
                        }
                    });

                    if (count($cekItemExists) === 0) {
                        $getDataItem = $this->getItemMaster($implodeItem, $cekItem);

                        if (!empty($valueModel) && strlen($valueModel) > 4 && count($getDataItem) > 0) {

                            $this->data['ten'] = $this->tenNo;
                            $this->data['model_cek'][$getDataItem['model']] = $getDataItem['model'];
                            $this->data['model'] = count($getDataItem) > 0 ? array_values($getDataItem['listSub']) : '';
                            $this->data['valmodel'][] = count($getDataItem) > 0 ? $getDataItem['valmodel'] : [];
                            // $this->data['cekItem'] = $getDataItem;
                        }
                    }
                }
            } else {
                $this->statusGetData = '';
            }
        }

        // For get Content
        $this->data['checkRow'][] = [$this->nowRows, $this->getRowsForModel, $this->statusGetData];
        if ($this->nowRows >= $this->getRowsForModel && $this->statusGetData == 'getContent') {
            $hasilCekKosong = [];
            foreach ($row as $keyCekKosongModel => $valueKosongModel) {
                if (!empty(trim($valueKosongModel))) {
                    $hasilCekKosong[] = $valueKosongModel;
                }
            }
            $this->data['contentCek'][] = $hasilCekKosong;
            $this->data['contentCek2'][] = $this->contentArray;

            if (count($hasilCekKosong) > 0) {
                $this->contentArray[] = $hasilCekKosong;
            }

            $this->data['cekContentJuga'][] = [count($hasilCekKosong), $hasilCekKosong, count($this->contentArray)];

            if (count($hasilCekKosong) === 0 && count($this->contentArray) > 0) {

                foreach ($this->contentArray as $keyRow => $valueRow) {
                    $this->tableBuild .= "<tr>";

                    foreach ($valueRow as $keyCol => $valueCol) {
                        $this->tableBuild .= "<td style='padding: 5px'>" . $valueCol . "</td>";
                    }

                    $this->tableBuild .= "</tr>";
                }

                $this->tableBuild .= "</tbody></table>";

                $this->data['content'] = $this->tableBuild;
                $this->statusGetData = '';
            }
        }

        if ($this->nowRows >= $this->getRowsForModel && $this->statusGetData == 'getRevised') {
            foreach ($this->contentArray as $keyRow => $valueRow) {
                $this->tableBuild .= "<tr>";

                foreach ($valueRow as $keyCol => $valueCol) {
                    $this->tableBuild .= "<td style='padding: 5px'>" . $valueCol . "</td>";
                }

                $this->tableBuild .= "</tr>";
            }
            $this->tableBuild .= "</tbody></table>";

            $this->data['content'] = $this->tableBuild;
            $this->statusGetData = '';
        }

        // For Exec Content
        $filenya = Storage::disk('ten_bim')->get($this->htmlEpson);
        $crawler = new Crawler($filenya);

        $listItem = $crawler->filterXPath('//*[@style="word-wrap: break-word;"]')->extract(['_text']);
        $this->data['exec'] = empty($listItem[5]) ? $listItem[3] : $listItem[5];
        $this->data['reason'] = empty($listItem[24]) ? (empty($listItem[25]) ? $listItem[26] : $listItem[25]) : $listItem[24];
        $this->data['cekhtml'] = $listItem;

        $listSubject = $crawler->filterXPath('//*[@class="comment-box"]')->extract(['_text']);
        $this->data['subject'] = $listSubject[1];
        $cekTen = CircularTenList::where('CTT_SECTENNO', $this->tenNo)->first();

        $this->data['mail_date'] = $cekTen->CTT_EMLDT;

        $pathInfo = pathinfo($this->htmlEpson);
        $directoryPath = $pathInfo['dirname'];

        $filesData = Storage::disk('ten_bim')->files($directoryPath);

        // return $filesData;
        $listCopiedFiles = [];
        foreach ($filesData as $file) {
            Storage::writeStream($file, Storage::disk('ten_bim')->readStream($file));
            $listCopiedFiles[] = basename($file);
        }

        // Copy to local storage laravel


        $datas = [];

        if ($this->options === 1) {
            $datas = [
                'ten' => $this->tenNo,
                'mail_date' => $this->data['mail_date'],
                'subject' => $this->data['subject'],
                'model' => $this->data['model'],
                'content' => $this->data['content'],
                'list_files' => $listCopiedFiles,
                'exec_sch' => $this->data['exec'],
                'reason' => $this->data['reason'],
                'username' => $this->username
            ];
            $this->pdf = $this->generateDocument($this->data['mail_date'], $datas, true);
        }

        if ($this->options === 2) {
            // Save to Cirten Master Table
            $storedTen = CircularTenMstr::updateOrCreate([
                'CIRTEN_NO' => $this->tenNo
            ], [
                'CIRTEN_NO' => $this->tenNo,
                'CIRTEN_MAILDT' => $this->data['mail_date'],
                'CIRTEN_TENIEI' => $this->tenEpsonNo,
                'CIRTEN_FILEPATH' => $this->filepathExcel,
                'CIRTEN_HTMFILEPATH' => $this->htmlEpson,
            ]);

            $getListItemFromTenList = CircularTenList::where('CTT_SECTENNO', $this->tenNo)->first();

            if (!empty($getListItemFromTenList->models) && count($getListItemFromTenList->models) > 0) {
                foreach ($getListItemFromTenList->models as $keyMdl => $valueMdl) {
                    // $this->data['model'][] = $valueMdl->CTID_ITEMCDNEW;
                    CircularTenModelDet::updateOrCreate([
                        'CM_ID' => $storedTen->id,
                        'CIM_ITMCD' => $valueMdl->CTID_ITEMCDNEW,
                    ], [
                        'CM_ID' => $storedTen->id,
                        'CIM_ITMCD' => $valueMdl->CTID_ITEMCDNEW,
                    ]);
                }
            }

            // Cek model kalo kosong ambil dari database
            $cekTenSudahInput = CircularTenMstr::where('CIRTEN_NO', $this->tenNo)->first();
            if (count($this->data['model']) === 0) {
                $cekModel = CircularTenModelDet::where('CM_ID', $cekTenSudahInput->id)->get();

                $hasilSupp = [];
                foreach ($cekModel->pluck('CIM_ITMCD') as $key => $value) {
                    $cekSupp = $this->getItemMaster($value, '');

                    $hasilSupp[count($cekSupp) > 0 ? array_merge($hasilSupp, array_values($cekSupp['listSub']))[0] : ''] = count($cekSupp) > 0 ? array_merge($hasilSupp, array_values($cekSupp['listSub']))[0] : '';
                }

                if (count($hasilSupp) > 0) {
                    $this->data['model_cek'] = $cekModel->pluck('CIM_ITMCD');
                    $this->data['model'] = array_values($hasilSupp);
                }
            }

            $status = '';
            if (count($this->data['model']) === 0) {
                $status .= 'Model not found on Excel of Ten, please add it manually !!';

                if (empty($this->data['content'])) {
                    $status .= '<br>Content not found, please check the excel !!';
                }

                $storedTen = CircularTenMstr::updateOrCreate([
                    'CIRTEN_NO' => $this->tenNo
                ], [
                    'CIRTEN_NO' => $this->tenNo,
                    'CIRTEN_MAILDT' => $this->data['mail_date'],
                    'CIRTEN_STATUS' => $status,
                    'CIRTEN_STATUSFLG' => 1
                ]);
            } elseif (empty($this->data['content'])) {
                $status .= '<br>Content not found, please check the excel !!';

                $storedTen = CircularTenMstr::updateOrCreate([
                    'CIRTEN_NO' => $this->tenNo
                ], [
                    'CIRTEN_NO' => $this->tenNo,
                    'CIRTEN_MAILDT' => $this->data['mail_date'],
                    'CIRTEN_STATUS' => $status,
                    'CIRTEN_STATUSFLG' => 1
                ]);
            } else {
                $status = '';

                $storedTen = CircularTenMstr::updateOrCreate([
                    'CIRTEN_NO' => $this->tenNo
                ], [
                    'CIRTEN_NO' => $this->tenNo,
                    'CIRTEN_MAILDT' => $this->data['mail_date'],
                    'CIRTEN_STATUS' => $status,
                    'CIRTEN_STATUSFLG' => 1
                ]);

                $datas = [
                    'ten' => $this->tenEpsonNo,
                    'mail_date' => $this->data['mail_date'],
                    'subject' => $this->data['subject'],
                    'model' => $this->data['model'],
                    'content' => $this->data['content'],
                    'list_files' => $listCopiedFiles,
                    'exec_sch' => $this->data['exec'],
                    'reason' => $this->data['reason'],
                    'username' => $this->username,
                ];

                $getListItemFromTenList = CircularTenList::where('CTT_SECTENNO', $this->tenNo)->first();

                if (!empty($getListItemFromTenList->models) && count($getListItemFromTenList->models) > 0) {
                    foreach ($getListItemFromTenList->models as $keyMdl => $valueMdl) {
                        CircularTenModelDet::updateOrCreate([
                            'CM_ID' => $storedTen->id,
                            'CIM_ITMCD' => $valueMdl->CTID_ITEMCDNEW,
                        ], [
                            'CM_ID' => $storedTen->id,
                            'CIM_ITMCD' => $valueMdl->CTID_ITEMCDNEW,
                        ]);
                    }
                } else {
                    foreach ($this->data['model_cek'] as $keyMdl => $valueMdl) {
                        CircularTenModelDet::updateOrCreate([
                            'CM_ID' => $storedTen->id,
                            'CIM_ITMCD' => $valueMdl,
                        ], [
                            'CM_ID' => $storedTen->id,
                            'CIM_ITMCD' => $valueMdl,
                        ]);
                    }
                }

                $this->data['send_data'] = $datas;
            }
        }

        if ($this->options === 3) {
            $datas = [
                'ten' => $this->tenNo,
                'mail_date' => $this->data['mail_date'],
                'subject' => $this->data['subject'],
                'model' => $this->data['model'],
                'content' => $this->data['content'],
                'list_files' => $listCopiedFiles,
                'exec_sch' => $this->data['exec'],
                'reason' => $this->data['reason'],
                'username' => $this->username
            ];
            $this->dataForPDF = $datas;
        }
    }

    function generateDocument($emailDate, $datas, $isExport = false)
    {
        if ($isExport) {
            $pdf = Pdf::loadView('STXI/BIM/circularTenLayout', $datas);

            return $pdf->download($this->tenNo . '.pdf');
        }

        return $datas;
    }

    function getItemMaster($item, $itemAlt)
    {
        if (!empty($item)) {
            $getDataItem = DB::connection('sqlsrv_mega_sme')->table('MITM_TBL')
                ->select(
                    'MITM_ITMCD',
                    'MITM_ITMD1',
                    'MITM_STKUOM',
                    'MITM_SPTNO',
                    'MITM_SUPCD',
                    'MITM_ITMTY'
                )
                ->where('MITM_ITMCD', 'like', $item . '%')
                ->whereNotNull('MITM_ITMTY')
                ->get();

            if (count($getDataItem) > 0) {
                $listSubcon = [];
                $items = [];
                foreach ($getDataItem as $keyItem => $value) {
                    $items[] = trim($value->MITM_ITMCD);
                    $getSubcon = empty($value->MITM_SUPCD)
                        ? substr(trim($value->MITM_ITMTY), 0, 3)
                        : substr(trim($value->MITM_SUPCD), 0, 3);

                    if ($getSubcon !== 'SMT' || $getSubcon !== 'VST' || $getSubcon !== 'KAI') {
                        $getSubcons = trim($getSubcon);
                    } else {
                        $getSubcons = trim($getSubcon);
                    }

                    $listSubcon[$getSubcons] = $getSubcons;
                }

                return [
                    'model' => $items[0],
                    'valmodel' => $item,
                    'cekItem' => $getDataItem,
                    'listSub' => $listSubcon
                ];
            } else {
                if (!empty($itemAlt)) {
                    return $this->getItemMaster($itemAlt, '');
                } else {
                    return [];
                }
            }
        } else {
            return [];
        }
    }
}
