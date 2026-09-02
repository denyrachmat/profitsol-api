<?php

namespace App\Traits\AMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\AMS\ApprovalMapDetail;
use App\Models\AMS\ApprovalTokenDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Redis;
use DB;
use Blade;
use App\Http\Requests\AMS\ApprovalRunningApproveActionRequest;
use Storage;

use App\Models\AMS\ApprovalMaster;
use App\Models\AMS\ApprovalHistDetail;
use App\Models\AMS\ApprovalAttachSet;
use App\Models\AMS\ApprovalAttachHist;
use App\Models\AMS\ApprovalDocSignBox;

use App\Jobs\AMS\EmailNotificationQueue;
use App\Models\PORTAL\PortalUserDet;

trait ApprovalActionTraits
{
    public function approveAction(ApprovalRunningApproveActionRequest $request)
    {
        DB::beginTransaction();
        $checkLatestOrder = 0;
        $getLatestData = null;
        if ($request->has('token') && !empty($request->token)) {
            $getLatestData = ApprovalHistDetail::where('amsm_id', $request->amsm_id)
                ->where('amshd_token', $request->token)
                ->whereHas('mapdet')
                ->with('mapdet')
                ->first();

            $checkLatestOrder = (int) $getLatestData->mapdet->amsmd_order;
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
                    DB::rollBack();
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

                                // Only flag a duplicate when the current submission's
                                // msgkey actually matches an in-flight one. No msgkey
                                // (or no matching key) must never auto-block a re-send.
                                if (isset($getParam->msgkey) && !empty($getParam->msgkey)
                                    && isset($getParam->data) && isset($getParam->data->{$getParam->msgkey})) {
                                    $checkJSON = is_string($request->data) ? json_decode($request->data, true) : $request->data;
                                    if (is_array($checkJSON) && array_key_exists($getParam->msgkey, $checkJSON)
                                        && $getParam->data->{$getParam->msgkey} == $checkJSON[$getParam->msgkey]) {
                                        $runningToken[] = $checkJSON[$getParam->msgkey];
                                    }
                                }
                            }
                        }

