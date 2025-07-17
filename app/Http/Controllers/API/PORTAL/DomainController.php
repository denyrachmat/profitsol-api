<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PORTAL\PortalDomain;
use DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Storage;
use App\Jobs\PORTAL\StatamicGenerateQueue;
use App\Traits\PORTAL\GencodeTraits;
use App\Http\Controllers\API\PORTAL\BaseController;

class DomainController extends BaseController
{
    use GencodeTraits;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = PortalDomain::get()->toArray();
        $hasil = [];
        foreach ($data as $key => $value) {
            $checkCMS = $this->isGencodeExists('CMS_INSTALLED', [
                'pgm_value' => $value['id'],
            ]);

            $checkCMSData = $this->getDataGencode('CMS_INSTALLED', [
                'pgm_value' => $value['id'],
            ], [
                'idDomain' => 'pgm_value|string',
                'stateCMS' => 'pgm_value2|string',
                'urlCMS' => 'pgm_desc3|string',
            ]);

            $hasil[] = array_merge($value, [
                'checkCoreDB' => [
                    [
                        'DB' => 'PORTAL',
                        'status' => $this->checkIfDatabaseExists($value['pd_prefix_db'] . '_PORTAL')
                    ],
                    [
                        'DB' => 'AMS',
                        'status' => $this->checkIfDatabaseExists($value['pd_prefix_db'] . '_AMS')
                    ],
                    [
                        'DB' => 'CMS',
                        'status' => $this->checkIfDatabaseExists($value['pd_prefix_db'] . '_CMS')
                    ],
                    [
                        'DB' => 'DMS',
                        'status' => $this->checkIfDatabaseExists($value['pd_prefix_db'] . '_DMS')
                    ],
                    [
                        'DB' => 'MRS',
                        'status' => $this->checkIfDatabaseExists($value['pd_prefix_db'] . '_MRS')
                    ],
                ],
                'pd_is_cms' => (string)$checkCMS,
                'CMSState' => $checkCMS ? $checkCMSData['stateCMS'] : '',
                'urlCMS' => $checkCMS ? $checkCMSData['urlCMS'] : '',
            ]);
        }

        return $this->handleResponse($hasil, 'Data Found');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $insert = PortalDomain::create(array_merge(['p_u_username' => $request->header('username')], $request->all()));


        return $this->handleResponse($insert, 'Data Stored');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $insert = PortalDomain::updateOrCreate([
            'id' => $id
        ], [
            'p_u_username' => $request->p_u_username,
            'pd_name' => $request->pd_name,
            'pd_desc' => $request->pd_desc,
            'pd_prefix_db' => $request->pd_prefix_db,
            'pd_img' => $request->pd_img,
            'pd_base_color' => $request->pd_base_color,
        ]);

