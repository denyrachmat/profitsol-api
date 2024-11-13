<?php

namespace App\Traits\STXI\LOG;
use Illuminate\Http\Request;

use App\Models\STXI\CEISA40\CEISARESPON;
use App\Models\STXI\LOG\EntitasMaster;
use App\Models\STXI\LOG\EntitasSkepDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\STXI\LOG\CeisaToken;


trait Ceisa40Traits
{
    public function apiPointData(
        $url,
        $method,
        $paramBody = [],
        $source = 'nle',
        $useToken = false,
        $useAuthX = false,
        $isFile = false,
        $usePortalAuth = true
    ) {
        $endpoint = $source === 'nle'
            ? /* 'https://nlehub.kemenkeu.go.id/' */ 'https://apis-gw.beacukai.go.id/' . $url
            : ($source === 'sce'
                ? 'https://apis-gw.beacukai.go.id/v2/sce-ws/' . $url
                : ($source === 'browse-service'
                    ? 'https://apis-gw.beacukai.go.id/v2/browse-service/v1/' . $url
                    : ($source === 'parser'
                        ? 'https://apis-gw.beacukai.go.id/v2/parser/v1/' . $url
                        : ($source === 'excel-service'
                            ? 'https://apis-gw.beacukai.go.id/excel-service/v1/' . $url
                            : ($source === 'report-parser'
                                ? 'https://apis-gw.beacukai.go.id/v2/report-parser/v1/' . $url
                                : $url
                            )
                        )
                    )
                )
            );

        $content = [];
        $guzz = new \GuzzleHttp\Client();

        try {

            if ($useToken) {
                $getToken = $this->cekToken($usePortalAuth);

                if (!empty($getToken)) {
                    if ($useAuthX) {
                        $headers = [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $getToken,
                            'Authorizationx' => 'Bearer ' . $getToken
                        ];
                    } else {
                        $headers = [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $getToken,
                            'Beacukai-Api-Key' => '6222a75e-1dbb-493e-9461-27f721097e9c'
                        ];
                    }
                    // logger(json_encode($headers));

                    $res = $guzz->request($method, $endpoint, [
                        // 'verify' => false,
                        'headers' => $headers,
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
                    'decode_content' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'body' => json_encode($paramBody)
                ]);
            }
            // return $headers;

            $content['PARAM'] = $paramBody;
            $content['CODE'] = $res->getStatusCode();
            // $content['CURL'] = json_decode($res->getBody(), true);

            if ($isFile) {
                return $res->getBody();
            }

            return json_decode($res->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // return $endpoint;
            $response = $e->getResponse();

            $responseBodyAsString = $response->getBody()->getContents();
            return json_decode($responseBodyAsString, true);
        }
    }

    public function cekToken($usePortalAuth)
    {
        $getToken = CeisaToken::orderBy('created_at', 'desc');
        // Jika DB kosong
        if (empty(with(clone $getToken)->first())) {
            if ($usePortalAuth) {
                $login = $this->loginPortal();
            } else {
                $login = $this->login();
            }
            // logger($login);
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
                // Refresh Token
                if ($usePortalAuth) {
                    $this->loginPortal();
                } else {
                    $this->login();
                }

                $cekTokenNya = with(clone $getToken)->first();

                return $cekTokenNya->access_token;
            } else {
                // logger($cekLogin);
                return $dataToken->access_token;
            }
        }
    }

    public function login()
    {
        try {
            $sendData = $this->apiPointData(
                'nle-oauth/v1/user/login',
                'POST',
                [
                    'username' => 'ujangstx',
                    'password' => 'Ujang0123'
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

    public function loginPortal()
    {
        try {
            $sendData = $this->apiPointData(
                'v2/authws/user/login',
                'POST',
                [
                    'username' => 'ujangstx',
                    'password' => 'Ujang0123'
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
                        if ($request->has('isSaved') && $request->isSaved == 1) {
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
                                'ID_HEADER' => $value['idHeader']
                            ]);
                        }

                        $dataHasil[] = [
                            'id' => $value['idHeader'],
                            'nopen' => $value['nomorDaftar'],
                            'noaju' => $value['nomorAju'],
                            'tglpen' => $value['tanggalDaftar'],
                            'respon' => [
                                'tipe' => $value['namaRespon'],
                                'no' => $value['nomorRespon'],
                                'tgl' => $value['tanggalRespon']
                            ],
                            'param' => $request->all(),
                            'statInsert' => $request->has('isSaved') && $request->isSaved == 1 ? $insertCeisa : 'Not Saved',
                            'dataOri' => $value
                        ];
                    } else {
                        if ($request->has('isSaved') && $request->isSaved == 1) {
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
                                'ID_HEADER' => $value['idHeader']
                            ]);
                        }

                        $dataHasil[] = [
                            'id' => $value['idHeader'],
                            'nopen' => $value['nomorDaftar'],
                            'noaju' => $value['nomorAju'],
                            'tglpen' => $value['tanggalDaftar'],
                            'respon' => [
                                'tipe' => $value['namaRespon'],
                                'no' => $value['nomorRespon'],
                                'tgl' => $value['tanggalRespon']
                            ],
                            'param' => $request->all(),
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

    public function getDetPerusahaan(Request $request): array
    {
        $hasil = [];
        foreach ($request->data as $key => $value) {
            $getDetilPerusahanPenerima = $this->apiPointData(
                'profil/perusahaan/data-perusahaan-by-npwp/?npwp=' . $value,
                'GET',
                [],
                'sce',
                true
            );

            $hasil[] = $getDetilPerusahanPenerima;

            if (isset($getDetilPerusahanPenerima['namaPerusahaan'])) {
                EntitasMaster::where('NPWP', $value)->delete();
                EntitasMaster::create([
                    'NPWP' => $value,
                    'NAMA' => $getDetilPerusahanPenerima['namaPerusahaan'],
                    'ALMT' => $getDetilPerusahanPenerima['alamatPerusahaan'] . ', ' . $getDetilPerusahanPenerima['rtRw'] . ', ' . $getDetilPerusahanPenerima['kelurahan'],
                    'KODEKTR' => '050900',
                    'NIB' => $getDetilPerusahanPenerima['nib'],
                ]);

                $getSkepPerusahaan = $this->apiPointData(
                    'GudangPlb/perusahanSkepFasilitas?idPerusahaanPajak=' . $value,
                    'GET',
                    [],
                    'parser',
                    true
                );

                if (!empty($getSkepPerusahaan)) {
                    foreach ($getSkepPerusahaan['data'] as $keySkep => $valueSkep) {
                        EntitasSkepDetail::where('NPWP', $value)->where('NOSKEP', $valueSkep['nomorSkep'])->delete();
                        EntitasSkepDetail::create([
                            'NPWP' => $value,
                            'NOSKEP' => $valueSkep['nomorSkep'],
                            'EFFDT' => $valueSkep['awalBerlaku'],
                        ]);
                    }
                }
            }
        }

        return $hasil;
    }

    public function getEntitas($idHeader)
    {
        $getEntitas = $this->apiPointData(
            'TdEntitas/findByIdHeader?idHeader=' . $idHeader,
            'GET',
            [],
            'parser',
            true
        );

        if (!empty($getEntitas)) {
            return $getEntitas;
        }
    }

    public function downloadExcel($noAju, $bc, $id, $isStore = true)
    {
        // logger(json_encode([$noAju, $bc, $id, $isStore]));
        $getDetilPerusahanPenerima = $this->apiPointData(
            'ekspor-xml/Xlsx?nomorAju=' . $noAju . '&idUser=adf9ea0f-de99-444d-b502-e4a474670624&kodeDokumen=' . $bc,
            'GET',
            [],
            'excel-service',
            true,
            false,
            true
        );

        if (is_array($getDetilPerusahanPenerima) && isset($getDetilPerusahanPenerima['Exception'])) {
            return $this->handleError($getDetilPerusahanPenerima['Exception']);
        }

        if (!empty($getDetilPerusahanPenerima)) {
            switch ($bc) {
                case '27':
                    $cekEntitas = $this->getEntitas($id);

                    $cekEntitas = array_values(array_filter($cekEntitas, function ($f) {
                        return $f['kodeEntitas'] == '7';
                    }));

                    if ($cekEntitas[0]['nomorIdentitas'] === '015582513056000') { // Jika Entitas pemilik dari sumitronics
                        $bcComp = 'BC 2.7';
                    } else { // Selain itu maka return
                        $bcComp = 'BC 2.7I';
                    }

                    break;

                default:
                    $splitStr = str_split((string) $bc);
                    $bcComp = 'BC ' . implode('.', $splitStr);

                    break;
            }

            $fileName = $bcComp . ' ' . $noAju . '.xlsx';

            if ($isStore) {
                Storage::put('public/ceisa40storage/' . $fileName, $getDetilPerusahanPenerima);
                return 'storage/app/public/ceisa40storage/' . $fileName;
            } else {
                Storage::put('public/upload_ceisa40/' . $fileName, $getDetilPerusahanPenerima);
                return 'storage/upload_ceisa40/' . $fileName;
            }
        } else {
            return $this->handleError('Failed fetching data from Portal Ceisa 4.0 !!');
        }
    }
}
