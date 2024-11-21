<?php

namespace App\Traits\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\AMS\ApprovalMapDetail;
use App\Models\AMS\ApprovalTokenDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Redis;
use DB;
use Blade;
use App\Http\Requests\AMS\ApprovalRunningApproveActionRequest;
use Storage;

use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalHistDetail;
use App\Models\AMS\ApprovalAttachSet;
use App\Models\AMS\ApprovalAttachHist;

use App\Jobs\AMS\EmailNotificationQueue;
use App\Models\PORTAL\PortalUserDet;

trait ApprovalActionTraits
{
    public function approveAction(ApprovalRunningApproveActionRequest $request)
    {
        $checkLatestOrder = 0;
        $getLatestData = null;
        if ($request->has('token') && !empty($request->token)) {
            $getLatestData = ApprovalHistDetail::where('amsm_id', $request->amsm_id)
                ->where('amshd_token', $request->token)
                ->with('mapdet')
                ->first();

            $checkLatestOrder = (int)$getLatestData->mapdet->amsmd_order;
        }

        // Fetch Approval map
        $dataMaster = ApprovalMaster::where('id', $request->amsm_id)->with(
            'det',
            function ($f) use ($checkLatestOrder) {
                $f->orderBy('amsmd_order');

                if ($checkLatestOrder > 0) {
                    $f->where('amsmd_order', '>=', $checkLatestOrder);
                }
            }
        )
            ->with('apprvSet')
            ->first();

        // Check if quota more than 0 then using quota
        if ($dataMaster->apprvSet->amssd_quotkn > 0) {
            $getToken = ApprovalTokenDetail::where('amsm_id', $request->amsm_id);

            if ($request->has('token') && !empty($request->token)) {
                if (!empty($getLatestData)) {
                    $useToken = $getLatestData->amstd_token;
                } else {
                    $useToken = (clone $getToken)->first()->amstd_token;
                }
            } else {
                $useTokenTest = (clone $getToken)->whereDoesntHave('hist')->first();

                if (empty($useTokenTest)) {
                    return $this->handleError('Your quota is empty, please consult administrator !!');
                } else {
                    // Check if key already running
                    $useTokenCheckRunning = (clone $getToken)->with('hist', function ($f) {
                        $f->where('amshd_stat', 'receive');
                    })->whereHas('hist')
                        ->get()
                        ->toArray();

                    if (!empty($useTokenCheckRunning)) {
                        $runningToken = [];
                        foreach ($useTokenCheckRunning as $keyTokenCheck => $valueTokenCheck) {
                            $dataSent = $valueTokenCheck['hist'];
                            foreach (array_values($dataSent) as $keySent => $valueSent) {
                                $getParam = json_decode($valueSent['amshd_paramstore']);

                                if (isset($getParam->msgkey)) {
                                    $cekValue = $getParam->data->{$getParam->msgkey};
                                    $checkJSON = is_string($request->data) ? json_decode($request->data, true) : $request->data;

                                    if ($cekValue == $checkJSON[$getParam->msgkey]) {
                                        $runningToken[] = $checkJSON[$getParam->msgkey];
                                    }
                                } else {
                                    $runningToken[] = $getParam['data'][array_keys($getParam['data'])[0]];
                                }
                            }
                        }

                        if (count($runningToken) > 0) {
                            return $this->handleError('you already send Approval, please d');
                        }
                        $useToken = $useTokenTest->amstd_token;
                    } else {
                        $useToken = $useTokenTest->amstd_token;
                    }
                }
            }
        } else {
            $useToken = Str::random(50);
            ApprovalTokenDetail::create([
                'p_u_username' => $request->username,
                'amsm_id' => $request->amsm_id,
                'amstd_token' => $useToken,
            ]);
        }

        // Check if there is any content using variable on recepient
        preg_match_all("/\{{(.*?)\}}/", str_replace('$', '', $dataMaster->apprvSet->amssd_content), $matches);
        $listVariable = array_values(array_filter($matches[1], function ($fc) {
            return !str_contains($fc, 'fullname') && !str_contains($fc, "['");
        }));

        if (count($listVariable) > 0) {
            if ($request->has('data')) {
                $checkJSON = is_string($request->data) ? json_decode($request->data, true) : $request->data;
                $checkFil = array_values(array_filter($listVariable, function ($f) use ($request, $checkJSON) {
                    return !in_array($f, array_keys($checkJSON));
                }));

                if (count($checkFil) > 0) {
                    return $this->handleError("you hasn't provide some data keys on request!!", $checkFil);
                }

                if ($request->has('msgkey') && !empty($request->msgkey)) {
                    $keyRequest = $checkJSON[$request->msgkey];
                    $cekHist = ApprovalHistDetail::where('amsm_id', $request->amsm_id)->where('amshd_paramstore', 'like', "%" . $keyRequest . "%")->first();

                    if (!empty($cekHist) && $useToken !== $cekHist->amstd_token) {
                        return $this->handleError("Key " . $keyRequest . " already submited !!", [
                            'hist' => $cekHist,
                            'token_used' => $useToken
                        ]);
                    }
                }
            } else {
                return $this->handleError("you hasn't provide data keys on request!!", $listVariable);
            }
        }

        // Start Calculating approval
        $hist = [];
        $getfirstOrder = $checkLatestOrder;
        foreach ($dataMaster->det as $keyDet => $valueDet) {
            $checkLatestToken = ApprovalHistDetail::where('amsm_id', $request->amsm_id)
                ->with('mapdet')
                ->with('senderUser')
                ->with('receiveUser')
                ->with('attch')
                ->where('amstd_token', $useToken);

            $checkLatest = (clone $checkLatestToken)->orderBy('id', 'desc')->first();
            $checkFirst = (clone $checkLatestToken)->orderBy('id', 'asc')->first();

            $nextStat = 'sent';
            if (!empty($checkLatest)) {
                $nextStat = $valueDet['amsmd_order'] != $checkLatest->mapdet->amsmd_order && $request->stat === 1
                    ? 'approve'
                    : (
                        $valueDet['amsmd_order'] != $checkLatest->mapdet->amsmd_order && $request->stat === 0
                        ? 'reject'
                        : 'sent'
                    );
            }

            $histToken = Str::random(50);
            $toEmail = '';

            $useTokenCreate = ApprovalTokenDetail::where('amstd_token', $useToken)->first();

            // If First or now order more than last order
            if ((int) $valueDet['amsmd_order'] > $checkLatestOrder || empty($checkFirst)) {
                // If Next Order
                if ($valueDet['amsmd_order'] == (int) $checkLatestOrder + 1) {
                    $this->sendingApproval($request, $dataMaster, $checkFirst, $keyDet, $valueDet, $histToken, $useToken, $nextStat);
                } else {
                    break;
                }
            } else {
                // If last order
                if (!isset($dataMaster->det[$checkLatestOrder])) {
                    $this->sendingApproval($request, $dataMaster, $checkFirst, $keyDet, $valueDet, $histToken, $useToken, $nextStat, true);
                    // Delete used token
                    ApprovalTokenDetail::where('id', $useTokenCreate->id)->delete();
                }
            }

            if ($nextStat === 'reject') {
                ApprovalHistDetail::where('amstd_token', $useToken)->delete();
            }
        }

        return $this->handleResponse($hist, 'Success');
    }

