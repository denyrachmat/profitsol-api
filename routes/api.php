<?php
use App\Http\Controllers\STXI\LOG\CeisaMonitoringController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\CMS\FormController;
use App\Http\Controllers\API\CMS\QuizController;
use App\Http\Controllers\API\DMS\DocumentController;
use App\Http\Controllers\API\DMS\FolderController;
use App\Http\Controllers\API\MACROLIST\macroListController;
use App\Http\Controllers\API\MRS\DBConnectionController;
use App\Http\Controllers\API\MRS\ReportColsController;
use App\Http\Controllers\API\MRS\ReportController;
use App\Http\Controllers\API\PORTAL\NotifController;
use App\Http\Controllers\API\TOS\QuizViewController;
use App\Http\Controllers\API\TOS\TrainingController;
use App\Http\Controllers\API\TOS\TrainingListController;
use App\Http\Controllers\Scheduller\EMS2\WEBEdiTYOExtractor;
use App\Http\Controllers\STXI\BIM\CircullarTenController;
use App\Http\Controllers\STXI\EMS2\ForcastDOTYOController;
use App\Http\Controllers\STXI\EMS2\yeidPOConfirmController;
use App\Http\Controllers\STXI\EMS2\YMICDCUController;
use App\Http\Controllers\STXI\EMS2\YMIQuotantionController;
use App\Http\Controllers\STXI\EMS2\deliveryMethodToPSIController;
use App\Http\Controllers\STXI\EMS2\poSummaryController;
use App\Http\Controllers\STXI\PU\PAApprovalController;
use App\Http\Controllers\STXI\LOG\INSWDataController;
use App\Http\Controllers\STXI\LOG\Ceisa40UploaderController;
use App\Http\Controllers\API\PORTAL\AuthController;
use App\Http\Controllers\API\PORTAL\ProfileController;
use App\Http\Controllers\API\PORTAL\ProfilesController;
use App\Http\Controllers\API\PORTAL\UsersController;
use App\Http\Controllers\API\PORTAL\AppController;
use App\Http\Controllers\API\PORTAL\RoleController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
//     Route::post('profile', ProfileController::class);
//     // Route::group(['prefix' => 'portal'], function() {
//     //     Route::post('profile', ProfileController::class);
//     // });
// });

Route::group(['prefix' => 'portal', 'middleware' => 'auth:sanctum', 'verify' => true], function () {

    // Settings Menu
    Route::resource('users', UsersController::class);

    Route::resource('profiles', ProfilesController::class);

    Route::resource('apps', AppController::class);
    Route::get('appsParent', [AppController::class, 'indexParentOnly']);

    Route::resource('roles', RoleController::class);
    Route::post('change-password', [AuthController::class, 'change_password']);

    // Dashboard
    Route::post('profile', [ProfileController::class, 'store'])->middleware('verified');
    Route::get('countryList', [ProfileController::class, 'getCountryList']);
    Route::resource('notif', NotifController::class);
});

Route::group(['prefix' => 'dms'], function () {
    Route::resource('documents', DocumentController::class);
    Route::get('documents/getSourceOnly/{id}', [DocumentController::class, 'sourceOnly']);

    Route::resource('folders', FolderController::class);
    Route::get('migrateToDB/{users}/{path?}/{isCheck?}', [FolderController::class, 'migrateRealFileToDB']);
    // Tester
    Route::get('checkFolders/{users}', [FolderController::class, 'checkPerm']);
    Route::get('checkPath/{users}/{path?}', [FolderController::class, 'checkPath']);
    Route::get('checkDeletedFolders/{users}', [FolderController::class, 'dbSyncToRealDoc']);
    Route::get('syncRootFiles/{users}', [FolderController::class, 'syncRootFiles']);
});

Route::group(['prefix' => 'cms'], function () {
    Route::resource('forms', FormController::class);
    Route::resource('quiz', QuizController::class);

    Route::post('storeAnswers', [FormController::class, 'storeAnswers']);
    
    Route::get('migrationHRMS', [QuizController::class, 'migrationHRMS']);
    Route::get('migrationHRMSUserAns', [QuizController::class, 'migrateUsersAnswers']);
    Route::get('viewByLinkForm/{link}', [FormController::class, 'viewByLinkForm']);
});

