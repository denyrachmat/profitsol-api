<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\STXI\CEISA40\CEISARESPON;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Http\File;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\STXI\LOG\ImportCeisa40;
use App\Models\STXI\LOG\CeisaToken;

class Ceisa40UploaderController extends BaseController
{
    public function uploadData(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();
        $realFileName = $req->file('file')->getClientOriginalName();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_ceisa40/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_ceisa40/' . $nama_file);
        }


        if (str_contains($realFileName, '1.6') || str_contains($realFileName, '2.7I') || str_contains($realFileName, 'BC 4.0')) {
            logger('ini incoming !!');
            $state = 'INC';
        } else {
            $state = 'OUT';
        }

        $importer = new ImportCeisa40($state);

        Excel::import($importer, public_path('/storage/upload_ceisa40/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }

    public function apiPointData(
        $url,
        $method,
        $paramBody = [],
        $source = 'nle',
        $useToken = false
    ) {
        $endpoint = $source === 'nle'
            ? /* 'https://nlehub.kemenkeu.go.id/' */'https://apis-gw.beacukai.go.id/' . $url
            : ($source === 'sce'
                ? 'https://apis-gw.beacukai.go.id/v2/sce-ws/' . $url
                : ($source === 'browse-service'
                    ? 'https://apis-gw.beacukai.go.id/v2/browse-service/v1/' . $url
                    : ($source === 'parser'
                        ? 'https://apis-gw.beacukai.go.id/v2/parser/v1/' . $url
                        : $url
                    )
                )
            );

        $content = [];
        $guzz = new \GuzzleHttp\Client();

        try {
            if ($useToken) {
                $getToken = $this->cekToken();

                if (!empty($getToken)) {
                    $res = $guzz->request($method, $endpoint, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $getToken
                        ],
                        'body' => json_encode($paramBody),
                    ]);
                } else {
                    $res = $guzz->request($method, $endpoint, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ],
                        'body' => json_encode($paramBody)
                    ]);
                }
            } else {
                $res = $guzz->request($method, $endpoint, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'body' => json_encode($paramBody)
                ]);
            }
            // return 'masuk sini';
            $content['PARAM'] = $paramBody;
            $content['CURL'] = json_decode($res->getBody(), true);
            return $content['CURL'];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // return $endpoint;
            $response = $e->getResponse();

            $responseBodyAsString = $response->getBody()->getContents();
            return json_decode($responseBodyAsString, true);
        }
    }

    public function cekToken()
    {
        $getToken = CeisaToken::orderBy('created_at', 'desc');
        // Jika DB kosong
        if (empty(with(clone $getToken)->first())) {
            $login = $this->login();
            if ($login['status']) {
                $cekToken = with(clone $getToken)->first();

                return $cekToken->access_token;
            }
        } else {
            $dataToken = with(clone $getToken)->first();
            $from_time = strtotime($dataToken->created_at);
            $to_time = strtotime(date('Y-m-d H:i:s'));

            $minutes = round(abs($to_time - $from_time) / 60, 2);

            if ($minutes >= 5) {
                $cekLogin = $this->login();

                // logger('Cek Login : '. $cekLogin);

                $cekTokenNya = with(clone $getToken)->first();

                // logger('token succes : ' . $cekTokenNya->access_token);

                return $cekTokenNya->access_token;
            } else {
                return $dataToken->access_token;
            }
        }
    }

    public function login()
    {

        try {
            $sendData = $this->apiPointData(
                // 'auth-amws/v1/user/login',
                'nle-oauth/v1/user/login',
                'POST',
                [
                    'username' => 'erwinstx',
                    'password' => 'Erwin0123'
                ]
            );

            // logger($sendData);

            CeisaToken::create([
                'access_token' => $sendData['item']['access_token'],
                'refresh_token' => $sendData['item']['refresh_token'],
            ]);

            return $sendData;
        } catch (\Throwable $th) {
            // logger('Login Error!');
            // logger($th);
            return [
                'status' => 'failed',
                'message' => 'Login Error : ' . $th->getMessage()
            ];
            //throw $th;
        }
    }

    public function refreshToken()
    {
        try {
            $sendData = $this->apiPointData(
                'nle-oauth/v1/auth-amws/v1/user/update-token',
                'POST',
                [],
                'nle',
                true
            );

            CeisaToken::create([
                'access_token' => $sendData['item']['access_token'],
                'refresh_token' => $sendData['item']['refresh_token'],
            ]);

            return $sendData;
        } catch (\Throwable $th) {
            return [
                'status' => 'failed',
                'message' => $th->getMessage()
            ];
            //throw $th;
        }
    }

    public function getNopen(Request $request)
    {
        try {
            $url = 'browse/dokumen-pabean-portal?nomorIdentitas=015582513056000&page=1&idUser=3e158b35-16e4-4aaa-af76-d9c382284e6c';
            if ($request->has('kodeJalur') && !empty($request->kodeJalur)) {
                $url .= '&kodeJalur=' . $request->kodeJalur;
            }

            if ($request->has('namaPerusahaan') && !empty($request->namaPerusahaan)) {
                $url .= '&namaPerusahaan=' . $request->namaPerusahaan;
            }

            if ($request->has('namaPpjk') && !empty($request->namaPpjk)) {
                $url .= '&namaPpjk=' . $request->namaPpjk;
            }

            if ($request->has('kodeKantor') && !empty($request->kodeKantor)) {
                $url .= '&kodeKantor=' . $request->kodeKantor;
            }

            if ($request->has('nomorAju') && !empty($request->nomorAju)) {
                $url .= '&nomorAju=' . $request->nomorAju;
            }

            if ($request->has('nomorDaftar') && !empty($request->nomorDaftar)) {
                $url .= '&nomorDaftar=' . $request->nomorDaftar;
            }

            if ($request->has('size') && !empty($request->size)) {
                $url .= '&size=' . $request->size;
            } else {
                $url .= '&size=1000';
            }

            $sendData = $this->apiPointData(
                $url,
                'GET',
                [],
                'browse-service',
                true
            );

            if (count($sendData['data']) > 0) {
                $dataHasil = [];
                foreach ($sendData['data'] as $key => $value) {
                    if (!empty($value['namaRespon'])) {
                        if($request->has('isSaved') && $request->isSaved == 1) {
                            $insertCeisa = CEISARESPON::updateOrCreate([
                                'NOMOR_AJU' => $value['nomorAju'],
                                'RES_TYPE' => $value['namaRespon'],
                            ], [
                                'NOMOR_AJU' => $value['nomorAju'],
                                'NOMOR_DAFTAR' => $value['nomorDaftar'],
                                'RES_DATE' => date('Y-m-d H:i:s', strtotime($value['tanggalRespon'])),
                                'RES_TYPE' => $value['namaRespon'],
                                'RES_NO' => $value['nomorRespon'],
                                'TYPE_DOC' => $value['kodeDokumen'],
                                'TGL_DAFTAR' => date('Y-m-d H:i:s', strtotime($value['tanggalDaftar'])),
                            ]);
                        }

                        $dataHasil[] = [
                            'nopen' => $value['nomorDaftar'],
                            'noaju' => $value['nomorAju'],
                            'tglpen' => $value['tanggalDaftar'],
                            'respon' => [
                                'tipe' => $value['namaRespon'],
                                'no' => $value['nomorRespon'],
                                'tgl' => $value['tanggalRespon']
                            ],
                            'statInsert' => $request->has('isSaved') && $request->isSaved == 1 ? $insertCeisa : 'Not Saved',
                            'dataOri' => $value
                        ];
                    }
                }

                return [
                    'status' => true,
                    'message' => 'No aju ditemukan !',
                    'data' => $dataHasil,
                    // 'dataOri' => $sendData
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No aju tidak ditemukan !',
                    'data' => [],
                    'dataOri' => $sendData
                ];
            }
            // return $sendData;
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage()
            ];
            //throw $th;
        }
    }
}
