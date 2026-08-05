<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\FormAnswerDet;
use App\Models\CMS\FormMultiDet;
use Illuminate\Http\Request;
use App\Traits\CMS\FormsTraits;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormMasterTitle;
use App\Models\CMS\FormSetupDet;
use App\Models\CMS\FormMaster;
use App\Models\CMS\FormShareDet;
use App\Traits\TOS\TrainingTraits;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

use App\Models\User;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TOS\ImportQuizTemplate;
use App\Services\DocumentParserService;
use Illuminate\Support\Facades\Http;

class QuizController extends Controller
{

    protected $docParser;

    use FormsTraits;

    public function __construct(DocumentParserService $docParser)
    {
        // Increase script execution time for heavy queries
        set_time_limit(1800); // 30 minutes, adjust as needed
        ini_set('max_execution_time', 1800);
        $this->docParser = $docParser;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

        // return $request;
        FormAnswerUserDet::where('cfm_id', $request->id)
            ->where('p_u_username', $request->header('username'))
            ->delete();
        $created = [];
        foreach ($request->ans as $key => $value) {
            $getID = FormAnswerUserDet::where('cfm_id', $request->id)
                ->where('p_u_username', $request->header('username'))
                ->orderBy('created_at', 'desc')
                ->first();
            if (empty($getID)) {
                $getDeletedID = FormAnswerUserDet::withTrashed()->where('cfm_id', $request->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $nextID = empty($getDeletedID) ? 1 : $getDeletedID['cfaud_batch'] + 1;
            } else {
                $nextID = $getID['cfaud_batch'];
            }

            if (is_array($value)) {
                sort($value);
            }

            $created[] = FormAnswerUserDet::create([
                'p_u_username' => $request->header('username'),
                'cfaud_batch' => $nextID,
                'cfm_id' => $request->id,
                'cfmd_id' => $request->questId[$key],
                'cfm_val' => is_array($value) ? json_encode($value) : $value,
            ]);
        }

        return response([
            'status' => true,
            'message' => 'Answers successfully submited !',
            'data' => $created
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id, $idDet = '')
    {
        $dataAnswersHead = FormAnswerDet::select('cms_form_ans_det.*', 'cfm_seq_name')
            ->where('cms_form_ans_det.cfm_id', $id)
            ->leftjoin('cms_form_mstr', function ($join) {
                $join->on('cms_form_ans_det.cfmd_id', '=', 'cms_form_mstr.id');
            })
            ->orderBy(DB::raw('CAST(cfm_seq_name AS int)'), 'asc');
        $dataHeader = FormMasterTitle::where('id', $id)->with([
            'formMaster' => function ($f2) {
                $f2->where('cfm_parent_id', 0);
                // $f2->where('cfm_type', '=', 'form');
                $f2->with('formDetail.formAnswer');
                $f2->with('allChildrenContent');
                // $f2->orderBy(DB::raw('CAST(cfm_seq_name AS int)'), 'asc');
            }
        ])->first();

        // return $dataAnswersHead->get()->toArray();

        $cekSetup = FormSetupDet::where('cfmt_id', $id)->first();
        if ($cekSetup && $cekSetup->cfsd_quest_limit !== null && $cekSetup->cfsd_quest_limit > 0) {
            $dataAnswers = (clone $dataAnswersHead)
                ->join('cms_form_ans_user_det', function ($f) {
                    $f->on('cms_form_ans_det.cfm_id', 'cms_form_ans_user_det.cfm_id');
                    $f->on('cms_form_ans_det.cfmd_id', 'cms_form_ans_user_det.cfmd_id');
                })
                ->where('cms_form_ans_user_det.deleted_at', null)
                ->where('cms_form_ans_user_det.p_u_username', $request->header('username'))
                ->get();
        } else {
            $dataAnswers = (clone $dataAnswersHead)->get();
        }

        // return $dataHeader->toArray();
        $dataOri = $this->getHeaderAllForms([$dataHeader->toArray()])[0]['forms'];

        // return $dataOri;

        // return $dataAnswers;
        $hasil = [];
        $hasilOri = [];
        $startKey = 0;
        foreach ($dataAnswers as $key => $value) {
            $answers = is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : $value['cfm_val'];
            if (is_array($answers)) {
                sort($answers);
            }

            $data = FormAnswerUserDet::where('p_u_username', $request->header('username'))
                ->where('cfm_id', (int) $id)
                ->where('cfmd_id', $value['cfmd_id'])
                ->first();

            if (!empty($data)) {
                $cekOri = array_values(array_filter($dataOri, function ($f) use ($value) {
                    return $f['id'] == $value['cfmd_id'];
                }));

                if (count($cekOri) > 0) {
                    $hasilOri[] = $cekOri[0];
                }

                // return $hasilOri;

                $answersUser = !empty($data)
                    ? (is_array(json_decode($data->cfm_val)) ? json_decode($data->cfm_val) : $data->cfm_val)
                    : (is_array(json_decode($value['cfm_val'])) ? [] : "");

                $getLabelCek = FormMultiDet::select('cfmd_label')
                    ->where('cfm_id', $data->cfmd_id)
                    ->whereIn('cfmd_value', is_array(json_decode($value['cfm_val'])) ? json_decode($value['cfm_val']) : [$value['cfm_val']]);

                if (!empty($idDet)) {
                    $getLabelCek->where('cfmd_id', $idDet);
                }

                $getLabel = $getLabelCek->pluck('cfmd_label');

                $hasil[$startKey] = [
                    'status' => $answers === $answersUser,
                    'users' => $answersUser,
                    'ans' => $answers,
                    'ans_value' => $getLabel,
                    'exp' => $value['cfm_exp']
                ];

                $startKey++;
            }
        }

        $getGrade = array_filter($hasil, function ($f) {
            return $f['status'];
        });

        if ($cekSetup && $cekSetup->cfsd_quest_limit !== null && intval($cekSetup->cfsd_quest_limit) > 0) {
            $totalGrade = round((count($getGrade) / intval($cekSetup->cfsd_quest_limit)) * 100, 2);
        } else {
            $totalGrade = round((count($getGrade) / count($dataAnswers)) * 100, 2);
        }

        $cekStatGrade = FormSetupDet::where('cfmt_id', $id)->first();

        return response([
            'status' => true,
            'data' => $hasil,
            'grade' => $totalGrade,
            'is_pass' => $totalGrade >= ($cekStatGrade->cfsd_min_pass ?? 0),
            'data_ori' => $hasilOri,
            'data_ans' => $dataAnswers,
            'setup' => $cekSetup
        ]);
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

    public function migrationHRMS()
    {
        $data = DB::select("
            SELECT
                '' as id,
                'deny-rachmat@sumitronics.co.jp' as p_u_username,
                cfmt.id as cfmt_id,
                'form' as cfm_type,
                hfcm.div_content,
                (
                    select count(*) from HRMS.dbo.hrms_form_mstr_det hfmd
                    where hfmd.form_mstr_id = hfcm.div_content
                ) as totForms
            FROM STX_CMS.dbo.cms_form_mstr_title cfmt
            INNER JOIN HRMS.dbo.hrms_form_content_mapping hfcm ON hfcm.form_name = cfmt.cfmt_title
            where cfmt.cfmt_quiz_flag = 1
            --and hfcm.div_id = 'FRM-2209120002'
            order by hfcm.id
        ");

        $hasil = [];
        foreach ($data as $key => $value) {
            $getAnswersTest = DB::table('HRMS.dbo.hrms_form_key_answers as hfka')
                ->where('hfka.form_id', $value->div_content)
                ->get();

            $hasilConvert = [
                'component' => [
                    'label' => count($getAnswersTest) > 1 ? 'Multiple Checkbox (Multiple choice)' : 'Multiple Choice',
                    'category' => 'multiple',
                    'value' => [
                        'type' => count($getAnswersTest) > 1 ? 'multiple-checkbox' : 'multiple-radio',
                        'comp' => count($getAnswersTest) > 1 ? 'q-checkbox' : 'q-radio'
                    ]
                ],
                'detail_data' => [],
                'label' => ''
            ];
            if ($value->totForms > 0) {
                $dataDet = DB::table('HRMS.dbo.hrms_form_mstr_det as hfmd')->where('hfmd.form_mstr_id', $value->div_content)->get();
                $getLabel = DB::table('HRMS.dbo.hrms_form_mstr as hfm')->where('hfm.form_id', $value->div_content)->first();
                foreach ($dataDet as $keyDet => $valueDet) {
                    $hasilConvert['detail_data'][] = [
                        'col_det_id' => $valueDet->form_var_id,
                        'col_det_label' => '',
                        'value' => $keyDet + 1,
                        'label' => $valueDet->form_option_label
                    ];
                }

                $hasilConvert['label'] = $getLabel->form_label;
            }

            $hasil[] = [
                'ori_id' => $value->div_content,
                'id' => $value->id,
                'p_u_username' => $value->p_u_username,
                'cfmt_id' => $value->cfmt_id,
                'cfm_type' => $value->totForms > 0 ? 'form' : 'html',
                'cfm_seq_name' => null,
                'cfm_content' => $value->totForms > 0 ? $hasilConvert : $value->div_content,
                'cfm_parent_id' => 0,
                'cfm_required' => 0
            ];
        }

        foreach ($hasil as $keyHasil => $valueHasil) {
            if ($valueHasil['cfm_type'] === 'form') {
                $master = FormMaster::create([
                    'p_u_username' => $valueHasil['p_u_username'],
                    'cfmt_id' => $valueHasil['cfmt_id'],
                    'cfm_type' => $valueHasil['cfm_type'],
                    'cfm_seq_name' => $valueHasil['cfm_seq_name'],
                    'cfm_content' => is_array($valueHasil['cfm_content']) ? json_encode($valueHasil['cfm_content']) : $valueHasil['cfm_content'],
                    'cfm_parent_id' => $valueHasil['cfm_parent_id'],
                    'cfm_required' => $valueHasil['cfm_required'],
                ]);

                $getLabelList = [];
                foreach ($valueHasil['cfm_content']['detail_data'] as $key => $valueContent) {
                    $insertFormDet = FormMultiDet::create([
                        'cfm_id' => $master->id,
                        'cfmd_value' => $valueContent['value'],
                        'cfmd_label' => $valueContent['label'],
                    ]);

                    $getLabelList[]['value'] = $valueContent['value'];
                    $getLabelList[]['label'] = $valueContent['label'];
                }

                $getAnswers = DB::table('HRMS.dbo.hrms_form_key_answers as hfka')
                    ->where('hfka.form_id', $valueHasil['ori_id'])
                    // ->where('hfka.ans_val', 'like', '%'.$valueContent['label'].'%')
                    ->get();

                if (count($getAnswers) > 0) {
                    $getAnswersFilter = array_values(array_filter($getAnswers->toArray(), function ($f) use ($valueHasil) {
                        $listLabel = [];
                        foreach ($valueHasil['cfm_content']['detail_data'] as $key => $valueLabelDet) {
                            $listLabel[] = $valueLabelDet['label'];
                        }

                        return in_array($f->ans_val, $listLabel);
                    }));

                    if (count($getAnswersFilter) === 1) {
                        FormAnswerDet::create([
                            'p_u_username' => 'deny-rachmat@sumitronics.co.jp',
                            'cfm_id' => $valueHasil['cfmt_id'],
                            'cfmd_id' => $master->id,
                            'cfm_val' => FormMultiDet::where(DB::raw('CAST(cfmd_label AS VARCHAR(MAX))'), $getAnswersFilter[0]->ans_val)->first()->cfmd_value,
                            'cfm_exp' => !empty($getAnswersFilter[0]->ans_remark) ? $getAnswersFilter[0]->ans_remark : ''
                        ]);
                    } else {

                        $hasilValAns = [];
                        foreach ($getAnswersFilter as $keyAns => $valueAns) {
                            $hasilValAns[] = $valueAns->ans_val;
                        }

                        FormAnswerDet::create([
                            'p_u_username' => 'deny-rachmat@sumitronics.co.jp',
                            'cfm_id' => $valueHasil['cfmt_id'],
                            'cfmd_id' => $master->id,
                            'cfm_val' => json_encode(FormMultiDet::whereIn(DB::raw('CAST(cfmd_label AS VARCHAR(MAX))'), $hasilValAns)->get()->pluck('cfmd_value')),
                            'cfm_exp' => !empty($getAnswersFilter[0]->ans_remark) ? $getAnswersFilter[0]->ans_remark : ''
                        ]);
                    }
                }
            }
        }

        return $hasil;
    }

    public function migrateUsersAnswers()
    {
        ini_set('memory_limit', '2G');
        $getUsers = DB::table('HRMS.dbo.hrms_user_mstr as hum')
            ->select('hum.email', 'hum.first_name', 'hum.last_name', 'hum.username')
            ->join(DB::raw('HRMS.dbo.hrms_form_hist hfh'), 'hum.username', 'hfh.form_hist_username')
            ->join(DB::raw('HRMS.dbo.hrms_form_content_mapping hfcm'), 'hfcm.div_id', 'hfh.form_id')
            //->where('hum.email', 'muhammad-zubir@sumitronics.co.jp')
            ->groupBy('hum.email', 'hum.first_name', 'hum.last_name', 'hum.username')
            ->get();

        // return $getUsers;
        // Migrate users

        $hasil = [];
        foreach ($getUsers as $key => $value) {
            $userCheck = User::where('email', $value->email)->first();
            if (empty($userCheck)) {
                $user = User::create([
                    'username' => $value->email,
                    'email' => $value->email,
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'password' => bcrypt('123456'),
                ]);

                $user->det()->create([
                    'u_username' => $value->email,
                    'pud_first_name' => $value->first_name,
                    'pud_last_name' => $value->last_name,
                ]);
            } else {
                $user = $userCheck;
            }

            $dataJawabanDraft = DB::table('HRMS.dbo.hrms_user_mstr as hum')
                ->join(DB::raw('HRMS.dbo.hrms_form_hist hfh'), 'hum.username', 'hfh.form_hist_username')
                ->join(DB::raw('HRMS.dbo.hrms_form_content_mapping hfcm'), function ($j) {
                    $j->on('hfh.form_id', 'hfcm.div_id');
                    $j->on('hfh.form_hist_id', 'hfcm.div_content');
                })
                ->join(DB::raw('STX_CMS.dbo.cms_form_mstr_title cfmt'), 'cfmt.cfmt_title', 'hfcm.form_name')
                ->where('hfh.form_hist_username', $value->username);

            $dataJawaban = (clone $dataJawabanDraft)
                ->select(
                    'hum.*',
                    'hfh.*',
                    'hfcm.form_name',
                    'cfmt.id as cfmt_id'
                )
                // ->where('hfh.form_id', 'FRM-2209120002')
                ->orderBy('hfh.created_at')
                ->get();

            $userAns = [];
            foreach ($dataJawaban as $key => $valueJawaban) {
                $cekIDJawaban = FormMultiDet::whereIn(DB::raw('CAST(cfmd_label AS VARCHAR(MAX))'), json_decode($valueJawaban->form_hist_value))
                    ->orderBy('cfm_id', 'asc');

                $getIDJawaban = (clone $cekIDJawaban)->get()->pluck('cfmd_value');
                $getIDJawabanAll = (clone $cekIDJawaban)->get();

                // FormAnswerUserDet::where('p_u_username', $valueJawaban->email)
                //     ->where('cfm_id', $getIDJawabanFirst->cfm_id)
                //     ->where('cfmd_id', $getIDJawabanFirst->cfmd_id)
                //     ->forceDelete();

                $groupJawaban = [];
                foreach ($getIDJawaban as $key => $value) {
                    $groupJawaban[$value] = $value;
                }

                if (count($getIDJawabanAll) > 0) {
                    $cekJawabanExists = FormAnswerUserDet::where('cfm_id', $valueJawaban->cfmt_id)
                        // ->whereIn('cfmd_id', (clone $getIDJawabanAll)->pluck('cfm_id'))
                        ->where('p_u_username', $valueJawaban->email)
                        // ->where('cfm_val', count(array_values($groupJawaban)) > 1 ? json_encode(array_values($groupJawaban)) : array_values($groupJawaban)[0])
                        ->get()
                        ->pluck('cfmd_id')
                        ->toArray();

                    $viewDataJawaban = (clone $getIDJawabanAll)->pluck('cfm_id')->toArray();
                    $filterDataAnsExists = array_values(array_filter($viewDataJawaban, function ($f) use ($cekJawabanExists) {
                        return !in_array($f, $cekJawabanExists);
                    }));

                    if (count($filterDataAnsExists) > 0) {
                        if (count($filterDataAnsExists) > 1) {
                            // logger([$valueJawaban->cfmt_id, $viewDataJawaban, $cekJawabanExists, $filterDataAnsExists]);
                        }

                        $cekBatch = FormAnswerUserDet::select('cfaud_batch')->where('p_u_username', $valueJawaban->email)
                            ->where('cfm_id', $valueJawaban->cfmt_id)
                            ->where('cfmd_id', $filterDataAnsExists[0])
                            ->withTrashed()
                            ->first();

                        $cekLatestBatch = FormAnswerUserDet::select('cfaud_batch')->where('p_u_username', $valueJawaban->email)
                            ->where('cfm_id', $valueJawaban->cfmt_id)
                            ->first();

                        $userAja = FormAnswerUserDet::create([
                            'p_u_username' => $valueJawaban->email,
                            'cfm_id' => $valueJawaban->cfmt_id,
                            'cfmd_id' => $filterDataAnsExists[0],
                            'cfm_val' => count(array_values($groupJawaban)) > 1 ? json_encode(array_values($groupJawaban)) : array_values($groupJawaban)[0],
                            'cfaud_batch' => empty($cekBatch) ? (empty($cekLatestBatch) ? 1 : $cekLatestBatch->cfaud_batch) : $cekBatch->cfaud_batch + 1
                        ]);

                        if (!empty($valueJawaban->deleted_at)) {
                            FormAnswerUserDet::where('p_u_username', $valueJawaban->email)
                                ->where('cfm_id', $valueJawaban->cfmt_id)
                                ->where('cfmd_id', $filterDataAnsExists[0])
                                ->where('cfm_val', count(array_values($groupJawaban)) > 1 ? json_encode(array_values($groupJawaban)) : array_values($groupJawaban)[0])
                                ->delete();
                        } else {
                            $userAns[] = $userAja;
                        }
                    }
                }
            }

            $dataJawabanForRegUserShare = (clone $dataJawabanDraft)
                ->select(
                    'cfmt.id as cfmt_id'
                )
                ->groupBy('cfmt.id')
                ->get();

            foreach ($dataJawabanForRegUserShare as $key => $valueReg) {
                $cekData = FormShareDet::where('cfmt_id', $valueReg->cfmt_id)
                    ->where('cfsd_to', $user->username)
                    ->first();

                if (empty($cekData)) {
                    FormShareDet::create([
                        'cfmt_id' => $valueReg->cfmt_id,
                        'p_u_username' => 'deny-rachmat@sumitronics.co.jp',
                        'cfsd_to' => $user->username,
                        'cfsd_gen_link' => \Illuminate\Support\Str::random(40)
                    ]);
                }
            }

            foreach ($userAns as $keyAnsRevision => $valueAnsRevision) {
                $cekJawaban = FormAnswerDet::where('cfm_id', $valueAnsRevision->cfm_id)
                    ->where('cfmd_id', $valueAnsRevision->cfmd_id)
                    // ->where('p_u_username', $valueAnsRevision->p_u_username)
                    ->first();

                // logger($cekJawaban);

                if (!empty($cekJawaban)) {
                    FormAnswerUserDet::where('cfm_id', $valueAnsRevision->cfm_id)
                        ->where('cfmd_id', $valueAnsRevision->cfmd_id)
                        ->where('p_u_username', $valueAnsRevision->p_u_username)
                        ->update([
                            'cfm_val' => $cekJawaban->cfm_val
                        ]);
                }
            }

            $hasil[] = [
                'data' => $dataJawaban,
                'insertAns' => $userAns
            ];
        }

        return $hasil;
    }

    public function getHTMLList($id)
    {
        return FormMaster::where('cfmt_id', $id)->where('cfm_type', 'html')->get()->toArray();
    }

    public function downloadHTMLMaterial($id)
    {
        // $pdf = PDF::loadFile("http://192.168.100.32:8081/portal_v2/#/showHTMLTraining?data={$id}");
        // $pdf = PDF::loadFile("http://localhost:8080/#/showHTMLTraining?data={$id}");
        $pdf = PDF::loadView("CMS.HTMLMaterial", ['data' => $this->getHTMLList($id)]);

        return base64_encode($pdf->inline('download.pdf'));
    }

    public function uploadQuizTemplate(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $import = new ImportQuizTemplate();
            Excel::import($import, $request->file('file'));

            // Mengembalikan struktur JSON nested yang siap di-render dinamis oleh Quasar
            return response()->json($import->parsedData, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses skema form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadQuizTemplate()
    {
        $filePath = storage_path('app/Quiz template.xlsx');

        if (!file_exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'File not found'
            ], 404);
        }

        $filename = 'Quiz template.xlsx';
        return response()->download($filePath, $filename);
    }

    public function parseDocumentForAI(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,docx|max:5120' // Max 5MB
        ]);

        try {
            $file = $request->file('file');
            $text = $this->docParser->extractText($file->getPathname(), $file->getClientOriginalExtension());

            // Panggil fungsi untuk menembak 9router
            $aiResponse = $this->askAIToParse($text);

            return response()->json($aiResponse, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses AI Parser: ' . $e->getMessage()
            ], 500);
        }
    }

    private function askAIToParse(string $documentText)
    {
        $apiKey = config('ninerouter.api_key');
        $apiUrl = config('ninerouter.url');

        if (empty($apiKey) || empty($apiUrl)) {
            throw new \Exception("Konfigurasi NINEROUTER_API_KEY atau NINEROUTER_URL belum diset.");
        }

        // STRATEGI DIET PAYLOAD: Minta format minimalis ke AI untuk menghemat token hingga 70%
        // $prompt = "Berikut adalah teks mentah dari dokumen kuis yang harus kamu analisis:\n"
        //     . "=========================================\n"
        //     . $documentText . "\n"
        //     . "=========================================\n\n"
        //     . "Tugasmu: Transformasikan dan isi dokumen di atas menjadi objek JSON minimalis dengan skema kaku berikut:\n\n"
        //     . "{\n"
        //     . "  \"title\": \"[Judul Kuis]\",\n"
        //     . "  \"quizzes\": [\n"
        //     . "    {\n"
        //     . "      \"q\": \"[Teks Pertanyaan]\",\n"
        //     . "      \"options\": {\n"
        //     . "        \"A\": \"[Isi Opsi A]\",\n"
        //     . "        \"B\": \"[Isi Opsi B]\",\n"
        //     . "        \"C\": \"[Isi Opsi C]\",\n"
        //     . "        \"D\": \"[Isi Opsi D]\"\n"
        //     . "      },\n"
        //     . "      \"exp\": \"[Penjelasan singkat jawaban, atau kosongkan jika tidak ada]\",\n"
        //     . "      \"ans\": \"[Huruf Kunci Jawaban tunggal (A/B/C/D) atau array jika jawaban banyak contoh [\\\"A\\\",\\\"B\\\"]]\"\n"
        //     . "    }\n"
        //     . "  ]\n"
        //     . "}\n\n"
        //     . "PERINGATAN: Sediakan output murni JSON mentah yang valid tanpa teks pembuka, penutup, atau markdown ```json!";

        // STRATEGI DIET PAYLOAD: Minta format minimalis ke AI untuk menghemat token hingga 70%
        // STRATEGI DIET PAYLOAD: Minta format minimalis ke AI untuk menghemat token hingga 70%
        $prompt = "Berikut adalah teks mentah dari dokumen kuis yang harus kamu analisis:\n"
            . "=========================================\n"
            . $documentText . "\n"
            . "=========================================\n\n"
            . "Tugasmu: Transformasikan dan isi dokumen di atas menjadi objek JSON minimalis dengan skema kaku berikut:\n\n"
            . "{\n"
            . "  \"title\": \"[Judul Kuis]\",\n"
            . "  \"quizzes\": [\n"
            . "    {\n"
            . "      \"q\": \"[Teks Pertanyaan. Jika bilingual, gabungkan ID & EN dengan spasi atau garis miring, atau escape enter sebagai \\\\n]\",\n"
            . "      \"options\": {\n"
            . "        \"[Key_Huruf]\": \"[Teks Opsi]\"\n"
            . "      },\n"
            . "      \"exp\": \"[Penjelasan singkat jawaban, atau kosongkan jika tidak ada]\",\n"
            . "      \"ans\": \"[Huruf Kunci Jawaban tunggal (misal: \\\"C\\\") atau array jika jawaban banyak contoh [\\\"A\\\",\\\"C\\\"]]\"\n"
            . "    }\n"
            . "  ]\n"
            . "}\n\n"
            . "ATURAN KETAT FORMAT & TEKNIS:\n"
            . "1. JUMLAH OPSI DINAMIS: Buat kunci objek ('A', 'B', 'C', 'D', 'E', dst.) sesuai jumlah pilihan di dokumen asli.\n"
            . "2. ATURAN BILINGUAL & STRINGS:\n"
            . "   - Jika teks memiliki 2 bahasa (Indonesia & Inggris), gabungkan keduanya dalam satu baris menggunakan pemisah ' / ' atau ' - ' untuk menghindari error control character, DILARANG menggunakan enter mentah (line break fisik) di dalam string JSON.\n"
            . "   - Semua teks harus berada dalam satu baris atau menggunakan escape karakter string JSON yang valid (\\\\n jika benar-benar butuh baris baru).\n"
            . "3. SANITASI KARAKTER: Bersihkan semua control character, tab tersembunyi, atau karakter aneh dari dokumen asli agar JSON murni valid.\n\n"
            . "PERINGATAN: Sediakan output murni JSON mentah yang valid tanpa teks pembuka, penutup, atau markdown ```json!";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->connectTimeout(15)
            ->post($apiUrl, [
                "model" => "auto-coding-helper-free",
                "messages" => [
                    [
                        "role" => "user",
                        "content" => $prompt
                    ]
                ],
                "response_format" => [
                    "type" => "json_object"
                ],
                "max_tokens" => 4000,
                "temperature" => 0.1
            ]);

        if ($response->failed()) {
            throw new \Exception("9router API Error (HTTP " . $response->status() . "): " . $response->body());
        }

        $rawBody = $response->body();
        $rawBody = trim($rawBody);

        if (str_contains($rawBody, 'data: [DONE]')) {
            $rawBody = str_replace('data: [DONE]', '', $rawBody);
            $rawBody = trim($rawBody);
        }

        $result = json_decode($rawBody, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (is_null($result) or !is_array($result)) {
            throw new \Exception("Response dari 9router bukan format JSON yang valid.");
        }

        $jsonString = null;
        if (isset($result['choices'][0]['message']['content'])) {
            $jsonString = $result['choices'][0]['message']['content'];
        } elseif (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $jsonString = $result['candidates'][0]['content']['parts'][0]['text'];
        }

        if (empty($jsonString)) {
            throw new \Exception("AI memproses, tapi kata kunci content tidak ditemukan.");
        }

        $jsonString = trim($jsonString);
        if (str_starts_with($jsonString, '```')) {
            $jsonString = preg_replace('/^```json\s*/i', '', $jsonString);
            $jsonString = preg_replace('/```$/', '', $jsonString);
            $jsonString = trim($jsonString);
        }

        $jsonString = preg_replace('/^[\x{FEFF}\x{200B}-\x{200D}]/u', '', $jsonString) ?? $jsonString;

        // Bersihkan control character mentah. PENTING: regex byte-safe (tanpa modifier
        // /u) agar tidak pernah return null ketika string mengandung UTF-8 tidak valid
        // (sering muncul dari campuran teks bilingual/emoji yang disalin dokumen).
        // json_decode gagal dengan JSON_ERROR_CTRL_CHAR kalau ada karakter ini.
        // CR/LF/tab dinormalisasi jadi spasi dulu (teks jadi tidak dempet), lalu yang
        // tersisa (C0 \x00-\x1F, DEL \x7F, C1 via 0xC2 0x80-0x9F) dihapus total.
        $jsonString = str_replace(["\r\n", "\r", "\n", "\t"], " ", $jsonString);
        $jsonString = preg_replace('/[\x00-\x1F\x7F]/', '', $jsonString);
        $jsonString = preg_replace('/[\xC2][\x80-\x9F]/', '', $jsonString);
        $jsonString = preg_replace('/\xc2\xa0/u', ' ', $jsonString) ?? $jsonString;

        // Decode JSON Ringkas dari AI. Jaring pengaman: kalau masih ada control char
        // yang lolos, bersihkan agresif (hapus SEMUA byte non-printable) lalu coba lagi.
        $aiData = $this->decodeAiJson($jsonString);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $badBytes = preg_replace('/[^\\x20-\\x7E]/', '?', $jsonString);
            \Log::error("Gagal Decode JSON Utama Kuis dari AI. Detail Error: " . json_last_error_msg());
            \Log::error("Payload yang bermasalah: " . $badBytes);
            throw new \Exception("Format JSON kuis rusak (" . json_last_error_msg() . ").");
        }

        // ==========================================
        // PERAKITAN STRUKTUR AKHIR (LAKUKAN DI LARAVEL)
        // ==========================================
        // Di sini Laravel yang menyusun skema kaku Quasar, menghemat beban token AI secara ekstrem!
        $finalData = [
            "id" => null,
            "title" => $aiData['title'] ?? 'AI Generated Quiz',
            "desc" => "",
            "isQuiz" => "1",
            "forms" => [],
            "exp" => [],
            "ans" => []
        ];

        $formIdCounter = 2602;
        $quizzes = $aiData['quizzes'] ?? [];

        foreach ($quizzes as $index => $quiz) {
            $seq = $index + 1;

            // 1. Rakit Form Kuis Kaku
            $detailData = [];
            if (isset($quiz['options']) && is_array($quiz['options'])) {
                foreach ($quiz['options'] as $flag => $desc) {
                    $detailData[] = [
                        "col_det_id" => "opt-" . rand(8000, 8999),
                        "col_det_label" => "",
                        "value" => trim($flag),
                        "label" => trim($flag) . ".\t" . trim($desc)
                    ];
                }
            }

            $finalData['forms'][] = [
                "id" => null,
                "type" => "form",
                "required" => false,
                // "seq_name" => $seq,
                "seq_name" => 1,
                "content" => [
                    "label" => $seq . ".\t" . ($quiz['q'] ?? ''),
                    "component" => [
                        "label" => "Multiple Choice",
                        "category" => "multiple",
                        "value" => [
                            "type" => "multiple-radio",
                            "comp" => "q-radio"
                        ]
                    ],
                    "detail_data" => $detailData
                ],
                "logics" => []
            ];

            // 2. Petakan Explanation
            $finalData['exp'][] = !empty($quiz['exp']) ? trim($quiz['exp']) : "-";

            // 3. Petakan Jawaban (ans)
            $finalData['ans'][] = $quiz['ans'] ?? "";
        }

        return $finalData;
    }

    /**
     * Decode JSON hasil AI dengan jaring pengaman: jika ada control character yang
     * belum tertangkap sehingga decode gagal dengan JSON_ERROR_CTRL_CHAR, bersihkan
     * semua byte non-printable (\x00-\x1F, \x7F-\x9F) lalu retry. Retry juga terapkan
     * pembersihan BOM & nbsp untuk kasus input yang lolos.
     */
    private function decodeAiJson(string $jsonString)
    {
        $decoded = json_decode($jsonString, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (json_last_error() !== JSON_ERROR_CTRL_CHAR) {
            return $decoded;
        }

        $aggressive = $jsonString;
        $aggressive = str_replace(["\r\n", "\r", "\n", "\t"], " ", $aggressive);
        $aggressive = preg_replace('/[\x00-\x1F\x7F-\x9F]/', '', $aggressive);
        \Log::warning("JSON AI mengandung control character, dilakukan aggressive cleanup.");

        return json_decode($aggressive, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