Route::group(['prefix' => 'tos'], function () {
    Route::resource('quizView', QuizViewController::class);
    Route::resource('training', TrainingController::class);
    Route::resource('trainingList', TrainingListController::class);

    Route::get('trainingListExport/{id}', [TrainingListController::class, 'exportData']);
    Route::get('showHistoryPerUser/{username}/{id}', [TrainingListController::class, 'showHistoryPerUser']);

    Route::get('exportAnalyticsQuestion/{id}', [TrainingListController::class, 'exportAnalyticsQuestion']);

    // showHistoryPerUser
});

Route::group(['prefix' => 'mrs'], function () {
    Route::resource('dbconn', DBConnectionController::class);
    Route::get('listDB/{id}/{type?}/{dbname?}', [DBConnectionController::class, 'listDB']);
    Route::get('getParameterSP/{id}/{dbname?}/{spname?}', [DBConnectionController::class, 'getParameterSP']);
    Route::post('checkConnection', [DBConnectionController::class, 'testConnection']);

    Route::resource('report', ReportController::class);
    Route::post('simRunning', [ReportController::class, 'simRunning']);
    Route::post('runningReport/{id}', [ReportController::class, 'runningReport']);
    Route::post('exportReport/{id}', [ReportController::class, 'exportToExcel']);

    Route::resource('reportCols', ReportColsController::class);
});