    public function approveHist(Request $request)
    {
        $hist = new ApprovalHistDetail;

        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $key => $value) {
                if (isset($value['step']) && $value['step'] === 'or') {
                    $hist = (clone $hist)->orwhere($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                } else {
                    $hist = (clone $hist)->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                }
            }
        }

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)->with('senderUser', 'receiveUser')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            return $this->handleResponse($datanya, 'Data Fetched');
        } else {
            return $this->handleError('No data found !!', []);
        }
    }

    public function approveListForNotif($uname)
    {
        return ApprovalHistDetail::where('amshd_username_apprv', $uname)->get();
    }

    public function getMasterApprovalByToken($token, $tokenHist, $isView = false)
    {
        $cekToken = ApprovalTokenDetail::with([
            'hist' => function ($f) use ($tokenHist) {
                $f->with('mapdet')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->toArray();
            }
        ])
            ->with([
                'selectedHist' => function ($f) use ($tokenHist) {
                    $f->with(
                        'mapdet',
                        'attch'
                    )
                        ->where('amshd_token', $tokenHist)
                        ->orderBy('id', 'asc')
                        ->get()
                        ->toArray();
                },
            ])
            ->where('amstd_token', $token)
            ->withTrashed()
            ->first();

        // return $cekToken;

        // If token is not deleted and if latest token order same with current token order or if not view mode
        if (
            (
                !empty($cekToken) &&
                !empty($cekToken->selectedHist) &&
                $cekToken->hist[count($cekToken->hist) - 1]['mapdet']['amsmd_order'] === $cekToken->selectedHist[count($cekToken->selectedHist) - 1]['mapdet']['amsmd_order']
            ) || $isView == 1
        ) {
            // Update Readed hist if read only
            if ($isView == 1) {
                foreach ($cekToken->selectedHist as $keyRead => $valueRead) {
                    $this->readUpdateFlag($valueRead->id);
                }
            }

            $getSender = array_values(array_filter((clone $cekToken)->toArray()['selected_hist'], function ($f) {
                return $f['amshd_stat'] !== 'receive';
            }))[0];
            $getReceiver = array_values(array_filter((clone $cekToken)->toArray()['selected_hist'], function ($f) {
                return $f['amshd_stat'] === 'receive';
            }))[0];

            $hasil = ApprovalMaster::where('id', $cekToken['amsm_id'])->with('det')->with('apprvSet', function ($f) {
                $f->get();
            })->first();

            if (!empty($hasil->apprvSet->amssd_content)) {
                $hasilnya = $hasil->apprvSet->amssd_content;
                $checkJSON = is_string($getReceiver['amshd_paramstore']) ? json_decode($getReceiver['amshd_paramstore'], true)['data'] : $getReceiver['amshd_paramstore']['data'];

                $hasil->apprvSet->amssd_content = $this->convertValuetoContent(
                    $hasil->apprvSet->amssd_content,
                    $getSender['p_u_username'],
                    $getReceiver['p_u_username'],
                    $checkJSON,
                );
                $hasilnya = $hasil->toArray();
            } else {
                $hasilnya = $hasil->toArray();
            }

            return $this->handleResponse(array_merge($hasilnya, ['token' => $cekToken]), 'Token found !!');
        }

        return $this->handleError('Token not found !! please check again !!');
    }

    public function sendingApproval($request, $dataMaster, $checkFirst, $keyDet, $valueDet, $histToken, $useToken, $nextStat, $isLast = false)
    {
        $getSender = PortalUserDet::where('u_username', $request->username)->first();
        // Sent Notif
        $hist = ApprovalHistDetail::create([
            'p_u_username' => $request->username,
            'amsm_id' => $request->amsm_id,
            'amsmd_id' => $valueDet['id'],
            'amshd_token' => $histToken,
            'amstd_token' => $useToken,
            'amshd_username_apprv' => $isLast // IF Approval Complete send back to requestor
                ? $checkFirst->p_u_username
                : $valueDet['amsmd_username'],
            'amshd_stat' => $nextStat,
            'amshd_remarks' => $request->remarks,
            'amshd_paramstore' => json_encode([
                'data' => is_string($request->data) ? json_decode($request->data, true) : $request->data,
                'onApproval' => $request->has('onApproval') ? $request->onApproval : [],
                'onDone' => $request->has('onDone') ? $request->onDone : [],
                'msgkey' => $request->has('msgkey') ? $request->msgkey : ''
            ])
        ]);

        $toEmail = $valueDet['amsmd_username'];

        // Receive Notif
        $hist = ApprovalHistDetail::create([
            'p_u_username' => $valueDet['amsmd_username'],
            'amsm_id' => $request->amsm_id,
            'amsmd_id' => $valueDet['id'],
            'amshd_token' => $histToken,
            'amstd_token' => $useToken,
            'amshd_username_apprv' => '',
            'amshd_stat' => 'receive',
            'amshd_remarks' => $request->remarks,
            'amshd_paramstore' => json_encode([
                'data' => is_string($request->data) ? json_decode($request->data, true) : $request->data,
                'onApproval' => $request->has('onApproval') ? $request->onApproval : [],
                'onDone' => $request->has('onDone') ? $request->onDone : [],
                'msgkey' => $request->has('msgkey') ? $request->msgkey : ''
            ])
        ]);

        // If using on Approval method trigger
        if ($request->has('onApproval') && count($request->onApproval) > 0) {
            $this->apiPointData(
                $request->onApproval['url'],
                $request->onApproval['methods'],
                $request->onApproval['params'] ?? [],
                $request->onApproval['headers'] ?? [],
                [
                    'approval' => [
                        'status' => $nextStat,
                        'remarks' => $request->remarks,
                    ]
                ]
            );
        }

        // If Attachment setting is setted up
        if ($dataMaster->apprvSet->amssd_attachment) {
            $cekSettingAttch = ApprovalAttachSet::where('aasd_id', $dataMaster->apprvSet->id)->get();
            $storeData = [];

            foreach ($cekSettingAttch as $keyAttch => $valueAttch) {
                $cekParam = json_decode(str_replace(search: "{{username}}", replace: $request->username, subject: $valueAttch->aats_param));
                foreach (json_decode($valueAttch->aats_param) as $keyParam => $valueParam) {
                    // $dataReq = json_decode($request->data);
                    $dataReq = is_string($request->data) ? (object) json_decode($request->data, true) : (object) $request->data;
                    if (isset($dataReq->{$keyParam})) {
                        $cekParam->{$keyParam} = $dataReq->{$keyParam};
                    } else {
                        ApprovalHistDetail::where('amshd_token', $histToken)->delete();
                        return $this->handleError('Param ' . $keyParam . ' is needed, please consult administrator !!');
                    }
                }

                $filenya = [];
                if ($request->has('file')) {
                    $filenya = $request->file('file');
                } else {
                    $checkHistory = $checkLatest->attch;

                    if (!empty($checkHistory)) {
                        foreach ($checkHistory as $keyFiles => $valueFiles) {

                            $getURLLink = json_decode($valueFiles->amaad_dl_link);
                            $getFile = $this->apiPointData(
                                $getURLLink->url,
                                $getURLLink->method,
                                $getURLLink->param ?? [],
                                $getURLLink->header ?? []
                            );

                            if ($getFile) {
                                ApprovalAttachHist::updateOrCreate([
                                    'amshd_id' => $hist->id,
                                    'amaad_source' => $valueFiles->amaad_source,
                                ], [
                                    'amshd_id' => $hist->id,
                                    'amaad_source' => $valueFiles->amaad_source,
                                    'amaad_filename' => $valueFiles->amaad_filename,
                                    'amaad_path' => $valueFiles->amaad_path,
                                    'amaad_size' => $valueFiles->amaad_size,
                                    'amaad_dl_link' => $valueFiles->amaad_dl_link,
                                ]);
                            }
                        }
                    }
                }

                // IF First Time send approval
                if (!$request->has('token') || empty($request->token)) {
                    foreach ($filenya as $keyFiles => $valueFiles) {
                        // Store attachment to storage
                        $storeDataCek = $this->apiPointData(
                            $valueAttch->aats_host,
                            $valueAttch->aats_method,
                            $cekParam,
                            $valueAttch->aats_header,
                            [],
                            $valueFiles,
                            $valueFiles->getClientOriginalName()
                        );

                        if ($storeDataCek) {
                            if ($request->has('downloadLinks') && count($request->downloadLinks) > 0) {
                                $linkDownload = $request->downloadLinks[$keyFiles];
                                $convLink = $this->convertValuetoContent($linkDownload, $valueDet['amsmd_username'], $valueDet['amsmd_username'], $storeDataCek['data'], '');
                                logger($linkDownload);
                            } else {
                                $convLink = '';
                            }
                            // Jika menggunakan DMS Sebagai Storage
                            if (str_contains($valueAttch->aats_name, 'DMS')) {
                                $storeData[] = $storeDataCek;
                                ApprovalAttachHist::updateOrCreate([
                                    'amshd_id' => $hist->id,
                                    'amaad_source' => $storeDataCek['data']['dfm_id'],
                                ], [
                                    'amshd_id' => $hist->id,
                                    'amaad_source' => $storeDataCek['data']['dfm_id'],
                                    'amaad_filename' => $valueFiles->getClientOriginalName(),
                                    'amaad_path' => $storeDataCek['data']['path'],
                                    'amaad_size' => $storeDataCek['data']['ddm_doc_size'],
                                    'amaad_dl_link' => $convLink
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // If Email notification is on
        if ($dataMaster->apprvSet->amssd_isemail) {
            $queueSet = new EmailNotificationQueue(
                $request->username,
                $toEmail,
                $request->subject ?? $dataMaster->ams_title,
                $valueDet->amsmd_reqaprv,
                $dataMaster->ams_content,
                $useToken . '/' . $histToken
            );

            dispatch($queueSet)->onQueue('sendEmailQueue');
        }

        Redis::publish('portalv2', json_encode([
            'app' => 'portal_notif',
            'message' => "You have new notification from {$getSender->pud_first_name} {$getSender->pud_last_name}",
            'type' => 'info',
            'data' => [
                'username_dest' => empty($checkFirst) ? $valueDet['amsmd_username'] : $checkFirst->p_u_username
            ]
        ]));
    }

    public function convertValuetoContent($content, $fromUname, $toUname, $param = [], $token = '')
    {
        $getUsersFrom = PortalUserDet::where('u_username', $fromUname)->first();
        $getUsers = PortalUserDet::where('u_username', $toUname)->first();

        // logger("{$getUsers->pud_first_name} {$getUsers->pud_last_name}");
        // Convert fullname Recepient variable

        // If user exists then use fullname instead
        // if (!empty($getUsers)) {
        //     $convertContent = str_replace(search: "{{recipient_fullname}}", replace: "{$getUsers->pud_first_name} {$getUsers->pud_last_name}", subject: $content);
        // } else {
        //     $convertContent = str_replace(search: "{{recipient_fullname}}", replace: $toUname, subject: $content);
        // }

        // // Convert fullname sender variable

        // // If user exists then use fullname instead
        // if (!empty($getUsersFrom)) {
        //     $convertContent = str_replace(search: "{{fullname}}", replace: "{$getUsersFrom->pud_first_name} {$getUsersFrom->pud_last_name}", subject: $convertContent);
        // } else {
        //     $convertContent = str_replace(search: "{{fullname}}", replace: $fromUname, subject: $convertContent);
        // }

        // $convertContent = str_replace(search: "{{linkapproval}}", replace: env('FE_URL') . "/ams/approvalAction/{$token}", subject: $convertContent);

        // logger($param);

        $params = [];
        foreach ($param as $keyVar => $valueVar) {
            $params[$keyVar] = $valueVar;
            // $convertContent = str_replace(search: "{{" . $keyVar . "}}", replace: $valueVar, subject: $convertContent);
        }

        logger(json_encode($params[$keyVar]));

        $convertContent = Blade::render($content, array_merge([
            'recipient_fullname' => !empty($getUsers) ? "{$getUsers->pud_first_name} {$getUsers->pud_last_name}" : $toUname,
            'fullname' => !empty($getUsersFrom) ? "{$getUsersFrom->pud_first_name} {$getUsersFrom->pud_last_name}" : $fromUname
        ], $params));

        return $convertContent;
    }

    public function readUpdateFlag($id)
    {
        $update = ApprovalHistDetail::where('id', $id)->update([
            'readed_at' => date('Y-m-d H:i:s')
        ]);

        return $this->handleResponse($update, 'Notif readed');
    }

    public function readAllNotif()
    {
        $update = ApprovalHistDetail::whereNull('readed_at')->update([
            'readed_at' => date('Y-m-d H:i:s')
        ]);

        return $this->handleResponse($update, 'Notif readed');
    }

    public function viewListSentApproval(Request $request)
    {
        $data = ApprovalHistDetail::select(
            'p_u_username',
            'amstd_token',
            'amshd_paramstore',
            'amsm_id',
            DB::raw('MAX(created_at) as created_at')
        );

        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $key => $value) {
                if (!empty($value['value'])) {
                    if (isset($value['step']) && $value['step'] === 'or') {
                        $data->orwhere($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                    } else {
                        $data->where($value['cols'], $value['param'], $value['param'] === 'like' ? "%{$value['value']}%" : $value['value']);
                    }
                }
            }
        }

        $data->groupBy(
            'p_u_username',
            'amstd_token',
            'amshd_paramstore',
            'amsm_id'
        );

        $hasil = [];
        foreach ($data->get()->toArray() as $key => $value) {
            $cekLast = ApprovalHistDetail::with('mapdet')
                ->where('amshd_stat', 'receive')
                ->where('amstd_token', $value['amstd_token'])
                ->orderBy('created_at', 'desc')
                ->first();

            $cekDet = ApprovalMapDetail::select('amsmd_order')->where('amsm_id', (int) $value['amsm_id'])->groupBy('amsmd_order')->get();
            $hasilDet = 0;
            foreach ($cekDet as $key => $valueDet) {
                if ((int) $valueDet->amsmd_order <= (int) $cekLast->mapdet->amsmd_order) {
                    $hasilDet += 1;
                }
            }

            $hasil[] = array_merge($value, [
                'percent' => $hasilDet / count($cekDet) * 100
            ]);
        }

        return $this->handleResponse($hasil, 'Data Fetched');
    }

    public function apiPointData($url, $method, $param = [], $headers = [], $optionalReturn = [], $file = null, $filename = '')
    {
        $guzz = new \GuzzleHttp\Client();

        try {
            $multipart = [];
            if ($file) {
                $multipart = [];
                foreach ($param as $key => $value) {
                    $multipart['multipart'][] = [
                        'name' => $key,
                        'contents' => $value,
                        'headers' => json_decode($headers, true)
                    ];
                }

                $expForExt = explode('.', $filename);
                $getExt = $expForExt[count($expForExt) - 1];

                $multipart['multipart'][] = [
                    'name' => 'file',
                    'contents' => $file,
                    'headers' => ['Content-Type' => $file->getMimeType()]
                ];

                $multipart['multipart'][] = [
                    'name' => 'filename',
                    'contents' => $filename,
                    'headers' => ['Content-Type' => 'application/json']
                ];

                $params = $multipart;
            } else {
                $params = [
                    'verify' => false,
                    'headers' => array_merge([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ], $headers),
                    'decode_content' => false,
                    'body' => count($param) > 0 ? json_encode(array_merge($param, $optionalReturn)) : json_encode([]),
                ];
            }

            $result = $guzz->request($method, $url, $params);

            return json_decode($result->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();

            $responseBodyAsString = $response->getBody()->getContents();
            // logger(message: $responseBodyAsString);
            return json_decode($responseBodyAsString, true);
        }
    }

    public function openFileBase64($base64File)
    {
        $extension = explode('/', explode(':', substr($base64File, 0, strpos($base64File, ';')))[1])[1];   // .jpg .png .pdf

        $replace = substr($base64File, 0, strpos($base64File, ',') + 1);

        // find substring fro replace here eg: data:image/png;base64,

        $image = str_replace($replace, '', $base64File);

        $image = str_replace(' ', '+', $image);

        $imageName = Str::random(10) . '.' . $extension;

        Storage::disk('local')->put($imageName, base64_decode($image));

        return Storage::disk('local')->url($imageName);
    }
}