        return $this->handleResponse($insert, 'Data Stored');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function changeDomain($id)
    {
        $domain = PortalDomain::where('id', $id)->first();

        $listDataBases = [
            [
                'db' => 'PORTAL',
                'alias_name' => $domain->pd_dbtype
            ],
            [
                'db' => 'AMS',
                'alias_name' => 'sqlsrv_ams'
            ],
            [
                'db' => 'CMS',
                'alias_name' => 'sqlsrv_cms'
            ],
            [
                'db' => 'DMS',
                'alias_name' => 'sqlsrv_dms'
            ],
            [
                'db' => 'MRS',
                'alias_name' => 'sqlsrv_mrs'
            ]
        ];

        $configDB = [];
        foreach ($listDataBases as $key => $value) {
            $dbName = "{$domain->pd_prefix_db}_{$value['db']}";
            if (!$this->checkIfDatabaseExists($dbName)) {
                DB::statement("CREATE DATABASE {$domain->pd_prefix_db}_{$value['db']}");
            }

            $configDB[] = config([
                "database.connections." . $value['alias_name'] => [
                    'driver' => $domain->pd_dbtype,
                    'host' => $domain->pd_host,
                    'database' => $domain->pd_host,
                    'username' => $domain->pd_username,
                    'password' => $domain->pd_password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                ]
            ]);
        }

        $listMigrationFile = [
            '2014_10_12_000000_create_users_table.php',
            '2014_10_12_100000_create_password_resets_table.php',
            '2019_08_19_000000_create_failed_jobs_table.php',
            '2019_12_14_000001_create_personal_access_tokens_table.php',
            '2022_03_04_035050_create_portal_users_det_table.php',
            '2022_03_04_035829_create_portal_users_fam_det_table.php',
            '2022_03_04_040022_create_portal_users_study_det_table.php',
            '2022_03_04_064250_create_portal_role_mstr_table.php',
            '2022_03_04_064333_create_portal_app_mstr_table.php',
            '2022_03_04_064720_create_portal_role_app_map_table.php',
            '2022_03_09_012656_create_portal_div_mstr_table.php',
            '2022_03_09_012855_create_portal_post_mstr_table.php',
            '2022_03_09_013923_create_portal_div_post_map_table.php',
            '2022_03_09_013945_create_portal_post_users_map_table.php',
            '2022_07_27_085624_create_portal_role_users_map_table.php',
            '2022_08_02_105431_create_dms_doc_mstr_table.php',
            '2022_08_02_105557_create_dms_folder_mstr_table.php',
            '2022_08_02_110317_create_dms_doc_apprv_map.php',
            '2022_08_02_110354_create_dms_ver_mstr.php',
            '2022_08_02_110413_create_dms_tags_mstr.php',
            '2022_08_02_110436_create_dms_doc_tags_map.php',
            '2022_08_02_111709_create_dms_doc_folder_user_share_map.php',
            '2022_08_10_083426_create_dms_users_doc_root_mstr_table.php',
            '2022_10_05_131826_create_jobs_table.php',
            '2022_10_14_163855_create_cms_form_mstr_table.php',
            '2022_10_14_190122_create_cms_form_ans_det_table.php',
            '2022_12_28_101853_create_cms_form_multi_det_table.php',
            '2022_12_28_105224_create_cms_form_ans_user_det_table.php',
            '2022_12_29_113457_create_cms_form_mstr_title_table.php',
            '2022_12_30_154140_create_cms_form_setup_det_table.php',
            '2023_01_12_085947_create_cms_form_share_det_table.php',
            '2023_01_12_153718_create_portal_notif_mstr.php',
            '2023_01_14_095215_create_tyo_po_mstr_table.php',
            '2023_07_06_140028_create_mrs_db_mstr_table.php',
            '2023_07_14_090325_create_mrs_report_mstr_table.php',
            '2023_07_18_162838_create_mrs_report_cols_det_table.php',
            '2023_08_14_114458_add_cols_macro_to_menu_portal_table.php',
            '2023_12_22_105959_create_cms_form_event.php',
            '2024_01_05_131555_add_real_quiz_date_to_cfsd.php',
            '2024_06_11_085446_create_cms_form_logics_det.php',
            '2024_06_11_111359_add_cfld_actions_to_cms_form_logics_det.php',
            '2024_09_12_112121_create_ams_apprv_mstr.php',
            '2024_09_12_112140_create_ams_apprv_map_det.php',
            '2024_09_12_113212_create_ams_apprv_hist_det.php',
            '2024_09_12_114447_create_ams_apprv_set_det.php',
            '2024_09_17_181330_create_ams_apprv_token_det.php',
            '2024_10_10_113637_create_portal_domain_table.php',
            '2024_10_21_101436_create_dms_doc_root_mstr.php',
            '2024_10_25_102822_add_dfm_root_name_to_dms_folder_mstr.php',
            '2024_10_28_130436_add_dfm_root_name_to_dms_doc_mstr.php',
            '2024_10_29_180705_add_amssd_attachment_to_ams_apprv_set_det.php',
            '2024_10_29_180907_create_ams_apprv_attch_det_table.php',
            '2024_10_30_141947_create_ams_apprv_attch_set_table.php',
            '2024_10_31_104407_add_ddfus_token_to_dms_doc_folder_user_share_map.php',
            '2024_11_01_134401_add_aats_base64_format_to_ams_apprv_attch_set.php',
            '2024_11_04_183233_add_amaad_dl_link_to_ams_apprv_attch_hist_det.php',
        ];

        $migrateTable = [];
        foreach ($listMigrationFile as $keyMigrate => $valueMigrate) {
            $migrateTable[] = $this->runSpecificMigration($valueMigrate);
        }
    }

    public function checkIfTableExists($tableName)
    {
        if (Schema::hasTable($tableName)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfDatabaseExists($databaseName)
    {
        try {
            DB::connection()->getPdo()->exec("USE {$databaseName}");
            return true;
        } catch (\Exception $e) {
            return true;
        }
    }

    public function runSpecificMigration($migrationFileName)
    {
        $migrationPath = database_path('migrations/' . $migrationFileName);

        if (file_exists($migrationPath)) {
            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/' . $migrationFileName,
                ]);

                return response()->json(['message' => 'Migration run successfully']);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Migration file not found'], 404);
        }
    }

    public function activateCMS(Request $request, $id)
    {
        $domain = PortalDomain::where('id', $id)->first();

        if (!$domain) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        $projectName = $domain->pd_name;

        if (Schema::hasTable('cms_form_mstr')) {
            return response()->json(['message' => 'CMS already activated for this domain'], 200);
        }

        try {
            $path = $this->createStatamicProject($id, $projectName, $request->header('username'));
            return response()->json(['message' => 'CMS activated successfully', 'path' => $path], 200);
        } catch (ProcessFailedException $e) {
            return response()->json(['error' => 'Failed to activate CMS: ' . $e->getMessage()], 500);
        }
    }

    public function createStatamicProject($id, $projectName, $username)
    {
        StatamicGenerateQueue::dispatch($id, $projectName, $username)->onQueue('portal_cms_install');
        return response()->json(['message' => 'Statamic project is being installed in background.']);
    }
}