Route::group(['prefix' => 'div'], function () {
    Route::group(['prefix' => 'ems2'], function () {
        // Start DLV TYO
        Route::get('itemSearch/{filter}', [deliveryMethodToPSIController::class, 'searchItemMaster']);
        Route::get('spq', [deliveryMethodToPSIController::class, 'SPQIndex']);
        Route::post('spq', [deliveryMethodToPSIController::class, 'SPQCreateUpdate']);
        Route::delete('spq/{id}', [deliveryMethodToPSIController::class, 'SPQDeleteData']);
        Route::get('spqChecker/{qty}/{qtyDel}/{model}', [deliveryMethodToPSIController::class, 'DLVCalSPQRes']);

        Route::get('dlv/{paginate}/{date?}', [deliveryMethodToPSIController::class, 'DLVIndex']);
        Route::get('dlvEmail/{date}', [deliveryMethodToPSIController::class, 'DLVSendEmail']);
        Route::post('dlv', [deliveryMethodToPSIController::class, 'DLVWithBarcode']);
        Route::post('dlvStore', [deliveryMethodToPSIController::class, 'DLVStore']);
        Route::get('dlvExport', [deliveryMethodToPSIController::class, 'DLVExport']);
        Route::get('dlvDelete/{date}/{loc?}/{item_num?}', [deliveryMethodToPSIController::class, 'deleteDelivery']);

        Route::post('uploadSPQ', [deliveryMethodToPSIController::class, 'UploadSPQ']);
        Route::get('syncBOMToPSI', [deliveryMethodToPSIController::class, 'syncBOMToPSI']);
        Route::get('DLVStockDelivery/{date}/{item?}', [deliveryMethodToPSIController::class, 'DLVStockDelivery']);

        Route::get('fifoData/{date?}/{item?}/{saved?}/{byItemOnly?}/{dateFifoStart?}/{do?}/{qty?}', [deliveryMethodToPSIController::class, 'fifoUpdateDLV']);
        Route::get('getFifoData/{date?}/{item?}', [deliveryMethodToPSIController::class, 'showFifoDLV']);

        Route::get('exportDOExcel/{date}/{item?}', [deliveryMethodToPSIController::class, 'exportDOExcel']);
        Route::get('getNextDN/{date}', [deliveryMethodToPSIController::class, 'deliveryLatestNo']);
        Route::post('deliveryToTYO', [deliveryMethodToPSIController::class, 'deliveryToTYO']);

        Route::post('uploadWeeklyPOData', [deliveryMethodToPSIController::class, 'uploadWeeklyPOData']);
        Route::get('getUploadedWeeklyPO/{date}', [deliveryMethodToPSIController::class, 'getUploadedWeeklyPO']);
        Route::get('exportWeeklyReport/{date}', [deliveryMethodToPSIController::class, 'ExportWeeklyReport']);
        Route::post('updateFIFO', [deliveryMethodToPSIController::class, 'replaceFIFODO']);
        Route::get('deleteFIFO/{id}', [deliveryMethodToPSIController::class, 'deleteFIFO']);
        Route::post('uploadPOTYO', [deliveryMethodToPSIController::class, 'uploadPO']);
        Route::post('getDataPOTYO', [deliveryMethodToPSIController::class, 'getDataPOTYO']);
        Route::post('storeDraftPOTYO', [deliveryMethodToPSIController::class, 'storeDraftPOTYO']);
        Route::post('deleteDraftPOTYO', [deliveryMethodToPSIController::class, 'deleteDraftPOTYO']);
        Route::get('getPOTYOMegaReady/{date}', [deliveryMethodToPSIController::class, 'getPOTYOMegaReady']);
        Route::post('UpdatePOTYOCells', [deliveryMethodToPSIController::class, 'UpdatePOTYOCells']);
        Route::post('deleteToDraft', [deliveryMethodToPSIController::class, 'deleteToDraft']);
        Route::get('getAllRecordDateOnly', [deliveryMethodToPSIController::class, 'getAllRecordDateOnly']);
        Route::post('ExportTYODOMega/{date}', [deliveryMethodToPSIController::class, 'ExportTYODOMega']);
        Route::get('ExportDOChecker/{date}', [deliveryMethodToPSIController::class, 'ExportDOChecker']);
        Route::post('uploadFifoDOData', [deliveryMethodToPSIController::class, 'uploadFifoDOData']);
        // End DLV TYO

        // Start PO Summary
        Route::post('uploadRawPO', [poSummaryController::class, 'uploadPO']);
        Route::get('POGetData/{date}', [poSummaryController::class, 'POGetData']);
        Route::get('POGetDataDet/{date}', [poSummaryController::class, 'POGetDataDet']);
        Route::get('POExportData/{date}', [poSummaryController::class, 'exportPO']);
        Route::get('PODetExportData/{date}', [poSummaryController::class, 'exportPODet']);
        // End PO Summary

        // Start DO Forcast TYO
        Route::resource('forecastDLVTYO', ForcastDOTYOController::class);
        Route::post('getReport/{export?}', [ForcastDOTYOController::class, 'getReport']);
        Route::get('getItemList/{item?}', [ForcastDOTYOController::class, 'getItemList']);
        Route::post('exportForcast', [ForcastDOTYOController::class, 'exportForcast']);
        Route::post('uploadForecast', [ForcastDOTYOController::class, 'uploadForecast']);
        // End DO Forcast TYO

        // Start YEID PO Confirmation
        Route::resource('ypoConfirm', yeidPOConfirmController::class);
        Route::get('checkYeidItem/{item}/{col?}', [yeidPOConfirmController::class, 'searchItem']);
        Route::get('searchPO/{item}/{po?}/{col?}', [yeidPOConfirmController::class, 'searchPO']);
        Route::post('getYPOData', [yeidPOConfirmController::class, 'getDataPagination']);
        Route::post('YPOExportExcel', [yeidPOConfirmController::class, 'exportExcel']);
        Route::get('updateData', [yeidPOConfirmController::class, 'cekData']);
        Route::post('uploadPOManual', [yeidPOConfirmController::class, 'uploadManualPO']);

        // End YEID PO Confirmation

        // Start CD/CU Price MRI
        Route::resource('ymiCDCU', YMICDCUController::class);
        Route::post('ymiCDCUPage', [YMICDCUController::class, 'getData']);
        Route::post('uploadPriceList', [YMICDCUController::class, 'uploadPriceList']);
        Route::post('registerPOMRI', [YMICDCUController::class, 'registerPO']);
        Route::post('exportExcelPriceList', [YMICDCUController::class, 'exportExcel']);


        Route::post('ymiQuoList', [YMIQuotantionController::class, 'getData']);
        Route::post('exportPriceList', [YMIQuotantionController::class, 'exportPriceList']);
        
        // End CD/CU Price MRI
    });

    Route::group(['prefix' => 'log'], function () {
        Route::get('INSWGetDataDetail/{filter}', [INSWDataController::class, 'getData']);
        Route::get('INSWGetData/{filter?}/{size?}', [INSWDataController::class, 'getListHSCode']);
        
        Route::post('uploadData', [Ceisa40UploaderController::class, 'uploadData']);
        Route::post('getNopen', [Ceisa40UploaderController::class, 'getNopen']);
        Route::post('getDetPerusahaan', [Ceisa40UploaderController::class, 'getDetPerusahaan']);

        Route::get('downloadExcelCeisa40/{noAju}/{bc}/{id}', [Ceisa40UploaderController::class, 'downloadExcel']);
        Route::get('syncCeisa/{noAju}/{bc}/{id}', [Ceisa40UploaderController::class, 'syncCeisaToWebBased']);

        Route::resource('ceisaMon', CeisaMonitoringController::class);
        Route::get('ceisaMonDet/{noAju}/{noDaftar}', [CeisaMonitoringController::class, 'show']);

        Route::get('testData/{db}/{data}', [Ceisa40UploaderController::class, 'test']);
        Route::get('interfaceBC',[CeisaMonitoringController::class, 'interfaceBCDOCMEGAtoWEB']);
        Route::get('interfaceByDate/{fdate}/{ldate}/{isInterMega?}', [Ceisa40UploaderController::class, 'syncByDate']);
    });

    Route::group(['prefix' => 'pu'], function () {
        Route::resource('PAApproval', PAApprovalController::class);
        Route::get('PAApproval/{id}/{username}/{table}', [PAApprovalController::class, 'show']);
    });

    Route::group(['prefix' => 'bim'], function () {
        Route::resource('cirten', CircullarTenController::class);
        Route::post('uploadCirten', [CircullarTenController::class, 'uploadCirTenFolder']);
        Route::get('generateDocument/{ten}/{isExport?}', [CircullarTenController::class, 'generateDocument']);
        Route::get('listModelFromHTM/{ten}', [CircullarTenController::class, 'listModelFromHTM']);
        Route::get('sendToDMS/{ten}', [CircullarTenController::class, 'sendToDMS']);
        Route::get('findModelCode/{item}', [CircullarTenController::class, 'findItem']);
        Route::get('addModelDetail/{ten}/{item}', [CircullarTenController::class, 'addModelDetail']);

    });
});

Route::group((['prefix' => 'scheduller']), function () {
    Route::get('downloadData', [WEBEdiTYOExtractor::class, 'downloadData']);
});

Route::group(['prefix' => 'macro'], function () {
    Route::resource('list', macroListController::class);
    Route::get('download/{path}', [macroListController::class, 'download']);
    Route::get('listRole', [macroListController::class, 'listFolderStxiWebSystem']);

});

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('countryList', [ProfileController::class, 'getCountryList']);

Route::post('forgot-password', [AuthController::class, 'forgot_password']);

Route::get('redis', function () {
    try{
        $redis=\Redis::connect('192.168.100.32',6379);
        return response('redis working');
    }catch(\Predis\Connection\ConnectionException $e){
        return $e;
        return response('error connection redis');
    }
});

Route::get('testredis', function () {
    // $redis = Redis::connection();
    // $redis->publish('message', json_encode([
    //     'app' => 'log',
    //     'status' => 'positive',
    //     'message' => 'Incoming on progress added',
    //     'data' => []
    // ]));

    Redis::publish('test-channel', 'a test message');

    $prefix = config('database.redis.options.prefix');
    $channel = $prefix . 'test-channel';

    return "Done. (published on $channel)";
});