                        if (count($runningToken) > 0) {
                            DB::rollBack();
                            return $this->handleError('you already send this Approval, please check again your data.', [
                                'runningToken' => $runningToken,
                                'token' => $useTokenTest->amstd_token,
                                'useTokenCheckRunning' => $useTokenCheckRunning
                            ]);
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

        // Also require any loop/collection the template iterates (e.g. @foreach($item_det ...)).
        // A missing collection would crash Blade::render at read time, so validate here at send.
        preg_match_all('/@foreach\(\s*\$([A-Za-z_][A-Za-z0-9_]*)\s+as|@forelse\(\s*\$([A-Za-z_][A-Za-z0-9_]*)\s+as/', $dataMaster->apprvSet->amssd_content, $loopMatches);
        foreach (array_filter(array_merge($loopMatches[1], $loopMatches[2])) as $loopVar) {
            if (!in_array($loopVar, $listVariable, true) && !str_contains($loopVar, 'fullname')) {
                $listVariable[] = $loopVar;
            }
        }

        if (count($listVariable) > 0) {
            if ($request->has('data')) {
                $checkJSON = is_string($request->data) ? json_decode($request->data, true) : $request->data;
                $checkFil = array_values(array_filter($listVariable, function ($f) use ($request, $checkJSON) {
                    return !in_array($f, array_keys($checkJSON));
                }));

                if (count($checkFil) > 0) {
                    DB::rollBack();
                    return $this->handleError("you hasn't provide some data keys on request!!", $checkFil);
                }

                // Type validation: check data types against defined variable schema
                if ($dataMaster->apprvSet->amssd_content_variables) {
                    $varSchema = $dataMaster->apprvSet->amssd_content_variables;
                    $typeErrors = $this->validateDataTypes($checkJSON, $varSchema);
                    if (!empty($typeErrors)) {
                        DB::rollBack();
                        return $this->handleError("Data type validation failed", $typeErrors);
                    }
                }

                if ($request->has('msgkey') && !empty($request->msgkey)) {
                    $keyRequest = $checkJSON[$request->msgkey];
                    $cekHist = ApprovalHistDetail::where('amsm_id', $request->amsm_id)->where('amshd_paramstore', 'like', "%" . $keyRequest . "%")->first();

                    if (!empty($cekHist) && $useToken !== $cekHist->amstd_token) {
                        DB::rollBack();
                        return $this->handleError("Key " . $keyRequest . " already submited !!", [
                            'hist' => $cekHist,
                            'token_used' => $useToken
                        ]);
                    }
                }
            } else {
                DB::rollBack();
                return $this->handleError("you hasn't provide data keys on request!!", $listVariable);
            }
        }

        // Start Calculating approval
        $hist = [];
        $getfirstOrder = $checkLatestOrder;
        $lastSentOrder = null; // order of the last step we sent, to stop the cascade
        // logger($dataMaster->det);
        foreach ($dataMaster->det as $keyDet => $valueDet) {
            // Only send one order at a time: once we've pushed a higher order's
            // notification out, do NOT pre-notify subsequent orders — they wait
            // until the current order is approved (handled by the approve path).
            if ($lastSentOrder !== null && (int) $valueDet['amsmd_order'] !== $lastSentOrder) {
                break;
            }

            $checkLatestToken = ApprovalHistDetail::where('amsm_id', $request->amsm_id)
                ->with('mapdet')
                ->with('senderUser')
                ->with('receiveUser')
                ->with('attch')
                ->where('amstd_token', $useToken);

            $checkLatest = (clone $checkLatestToken)->whereHas('mapdet')->orderBy('id', 'desc')->first();
            $checkFirst = (clone $checkLatestToken)->orderBy('id', 'asc')->first();

            // logger($checkFirst);
            // logger($checkLatest);
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

            if ($nextStat === 'reject') {
                ApprovalHistDetail::where('amstd_token', $useToken)->delete();
                break;
            }

            // If First or now order more than last order
            if ((int) $valueDet['amsmd_order'] > $checkLatestOrder || empty($checkFirst)) {
                // If Next Order
                if ($valueDet['amsmd_order'] == (int) $checkLatestOrder + 1) {
                    logger('masuk 1');
                    $this->sendingApproval($request, $dataMaster, $checkFirst, $checkLatest, $valueDet, $histToken, $useToken, $nextStat);
                    // Advance cursor so the next approver (order +1) can send too
                    $checkLatestOrder = (int) $valueDet['amsmd_order'];
                    $lastSentOrder = (int) $valueDet['amsmd_order'];
                } else {
                    logger('masuk 2');
                    break;
                }
            } else {
                // logger($dataMaster->det[$checkLatestOrder]);
                // logger([$checkLatestOrder, $valueDet['amsmd_order'], $valueDet['amsmd_username'], $request->username]);

                // if (isset($dataMaster->det[$checkLatestOrder]) && $valueDet['amsmd_order'] == (int) $checkLatestOrder) {
                //     $this->sendingApproval($request, $dataMaster, $checkFirst, $checkLatest, $valueDet, $histToken, $useToken, $nextStat);
                // }

                // If last order
                // logger(array_key_exists($checkLatestOrder + 1, (clone $dataMaster)->ToArray()['det']));
                // logger($dataMaster->det[$checkLatestOrder]);
                // logger($valueDet['amsmd_username'] == $request->username);

                $checkNextOrder = array_filter((clone $dataMaster)->ToArray()['det'], function ($f) use ($valueDet) {
                    return $f['amsmd_order'] == (int) $valueDet['amsmd_order'] + 1;
                });

                if (count($checkNextOrder) === 0 && $valueDet['amsmd_username'] == $request->username) {
                    $this->sendingApproval($request, $dataMaster, $checkFirst, $checkLatest, $valueDet, $histToken, $useToken, $nextStat, true);
                    // Delete used token
                    ApprovalTokenDetail::where('id', $useTokenCreate->id)->delete();
                    break;
                }

                // if (!array_key_exists($checkLatestOrder + 1, (clone $dataMaster)->ToArray()['det']) || (!isset($dataMaster->det[$checkLatestOrder]) && $valueDet['amsmd_username'] == $request->username)) {
                //     $this->sendingApproval($request, $dataMaster, $checkFirst, $checkLatest, $valueDet, $histToken, $useToken, $nextStat, true);
                //     // Delete used token
                //     ApprovalTokenDetail::where('id', $useTokenCreate->id)->delete();
                //     break;
                // }
            }

            $hist = ApprovalHistDetail::where('amshd_token', $histToken)
                ->with('mapdet')
                ->with('senderUser')
                ->with('receiveUser')
                ->with('attch')
                ->orderBy('id', 'desc')
                ->first();
        }

        DB::commit();
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

        $cekTimes = $request->has('page') ? $request->page : 1;

        if ((clone $hist)->count() > 0) {
            $datanya = (clone $hist)->with('senderUser', 'receiveUser')
                ->orderBy('created_at', 'desc')
                ->take(10 * $cekTimes)
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
            'hist' => function ($q) {
                $q->with('mapdet')
                    ->orderBy('id', 'asc');
            },
            'selectedHist' => function ($q) use ($tokenHist) {
                $q->with('mapdet', 'attch')
                    ->where('amshd_token', $tokenHist)
                    ->orderBy('id', 'asc');
            },
        ])->where('amstd_token', $token)
            ->withTrashed()
            ->first();

