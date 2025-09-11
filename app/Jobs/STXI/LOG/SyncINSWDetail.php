<?php

namespace App\Jobs\STXI\LOG;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\STXI\LOG\INSWDataMaster;
use App\Models\STXI\LOG\INSWDataJlsDetail;
use App\Models\STXI\LOG\INSWDataSatDetail;
use App\Models\STXI\LOG\INSWDataRegDet;
use App\Models\STXI\LOG\INSWDataDocBeaMaster;

class SyncINSWDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data, $search;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $search)
    {
        $this->data = $data;
        $this->search = $search;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $getHSCode = $this->data;

        try {
            $dataDetail = $this->getData($getHSCode);

            if (!empty($dataDetail)) {
                if (isset($dataDetail['data'][0])) {
                    $dataDetailGet = $dataDetail['data'][0];
                    $dataHSParent = $dataDetailGet['hsParent'][0];
                    $dataMFN = $dataDetailGet['mfn'][0];

                    if (!empty($this->search)) {
                        if (ctype_digit($this->search)) {
                            INSWDataMaster::where('ZID_HSCODE', $getHSCode)->forceDelete();
                        } else {
                            INSWDataMaster::where('ZID_HSCODE', $getHSCode)->delete();
                        }
                    }

                    $masterCreate = INSWDataMaster::updateOrCreate([
                        'ZID_HSCODE' => $getHSCode,
                    ], [
                        'ZID_HSCODE' => $getHSCode,
                        'ZID_BAGIAN' => $dataDetailGet['bagian'],
                        'ZID_BAB' => $dataDetailGet['bab'],
                        'ZID_HSPRNT' => $dataHSParent['hs_code_format'],
                        'ZID_HSPRNT_DESC_ID' => $dataHSParent['ur_id'],
                        'ZID_HSPRNT_DESC_EN' => $dataHSParent['ur_en'],
                        'ZID_HSPRNT_FRMT' => isset($dataDetailGet['hsParent'][1]) ? $dataDetailGet['hsParent'][1]['hs_code_format'] : '',
                        'ZID_HSPRNT_FRMT_DESC_ID' => isset($dataDetailGet['hsParent'][1]) ? $dataDetailGet['hsParent'][1]['ur_id'] : '',
                        'ZID_HSPRNT_FRMT_DESC_END' => isset($dataDetailGet['hsParent'][1]) ? $dataDetailGet['hsParent'][1]['ur_en'] : '',
                        'ZID_MFN_BMPPN' => isset($dataMFN['ppnbm'][0]) ? $dataMFN['ppnbm'][0]['ppnbm'] : '',
                        'ZID_MFN_BM' => isset($dataMFN['bm'][0]) ? $dataMFN['bm'][0]['bm'] : '',
                        'ZID_MFN_PPN' => isset($dataMFN['ppn'][0]) ? $dataMFN['ppn'][0]['ppn'] : '',
                        'ZID_MFN_PPH' => isset($dataMFN['pph'][0]) ? $dataMFN['pph'][0]['pph'] : '',
                        'ZID_MFN_CUKAI' => isset($dataMFN['cukai'][0]) ? $dataMFN['cukai'][0]['cukai'] : '',
                        'ZID_KOND' => $dataDetailGet['kondisiTertentu'],
                    ]);

                    $jlsCreate = [];
                    foreach ($dataDetailGet['bab_penjelasan'] as $keyJls => $valueJls) {

                        if (!empty($this->search)) {
                            if (ctype_digit($this->search)) {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bab')
                                    ->where('ZIJD_DET_ID', $valueJls)
                                    ->forceDelete();
                            } else {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bab')
                                    ->where('ZIJD_DET_ID', $valueJls)
                                    ->delete();
                            }
                        }
                        $jlsCreate[] = INSWDataJlsDetail::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bab',
                        ], [
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bab',
                            'ZIJD_DET_ID' => $valueJls,
                        ]);
                    }

                    foreach ($dataDetailGet['bab_penjelasan_en'] as $keyJls => $valueJls2) {

                        if (!empty($this->search)) {
                            if (ctype_digit($this->search)) {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bab_en')
                                    ->forceDelete();
                            } else {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bab_en')
                                    ->delete();
                            }
                        }

                        $jlsCreate[] = INSWDataJlsDetail::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bab_en',
                        ], [
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bab_en',
                            'ZIJD_DET_ID' => $valueJls2,
                        ]);
                    }

                    foreach ($dataDetailGet['bagian_penjelasan'] as $keyJls => $valueJls3) {

                        if (!empty($this->search)) {
                            if (ctype_digit($this->search)) {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bagian')
                                    ->forceDelete();
                            } else {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bagian')
                                    ->delete();
                            }
                        }

                        $jlsCreate[] = INSWDataJlsDetail::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bagian',
                        ], [
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bagian',
                            'ZIJD_DET_ID' => $valueJls3,
                        ]);
                    }

                    foreach ($dataDetailGet['bagian_penjelasan_en'] as $keyJls => $valueJls4) {

                        if (!empty($this->search)) {
                            if (ctype_digit($this->search)) {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bagian_en')
                                    ->forceDelete();
                            } else {
                                INSWDataJlsDetail::where('ZID_HSCODE', $getHSCode)
                                    ->where('ZIJD_TYPE', 'bagian_en')
                                    ->delete();
                            }
                        }

                        $jlsCreate[] = INSWDataJlsDetail::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bagian_en',
                        ], [
                            'ZID_HSCODE' => $getHSCode,
                            'ZIJD_TYPE' => 'bagian_en',
                            'ZIJD_DET_ID' => $valueJls4,
                        ]);
                    }



                    if (!empty($this->search)) {
                        if (ctype_digit($this->search)) {
                            INSWDataRegDet::where('ZID_HSCODE', $getHSCode)
                                // ->where('ZIRD_TYPE', 'import_regulation')
                                ->forceDelete();
                        } else {
                            INSWDataRegDet::where('ZID_HSCODE', $getHSCode)
                                ->delete();
                        }
                    }

                    $dataRegCreate = [];
                    foreach ($dataDetailGet['import_regulation'] as $key => $valueReg) {
                        $dataRegCreate[] = INSWDataRegDet::create([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'import_regulation',
                            'ZIRD_NMIJIN' => $valueReg['nama_ijin'] ?? $valueReg['name'],
                            'ZIRD_KDIJIN' => $valueReg['kd_ijin'] ?? $valueReg['deskripsi'],
                            'ZIRD_DESC' => $valueReg['desc'] ?? $valueReg['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueReg['dok_pabean']),
                            'ZIRD_LEGAL' => $valueReg['legal'] ?? '',
                            'ZIRD_MODUL' => $valueReg['modul'],
                            'ZIRD_SKEPNO' => $valueReg['nomor_skep'] ?? ''
                        ]);
                    }

                    foreach ($dataDetailGet['import_regulation_border'] as $key2 => $valueRegBord) {
                        $dataRegCreate[] = INSWDataRegDet::create([
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'import_regulation_border',
                            'ZIRD_NMIJIN' => $valueRegBord['nama_ijin'] ?? $valueRegBord['name'],
                            'ZIRD_KDIJIN' => $valueRegBord['kd_ijin'] ?? $valueRegBord['kode_ijin'],
                            'ZIRD_DESC' => $valueRegBord['desc'] ?? $valueRegBord['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueRegBord['dok_pabean']),
                            'ZIRD_LEGAL' => $valueRegBord['legal'] ?? '',
                            'ZIRD_MODUL' => $valueRegBord['modul'],
                            'ZIRD_SKEPNO' => $valueRegBord['nomor_skep'] ?? ''
                        ]);
                    }

                    logger($dataDetailGet['export_regulation']);

                    foreach ($dataDetailGet['import_regulation_post_border'] as $key3 => $valueRegPostBord) {
                        $dataInsert = [
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'import_regulation_post_border',
                            'ZIRD_NMIJIN' => $valueRegPostBord['nama_ijin'] ?? $valueRegPostBord['name'],
                            'ZIRD_KDIJIN' => $valueRegPostBord['kd_ijin'] ?? $valueRegPostBord['kode_ijin'],
                            'ZIRD_DESC' => $valueRegPostBord['desc'] ?? $valueRegPostBord['deskripsi'],
                            'ZIRD_BEALIST' => json_encode($valueRegPostBord['dok_pabean']),
                            'ZIRD_LEGAL' => $valueRegPostBord['legal'] ?? '',
                            'ZIRD_MODUL' => $valueRegPostBord['modul'],
                            'ZIRD_SKEPNO' => $valueRegPostBord['nomor_skep'] ?? ''
                        ];
                        // logger(json_encode($dataInsert));
                        $dataRegCreate[] = INSWDataRegDet::create($dataInsert);
                    }

                    foreach ($dataDetailGet['export_regulation'] as $key4 => $valueExport) {
                        $dataInsert = [
                            'ZID_HSCODE' => $getHSCode,
                            'ZIRD_TYPE' => 'export_regulation',
                            'ZIRD_NMIJIN' => $valueExport['name'],
                            'ZIRD_KDIJIN' => '',
                            'ZIRD_DESC' => $valueExport['deskripsi'],
                            'ZIRD_BEALIST' => '',
                            'ZIRD_LEGAL' => $valueExport['legal'],
                            'ZIRD_MODUL' => $valueExport['modul'],
                            'ZIRD_SKEPNO' => $valueExport['nomor_skep'] ?? ''
                        ];
                        $dataRegCreate[] = INSWDataRegDet::create($dataInsert);
                    }

                    logger(json_encode($dataRegCreate));

                    foreach ($dataDetailGet['dok_kepabean_import_border'] as $key5 => $valueDoc) {
                        INSWDataDocBeaMaster::updateOrCreate([
                            'ZIDBD_DOCCD' => $valueDoc['kd_dokumen'],
                        ], [
                            'ZIDBD_DOCCD' => $valueDoc['kd_dokumen'],
                            'ZIDBD_DOCNM' => $valueDoc['nm_dokumen'],
                            'ZIDBD_DOCNMINTR' => $valueDoc['uraian_intr'],
                            'ZIDBD_LINK' => $valueDoc['ket_link'],
                            'ZIDBD_TLINK' => $valueDoc['ket_text_link'],
                            'ZIDBD_DESC' => $valueDoc['keterangan'],
                            'ZIDBD_DESCINTR' => $valueDoc['keterangan_intr'],
                        ]);
                    }

                    foreach ($dataDetailGet['dok_kepabean_import_post_border'] as $key6 => $valueDocPost) {
                        INSWDataDocBeaMaster::updateOrCreate([
                            'ZIDBD_DOCCD' => $valueDocPost['kd_dokumen'],
                        ], [
                            'ZIDBD_DOCCD' => $valueDocPost['kd_dokumen'],
                            'ZIDBD_DOCNM' => $valueDocPost['nm_dokumen'],
                            'ZIDBD_DOCNMINTR' => $valueDocPost['uraian_intr'],
                            'ZIDBD_LINK' => $valueDocPost['ket_link'],
                            'ZIDBD_TLINK' => $valueDocPost['ket_text_link'],
                            'ZIDBD_DESC' => $valueDocPost['keterangan'],
                            'ZIDBD_DESCINTR' => $valueDocPost['keterangan_intr'],
                        ]);
                    }


                    if (!empty($this->search)) {
                        if (ctype_digit($this->search)) {
                            INSWDataSatDetail::where('ZID_HSCODE', $getHSCode)
                                ->forceDelete();
                        } else {
                            INSWDataSatDetail::where('ZID_HSCODE', $getHSCode)
                                ->delete();
                        }
                    }

                    $satImp = [];
                    foreach ($dataDetailGet['refSatuan']['impor'] as $keySatImp => $valueSatimp) {
                        $satImp[] = INSWDataSatDetail::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                        ], [
                            'ZISD_TYPE' => 'import',
                            'ZISD_SERI' => $valueSatimp['seri'],
                            'ZISD_JENIS' => $valueSatimp['kd_satuan'],
                            'ZISD_SATUAN' => $valueSatimp['ur_satuan'],
                        ]);
                    }

                    $satExp = [];
                    foreach ($dataDetailGet['refSatuan']['expor'] as $keySatImp => $valueSatimp) {
                        $satExp[] = INSWDataSatDetail::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                        ], [
                            'ZISD_TYPE' => 'export',
                            'ZISD_SERI' => $valueSatimp['seri'],
                            'ZISD_JENIS' => $valueSatimp['kd_satuan'],
                            'ZISD_SATUAN' => $valueSatimp['ur_satuan'],
                        ]);
                    }

                    $hasilData[] = [
                        'status' => true,
                        'hsCode' => $getHSCode,
                        'message' => 'Data berhasil di update',
                        'storedMaster' => $masterCreate,
                        'storedPenjelasanDet' => $jlsCreate,
                        'storedStatusDet' => $satExp
                    ];
                } else {
                    $hasilData[] = [
                        'status' => false,
                        'hsCode' => $getHSCode,
                        'message' => 'Data detail sisa tidak ditemukan !!',
                        'data' => $dataDetail,
                        'storedMaster' => [],
                        'storedPenjelasanDet' => [],
                        'storedStatusDet' => []
                    ];
                }
            } else {
                $hasilData[] = [
                    'status' => false,
                    'hsCode' => $getHSCode,
                    'message' => 'Data detail tidak ditemukan !!',
                    'storedMaster' => [],
                    'storedPenjelasanDet' => [],
                    'storedStatusDet' => []
                ];
            }
        } catch (\Throwable $th) {
            $hasilData[] = [
                'status' => false,
                'hsCode' => $getHSCode,
                'message' => 'Ada error di server !!',
                'data' => [
                    $th->getMessage(),
                    $th->getLine()
                ],
                'storedMaster' => [],
                'storedPenjelasanDet' => [],
                'storedStatusDet' => []
            ];
        }
    }

    public function getData($hsCode)
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/ref/hscode/komoditas?hs_code=' . $hsCode;

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en,en-US;q=0.7,id;q=0.3',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'Authorization' => 'Basic eyJhbGciOiJSUzI1NiIsInR5cCI6ImJzYStqd3QiLCJraWQiOiJoUUNJcUJsWXRTYVZ1UjFWaFpGWGZpTWdrR2NoLS1hMG84NU5OTmxMR2xnIn0.eyJpYXQiOjE3NTc1NzI3MjksImV4cCI6MTc1NzYwOTk5OSwiYXVkIjoiaHR0cHM6Ly9pbnN3LmdvLmlkIiwiaXNzIjoiaHR0cHM6Ly9zc28uaW5zdy5nby5pZCIsInN1YiI6ImEwYWMwOWZhLTU2NzMtNDJjNi05ZmUxLTQxMWU2YzkzNzEwYSIsImp0aSI6IlUyRnNkR1ZrWDEvK2JvUTduZ3pnTHBSMWJRblNXVEFxYUw5dlVWZ2VpR3QvZG1PbWE3cm02NWRGN2Q5a0xpMUNWdEI4Qkp2ZG5mcmk1bVR2ZEZEeEVNTjBPZDBZWHl1Tm8zUktFcHBIVkhUczFicGN4T2dtNEU3bEpja3hDWEpBbk5xc0xCSmVTdjBJTE4yOFVUVlROVmtJdVBKTUpDMUxWRnJuMHJJMzhSRVkxbVBOYkVkajFiaFpMUHIyZmVmWk4vOVZkV3l3ei9TRnZqcmt1L0FVQnJyQ3o5dVNQR2dscndpTXBRZGlQNk9wQnhmWmJnclNOY3dEcFJISmpaUkkifQ.NQ24Rpuppzqq5viOmFh3aVgTpTWDZj-VCOVxSpePuJV_GxmPgRVJTimr_5D_GxNFNHm2S4bpxPA6VJtADapsi1S90cdBFuw76d6cq_Za-5Gpxftwfv26JT5PmRdU5ctXloZXqJvyRo4fL9PTrAi2m5-qSfMCWoXayHp-S4OKorxWKPeB2OOdeIedsBtEplETLhIQBj8QC8zq_rj_ucGI7iADbkexZyixNe3wSaR8sOjaigZO97goHoQd3SWIoPz-1QZZECutEueOOCNOiijFYYKkRxr5GYt8HbjZDL6SttKN4BReqlle2bjn0LTX8-gYO3hLbJiMWup3maSJM94HNA',
                'Origin' => 'https://insw.go.id',
                'Connection' => 'keep-alive',
                'Referer' => 'https://insw.go.id/',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-site',
                'If-None-Match' => 'W/"6d28-6m56MLyabkKGcY+hqixQFXrZ90g:dtagent10319250807130352az0t"',
                'Priority' => 'u=0'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
