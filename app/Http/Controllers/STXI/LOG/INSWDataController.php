<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Models\STXI\LOG\INSWDataMaster;
use App\Models\STXI\LOG\INSWDataJlsDetail;
use App\Models\STXI\LOG\INSWDataSatDetail;

class INSWDataController extends BaseController
{
    public function getData($hsCode)
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/ref/hscode/komoditas?hs_code=' . $hsCode;

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }

    public function getListMaster($hsCode = '', $size = 200)
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/cms/hscode?keyword=' . $hsCode . '&size=' . $size . '&from=0';

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }

    public function getListHSCode($hsCode = '', $maxSize = 200)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', '10800');
        $data = $this->getListMaster($hsCode === 0 || !empty($hsCode) ? '' : $hsCode, $maxSize)['data'][0]['result'];

        $hasilData = [];
        foreach ($data as $key => $value) {
            $getHSCode = $value['_source']['hs_code_format'];

            try {
                $dataDetail = $this->getData($getHSCode);

                if (!empty($dataDetail)) {
                    if (isset($dataDetail['data'][0])) {
                        $dataDetailGet = $dataDetail['data'][0];
                        $dataHSParent = $dataDetailGet['hsParent'][0];
                        $dataMFN = $dataDetailGet['mfn'][0];

                        $masterCreate = INSWDataMaster::updateOrCreate([
                            'ZID_HSCODE' => $getHSCode,
                        ], [
                            'ZID_HSCODE' => $getHSCode,
                            'ZID_BAGIAN' => $dataDetailGet['bagian'],
                            'ZID_BAB' => $dataDetailGet['bab'],
                            'ZID_HSPRNT' => $dataHSParent['hs_code_format'],
                            'ZID_HSPRNT_DESC_ID' => $dataHSParent['ur_id'],
                            'ZID_HSPRNT_DESC_EN' => $dataHSParent['ur_en'],
                            'ZID_HSPRNT_FRMT' =>  isset($dataDetailGet['hsParent'][1]) ? $dataDetailGet['hsParent'][1]['hs_code_format'] : '',
                            'ZID_HSPRNT_FRMT_DESC_ID' =>  isset($dataDetailGet['hsParent'][1]) ? $dataDetailGet['hsParent'][1]['ur_id'] : '',
                            'ZID_HSPRNT_FRMT_DESC_END' => isset($dataDetailGet['hsParent'][1]) ? $dataDetailGet['hsParent'][1]['ur_en'] : '',
                            'ZID_MFN_BM' => isset($dataMFN['bm'][0]) ? $dataMFN['bm'][0]['bm'] : '',
                            'ZID_MFN_PPN' => isset($dataMFN['ppn'][0]) ? $dataMFN['ppn'][0]['ppn'] : '',
                            'ZID_MFN_PPH' => isset($dataMFN['pph'][0]) ? $dataMFN['pph'][0]['pph'] : '',
                            'ZID_KOND' => $dataDetailGet['kondisiTertentu'],
                        ]);

                        $jlsCreate = [];
                        foreach ($dataDetailGet['bab_penjelasan'] as $keyJls => $valueJls) {
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
                            $jlsCreate[] = INSWDataJlsDetail::updateOrCreate([
                                'ZID_HSCODE' => $getHSCode,
                                'ZIJD_TYPE' => 'bagian_en',
                            ], [
                                'ZID_HSCODE' => $getHSCode,
                                'ZIJD_TYPE' => 'bagian_en',
                                'ZIJD_DET_ID' => $valueJls4,
                            ]);
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
                        foreach ($dataDetailGet['refSatuan']['impor'] as $keySatImp => $valueSatimp) {
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

        $dataRetSuccess = array_filter($hasilData, function($f) {
            return $f['status'] === true;
        });

        $dataRetFail = array_filter($hasilData, function($f) {
            return $f['status'] === false;
        });

        return [
            'success' => array_values($dataRetSuccess),
            'failed' => array_values($dataRetFail)
        ];
    }
}