        // return $cekToken;

        // If token is not deleted and if latest token order same with current token order or if not view mode
        if (
            (
                count($cekToken->hist) > 0 &&
                !empty($cekToken) &&
                !empty($cekToken->selectedHist) &&
                !empty($cekToken->hist[count($cekToken->hist) - 1]['mapdet']) &&
                !empty($cekToken->selectedHist[count($cekToken->selectedHist) - 1]['mapdet']) &&
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
            }));

            if (!isset($getSender[0])) {
                return $this->handleError('Token not found !! please check again !!');
            }

            $getSender = $getSender[0];

            $getReceiver = array_values(array_filter((clone $cekToken)->toArray()['selected_hist'], function ($f) {
                return $f['amshd_stat'] === 'receive';
            }));

            if (!isset($getReceiver[0])) {
                return $this->handleError('Token not found !! please check again !!');
            }

            $getReceiver = $getReceiver[0];

            $hasil = ApprovalMaster::where('id', $cekToken['amsm_id'])->with('det')->with('apprvSet', function ($f) {
                $f->get();
            })->first();

            // Document signing: attach signature boxes for the current approver step
            if (!empty($hasil->apprvSet->amssd_is_docsign)) {
                $currentStep = $getReceiver['mapdet']['amsmd_order'] ?? $getSender['mapdet']['amsmd_order'] ?? null;
                $signBoxes = ApprovalDocSignBox::with('mapdet.userDet')
                    ->where('amsm_id', $hasil->id);
                if ($currentStep !== null) {
                    $signBoxes->whereHas('mapdet', function ($q) use ($currentStep) {
                        $q->where('amsmd_order', $currentStep);
                    });
                }
                $hasilnyaSignBox = $signBoxes->get()->map(function ($box) {
                    return [
                        'amsmd_id' => $box->amsmd_id,
                        'amsmd_order' => $box->mapdet->amsmd_order ?? null,
                        'username' => $box->mapdet->amsmd_username ?? null,
                        'fullname' => $box->mapdet->userDet->fullname ?? $box->mapdet->amsmd_username ?? null,
                        'page_no' => $box->dsbx_page_no,
                        'x' => $box->dsbx_x,
                        'y' => $box->dsbx_y,
                        'width' => $box->dsbx_width,
                        'height' => $box->dsbx_height,
                        'label' => $box->dsbx_label,
                        'signed' => !empty($box->mapdet->hist->where('amshd_stat', 'approve')->first()->amshd_username_apprv),
                    ];
                });
            }

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

            return $this->handleResponse(array_merge($hasilnya, ['token' => $cekToken], isset($hasilnyaSignBox) ? ['sign_boxes' => $hasilnyaSignBox] : []), 'Token found !!');
        }

        return $this->handleError('Token not found !! please check again !!');
    }

    public function sendingApproval($request, $dataMaster, $checkFirst, $checkLatest, $valueDet, $histToken, $useToken, $nextStat, $isLast = false)
    {
        $getSender = PortalUserDet::where('u_username', $request->username)->first();
        $getDataSent = is_string($request->data) ? json_decode($request->data, true) : $request->data;
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
                'data' => $getDataSent,
                'onApproval' => $request->has('onApproval') ? $request->onApproval : [],
                'onDone' => $request->has('onDone') ? $request->onDone : [],
                'msgkey' => $request->has('msgkey') ? $request->msgkey : ''
            ])
        ]);

        $toEmail = $valueDet['amsmd_username'];

        // Receive Notif
        $hist = ApprovalHistDetail::create([
            'p_u_username' => $isLast // IF Approval Complete send back to requestor
                ? $checkFirst->p_u_username
                : $valueDet['amsmd_username'],
            'amsm_id' => $request->amsm_id,
            'amsmd_id' => $valueDet['id'],
            'amshd_token' => $histToken,
            'amstd_token' => $useToken,
            'amshd_username_apprv' => '',
            'amshd_stat' => 'receive',
            'amshd_remarks' => $request->remarks,
            'amshd_paramstore' => json_encode([
                'data' => $getDataSent,
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
                        'username' => $request->username,
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

                logger('punya attachment');
                logger(json_encode($filenya));

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

        // If document signing is enabled, apply signature and return sign boxes
        if ($dataMaster->apprvSet->amssd_is_docsign) {
            $signBoxes = ApprovalDocSignBox::where('amsm_id', $dataMaster->id)
                ->with('mapdet')
                ->get();
            
            if ($signBoxes->isNotEmpty()) {
                $hist->sign_boxes = $signBoxes;
            }
        }

        // If Email notification is on
        if ($dataMaster->apprvSet->amssd_isemail) {
            $cekKeyValue = array_values((array) $request->data)[0];
            if ($request->has('msgkey') && !empty($request->msgkey)) {
                $checkJSON = is_string($request->data) ? json_decode($request->data, true) : $request->data;
                $cekKeyValue = $checkJSON[$request->msgkey];
            }

            $queueSet = new EmailNotificationQueue(
                $request->username,
                $toEmail,
                // 'deny-rachmat@sumitronics.co.jp',
                $request->subject ? ($request->subject . ' - ' . $cekKeyValue) : $dataMaster->ams_title,
                $valueDet->amsmd_reqaprv,
                $dataMaster->ams_content,
                $useToken . '/' . $histToken
            );

            dispatch($queueSet)->onQueue('sendEmailQueue');
        }

        // If using on Approval method trigger
        if ($request->has('onDone') && count($request->onDone) > 0 && $isLast) {
            $this->apiPointData(
                $request->onDone['url'],
                $request->onDone['methods'],
                $request->onDone['params'] ?? [],
                $request->onDone['headers'] ?? [],
                [
                    'approval' => [
                        'status' => $nextStat,
                        'username' => $request->username,
                        'remarks' => $request->remarks,
                    ]
                ]
            );
        }

        try {
            Redis::publish('portalv2', json_encode([
                'app' => 'portal_notif',
                'message' => "You have new notification from {$getSender->pud_first_name} {$getSender->pud_last_name}",
                'type' => 'info',
                'data' => [
                    'username_dest' => empty($checkFirst) ? $valueDet['amsmd_username'] : $checkFirst->p_u_username
                ]
            ]));
        } catch (\Throwable $e) {
            // Redis/real-time push is best-effort; a down broker must not
            // abort/rollback the whole approval flow (token + hist + email).
            logger()->warning('Redis publish failed during approval sending: ' . $e->getMessage());
        }
    }

    public function convertValuetoContent($content, $fromUname, $toUname, $param = [], $token = '')
    {
        $getUsersFrom = PortalUserDet::where('u_username', $fromUname)->first();
        $getUsers = PortalUserDet::where('u_username', $toUname)->first();

        $params = [];
        foreach ($param as $keyVar => $valueVar) {
            $params[$keyVar] = $valueVar;
            // $convertContent = str_replace(search: "{{" . $keyVar . "}}", replace: $valueVar, subject: $convertContent);
        }

        // Provide empty defaults for any variable/loop referenced in the template
        // but not supplied, so Blade::render never blows up on missing data
        // (e.g. a generic approval template that references $item_det).
        $renderParams = array_merge([
            'recipient_fullname' => !empty($getUsers) ? "{$getUsers->pud_first_name} {$getUsers->pud_last_name}" : $toUname,
            'fullname' => !empty($getUsersFrom) ? "{$getUsersFrom->pud_first_name} {$getUsersFrom->pud_last_name}" : $fromUname
        ], $params);

        preg_match_all('/\$(?!\d)[A-Za-z_][A-Za-z0-9_]*/', $content, $matches);
        foreach (array_unique($matches[0]) as $varRef) {
            $varName = substr($varRef, 1); // strip leading $
            if (!array_key_exists($varName, $renderParams)) {
                // Loops default to empty array, scalars to empty string
                $renderParams[$varName] = str_contains($content, '@foreach(' . $varRef . ' ') || str_contains($content, '@forelse(' . $varRef . ' ') ? [] : '';
            }
        }

        $convertContent = Blade::render($content, $renderParams);

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
            DB::raw('MAX(amshd_paramstore) as amshd_paramstore'),
            'amsm_id',
            DB::raw('MAX(created_at) as created_at')
        )
            ->with('master')
            ->where('amshd_stat', 'sent')
            ->whereHas('mapdet');

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
            'amsm_id'
        );

        $result = $data->get()->toArray();

        // return $result;

        $hasil = [];
        foreach ($result as $key => $value) {
            $cekLast = ApprovalHistDetail::with('mapdet')
                // ->where('amshd_stat', 'receive')
                ->where('amstd_token', $value['amstd_token'])
                ->where('amshd_stat', 'sent')
                ->whereHas('mapdet')
                ->orderBy('created_at', 'desc')
                ->first();

            $cekDet = ApprovalMapDetail::select('amsmd_order')
                ->where('amsm_id', (int) $value['amsm_id'])
                ->groupBy('amsmd_order')
                ->get();

            $hasilDet = 0;
            foreach ($cekDet as $key => $valueDet) {
                if (!empty($valueDet) && !empty($cekLast)) {
                    if ((int) $valueDet->amsmd_order <= (int) $cekLast->mapdet->amsmd_order) {
                        $hasilDet += 1;
                    }
                }
            }

            if (!empty($cekLast)) {
                $getDataSent = is_string($cekLast->amshd_paramstore) ? json_decode($cekLast->amshd_paramstore, true) : $cekLast->amshd_paramstore;

                $dataKeyValue = null;
                if (isset($getDataSent['msgkey'])) {
                    $dataKeyValue = $getDataSent['data'][$getDataSent['msgkey']] ?? null;
                } else {
                    $values = array_values($getDataSent);
                    $dataKeyValue = ($values[0]['data'][0] ?? null) ?? ($values[0] ?? null);
                }

                $hasil[] = array_merge($value, [
                    'data' => $cekLast,
                    'percent' => $hasilDet / count($cekDet) * 100,
                    'dataKey' => $dataKeyValue
                ]);
            }
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

    public function viewApprovalMasterByApprvCode($apvcd)
    {
        $getData = ApprovalMaster::where('ams_idapv', $apvcd)
            ->first();

        return $this->handleResponse($getData, 'Data Fetched');
    }

    public function applySignatureToAttachment($attachmentPath, $signatureBase64, $signatureX, $signatureY, $pageNumber, $signerUsername)
    {
        try {
            if (!file_exists($attachmentPath)) {
                return null;
            }

            if (!str_ends_with(strtolower($attachmentPath), '.pdf')) {
                return null;
            }

            return $this->stampSignatureOnPdf($attachmentPath, $signatureBase64, $signatureX, $signatureY, $pageNumber);
        } catch (\Exception $e) {
            logger('Signature stamping failed: ' . $e->getMessage());
            return null;
        }
    }

    public function stampSignatureOnPdf($pdfPath, $signatureBase64, $x, $y, $pageNum = 1)
    {
        $signatureImage = $this->base64ToImage($signatureBase64);
        if (!$signatureImage) {
            return null;
        }

        return [
            'signed_pdf_path' => $pdfPath,
            'signature_image_path' => $signatureImage,
            'x' => $x,
            'y' => $y,
            'page' => $pageNum,
            'status' => 'pending_fpdi_overlay'
        ];
    }

    public function base64ToImage($base64String)
    {
        try {
            if (str_starts_with($base64String, 'data:image')) {
                $data = explode(',', $base64String);
                $data = base64_decode($data[1]);
            } else {
                $data = base64_decode($base64String);
            }

            $filename = 'sig_' . Str::random(12) . '.png';
            Storage::disk('local')->put($filename, $data);
            return Storage::disk('local')->path($filename);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate submitted data against a declared variable type schema.
     * Returns a list of human-readable errors (empty array if all pass).
     *
     * @param array $data          The submitted data payload
     * @param array $varSchema     [{name, type, label?, group?}] declared variables
     * @return array
     */
    public function validateDataTypes(array $data, array $varSchema)
    {
        $errors = [];

        foreach ($varSchema as $var) {
            if (!isset($var['name'])) continue;

            $name = $var['name'];
            $type = strtolower($var['type'] ?? 'string');
            $label = $var['label'] ?? $name;

            // Skip if the key isn't present (missing-key check happens elsewhere)
            if (!array_key_exists($name, $data)) continue;

            $value = $data[$name];

            switch ($type) {
                case 'string':
                case 'number':
                case 'integer':
                case 'float':
                case 'decimal':
                case 'boolean':
                case 'bool':
                case 'date':
                    if (!$this->passesTypeCheck($value, $type)) {
                        $errors[] = "{$label} must be {$this->typeDisplayName($type)}.";
                    }
                    break;
                case 'array':
                case 'list':
                    if (!is_array($value)) {
                        $errors[] = "{$label} must be an array.";
                        break;
                    }
                    // If this variable declares child fields, treat it as a loop of objects
                    // and validate each row's fields against their declared types.
                    if (!empty($var['fields']) && is_array($var['fields'])) {
                        foreach ($value as $i => $row) {
                            if (!is_array($row)) {
                                $errors[] = "{$label}[{$i}] must be an object.";
                                continue;
                            }
                            foreach ($var['fields'] as $field) {
                                if (!isset($field['name'])) continue;
                                $fName = $field['name'];
                                $fType = strtolower($field['type'] ?? 'string');
                                $fLabel = $field['label'] ?? $fName;
                                if (!array_key_exists($fName, $row)) {
                                    $errors[] = "{$label}[{$i}].{$fName} is required.";
                                    continue;
                                }
                                if (!$this->passesTypeCheck($row[$fName], $fType)) {
                                    $errors[] = "{$label}[{$i}].{$fLabel} must be {$this->typeDisplayName($fType)}.";
                                }
                            }
                        }
                    }
                    break;
                // 'string' is the default / catch-all; unknown types are ignored
            }
        }

        return $errors;
    }

    private function isValidDate($value)
    {
        if (is_numeric($value)) return false;
        if (!is_string($value)) return false;
        // Accept YYYY-MM-DD or a parseable date string
        $parsed = strtotime($value);
        return $parsed !== false;
    }

    /**
     * Check a single value against a data type.
     */
    private function passesTypeCheck($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'number':
            case 'float':
            case 'decimal':
                return is_numeric($value);
            case 'integer':
                return is_int($value);
            case 'boolean':
            case 'bool':
                return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true);
            case 'date':
                return $this->isValidDate($value);
            case 'array':
            case 'list':
                return is_array($value);
            default:
                return true; // unknown types are not validated
        }
    }

    private function typeDisplayName(string $type): string
    {
        $names = [
            'string' => 'a string',
            'number' => 'an integer',
            'integer' => 'an integer',
            'float' => 'a number',
            'decimal' => 'a number',
            'boolean' => 'a boolean',
            'bool' => 'a boolean',
            'date' => 'a valid date (YYYY-MM-DD)',
            'array' => 'an array',
            'list' => 'an array',
        ];
        return $names[$type] ?? 'valid';
    }
}

