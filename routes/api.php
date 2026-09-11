<?php
use App\Http\Controllers\API\AMS\ApprovalController;
use App\Http\Controllers\API\AMS\ApprovalDocSignController;
use App\Http\Controllers\API\AMS\ApprovalExternalController;
use App\Http\Controllers\API\AMS\ApprovalRunningController;
use App\Http\Controllers\API\AMS\ApprovalSettingsController;
use App\Http\Controllers\API\DMS\DocumenRootController;
use App\Http\Controllers\API\PORTAL\DomainController;
use App\Http\Controllers\API\PORTAL\GencodeController;
use App\Http\Controllers\API\PORTAL\MobileGencodeController;
use App\Http\Controllers\API\MOBILE\LabelManagerController;
use App\Http\Controllers\STXI\EMS2\labelPrintController;
use App\Http\Controllers\API\PORTAL\FrontPageController;
use App\Http\Controllers\STXI\EMS2\TYOAutoBarcodeController;
use App\Http\Controllers\STXI\EMS2\YPODailyConfController;
use App\Http\Controllers\STXI\IT\PartScannerController;
use App\Http\Controllers\STXI\LOG\CeisaMonitoringController;
use App\Http\Controllers\STXI\LOG\HSCodeReportController;
use App\Http\Controllers\STXI\LOG\HSCodeUploadController;
use App\Http\Controllers\STXI\PC\autoEmailWMSConfirmation;
use App\Http\Controllers\STXI\PC\autoSyncBOMtoPSIController;
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
use App\Http\Controllers\STXI\BIM\CirtenUpdateController;
use App\Http\Controllers\STXI\BIM\MRPWeeklyBasedController;
use App\Http\Controllers\STXI\EMS2\ForcastDOTYOController;
use App\Http\Controllers\STXI\EMS2\yeidPOConfirmController;
use App\Http\Controllers\STXI\EMS2\YMICDCUController;
use App\Http\Controllers\STXI\EMS2\YMIQuotantionController;
use App\Http\Controllers\STXI\EMS2\deliveryMethodToPSIController;
use App\Http\Controllers\STXI\EMS2\poSummaryController;
use App\Http\Controllers\STXI\PU\PAApprovalController;
use App\Http\Controllers\STXI\LOG\INSWDataController;
use App\Http\Controllers\STXI\LOG\Ceisa40UploaderController;
use App\Http\Controllers\STXI\LOG\WISController;
use App\Http\Controllers\API\PORTAL\AuthController;
use App\Http\Controllers\API\PORTAL\ProfileController;
use App\Http\Controllers\API\PORTAL\ProfilesController;
use App\Http\Controllers\API\PORTAL\UsersController;
use App\Http\Controllers\API\PORTAL\AppController;
use App\Http\Controllers\API\PORTAL\RoleController;
use App\Http\Controllers\API\RPA\RPAMasterController;
use App\Http\Controllers\STXI\EMS2\YMIDeliveryScheduleCompController;
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

Route::get('phpinfo', function () {
    return phpinfo();
});

Route::get('/whoami', function () {
    return [
        'auth' => auth()->check(),
        'user' => optional(auth()->user())->only('id', 'email'),
        'secure' => request()->isSecure(),
        'ip' => request()->ip(),
        'host' => request()->getHost(),
    ];
});

Route::group(['prefix' => 'portal' /* , 'middleware' => ['auth:sanctum','verified']*/], function () {
    // Settings Menu
    Route::group(['prefix' => 'users'], function () {
        Route::get('ActiveOnly', [UsersController::class, 'userActiveOnly']);
    });
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

    Route::resource('gencode', GencodeController::class);
    Route::group(['prefix' => 'gencode'], function () {
        Route::post('showDetail/{id}', [GencodeController::class, 'showDetail']);
        Route::post('deleteDetail/{id}', [GencodeController::class, 'deleteDetail']);
        Route::post('deleteDetailGroup/{id}', [GencodeController::class, 'deleteDetailGroup']);
        Route::post('saveGencode', [GencodeController::class, 'saveGencode']);
    });
});


Route::resource('domain', DomainController::class);
Route::group(['prefix' => 'domain'], function () {
    Route::post('startSetupCMS/{id}', [DomainController::class, 'activateCMS']);
});

Route::group(['prefix' => 'fpmanager'], function () {
    Route::get('getFPMenu', [FrontPageController::class, 'getFPMenu']);
    Route::post('saveNavMenu', [FrontPageController::class, 'saveNavMenu']);
    Route::get('getNavMenu', [FrontPageController::class, 'getNavMenuFromAPI']);
    Route::get('getNavMenu/{id}', [FrontPageController::class, 'getNavMenuFromAPI']);
    Route::put('updateMainPage/{id}/{state}', [FrontPageController::class, 'updateMainPage']);
    Route::post('updateOrderNavMenu', [FrontPageController::class, 'updateOrderNav']);

    Route::delete('deleteNavMenu/{id}', [FrontPageController::class, 'deleteNavMenu']);
    Route::get('getNavConf', [FrontPageController::class, 'getNavConf']);
    Route::get('getNavConf/{id}', [FrontPageController::class, 'getNavConf']);

    Route::get('getMainConf', [FrontPageController::class, 'getMainConf']);
    Route::post('saveMainConf', [FrontPageController::class, 'saveMainConf']);

    Route::post('saveTags', [FrontPageController::class, 'saveTags']);
    Route::delete('removeTag/{id}/{tag}', [FrontPageController::class, 'removeTag']);
    Route::post('publishPost/{id}/{state?}', [FrontPageController::class, 'publishPost']);

    Route::get('getNavAssignedDMS', [FrontPageController::class, 'getNavAssignedDMS']);
    Route::post('saveDMStoFrontPage', [FrontPageController::class, 'saveDMStoFrontPage']);

    Route::post('subscribe', [FrontPageController::class, 'subscribePosts']);
    Route::post('subscribeAllow', [FrontPageController::class, 'subscribe']);
    Route::post('unsubscribe', [FrontPageController::class, 'unsubscribe']);
    //Test

    Route::post('saveSubscriber', [FrontPageController::class, 'updateBulkSubscribePosts']);
});

Route::group(['prefix' => 'ams'], function () {
    Route::resource('approval', ApprovalController::class);
    Route::resource('approvalSettings', ApprovalSettingsController::class);

    // For sending approval
    Route::post('approveAction', [ApprovalRunningController::class, 'approveAction']);
    Route::post('approveHist', [ApprovalRunningController::class, 'approveHist']);
    Route::get('getMasterApprovalByToken/{token}/{tokenHist}/{isView?}', [ApprovalRunningController::class, 'getMasterApprovalByToken']);
    Route::get('readAllNotif', [ApprovalRunningController::class, 'readAllNotif']);
    Route::post('viewListSentApproval', [ApprovalRunningController::class, 'viewListSentApproval']);

    // Doc sign boxes
    Route::get('docsign/{amsm_id}', [ApprovalDocSignController::class, 'getSignBoxes']);
    Route::post('docsign/save', [ApprovalDocSignController::class, 'saveSignBoxes']);
    Route::post('docsign/upload', [ApprovalDocSignController::class, 'uploadDocumentForSigning']);
    Route::delete('docsign/{amsm_id}', [ApprovalDocSignController::class, 'deleteSignBoxes']);

    // External API (requires API key)
    Route::post('external/initialize', [ApprovalExternalController::class, 'initialize']);
    Route::get('external/approvals', [ApprovalExternalController::class, 'listApprovals']);
    Route::post('external/key/register', [ApprovalExternalController::class, 'registerKey']);
});

Route::group(['prefix' => 'dms'], function () {
    Route::resource('documents', DocumentController::class);
    Route::get('documents/getSourceOnly/{id}', [DocumentController::class, 'sourceOnly']);

    Route::resource('documentsRoot', DocumenRootController::class);
    Route::group(['prefix' => 'documentsRoots'], function () {
        Route::post('getDataFilter', [DocumenRootController::class, 'getDataFilter']);
        Route::get('getMapping/{root}', [DocumenRootController::class, 'getMapping']);
        Route::post('storeMappingRoot', [DocumenRootController::class, 'storeMappingRoot']);
        Route::get('getRegisteredRoot/{users}', [DocumenRootController::class, 'getRegisteredRoot']);
        Route::get('installDisk/{id}', [DocumenRootController::class, 'installDisk']);
        Route::get('resyncFolderToDB/{users}/{root}', [DocumenRootController::class, 'folderFilesSync']);
        Route::get('resyncFolderToDBStart/{users}/{root}', [DocumenRootController::class, 'folderFilesSyncStart']);
        Route::get('resyncFolderToDBPoll/{token}', [DocumenRootController::class, 'folderFilesSyncPoll']);
        Route::get('resyncFolderToDBInfo/{token}', [DocumenRootController::class, 'folderFilesSyncInfo']);
        Route::post('shareFileFolder', [DocumenRootController::class, 'shareFileFolder']);
        Route::get('getSharedToken/{token}/{sharedId?}/{users?}', [DocumenRootController::class, 'getSharedToken']);
        Route::get('getSharedFilesFolder/{token}/{sharedId?}/{users?}/{idFiles?}', [DocumenRootController::class, 'getSharedFilesFolder']);
        Route::get('getfiles/{root}/{path?}', [DocumenRootController::class, 'getfiles']);
    });

    Route::resource('folders', FolderController::class);
    Route::group(['prefix' => 'folderList'], function () {
        Route::get('list/{username}/{root}/{idParent?}/{isFetchAll?}/{id?}', [FolderController::class, 'showList']);
    });
    Route::get('migrateToDB/{users}/{path?}/{isCheck?}', [FolderController::class, 'migrateRealFileToDB']);
    // Tester
    Route::get('checkFolders/{users}', [FolderController::class, 'checkPerm']);
    Route::get('checkPath/{users}/{path?}', [FolderController::class, 'checkPath']);
    Route::get('browse/{users}/{root}/{path?}', [FolderController::class, 'browse'])->where('path', '.*');
    Route::get('checkDeletedFolders/{users}', [FolderController::class, 'dbSyncToRealDoc']);
    Route::get('syncRootFiles/{users}', [FolderController::class, 'syncRootFiles']);
    Route::post('uploadFiles', [DocumentController::class, 'uploadFilesForAPI']);
});

Route::group(['prefix' => 'cms'], function () {
    Route::resource('forms', FormController::class);
    Route::get('forms/{id}/{tags?}', [FormController::class, 'show']);
    Route::post('formsDetail', [FormController::class, 'showDetail']);

    Route::post('storeAnswers', [FormController::class, 'storeAnswers']);
    Route::post('storeBulkAnswers', [FormController::class, 'storeBulkAnswers']);
    Route::post('cloneForm', [FormController::class, 'cloneForm']);
    Route::get('completionStatus/{id}', [FormController::class, 'getCompletionStatus']);
    Route::delete('deleteAnswers/{id}/{batchID}', [FormController::class, 'destroyAnswers']);
    Route::get('getConnectedMRS/{id}', [FormController::class, 'getConnectedMRS']);
    Route::get('viewByLinkForm/{link}', [FormController::class, 'viewByLinkForm']);
    Route::get('viewByID/{id}', [FormController::class, 'viewByID']);
    Route::get('viewBySlug/{id}', [FormController::class, 'viewBySlug']);
    Route::post('showHistory/{id}', [FormController::class, 'showHistory']);
    Route::post('updateApprovalStatus', [FormController::class, 'updateAMSMapping']);
    Route::post('sendApproval', [FormController::class, 'sendApproval']);
    Route::post('updateStatus', [FormController::class, 'updateStatus']);
    Route::post('saveSetupTraining', [FormController::class, 'saveSetupTraining']);
    Route::post('restore/{id}', [FormController::class, 'restore']);
    Route::get('trashed', [FormController::class, 'trashed']);

    Route::resource('quiz', QuizController::class);

    Route::get('migrationHRMS', [QuizController::class, 'migrationHRMS']);
    Route::get('migrationHRMSUserAns', [QuizController::class, 'migrateUsersAnswers']);
    Route::get('viewHTMLOnlyQuiz/{id}', [QuizController::class, 'getHTMLList']);
    Route::post('downloadHTMLMaterial/{id}', [QuizController::class, 'downloadHTMLMaterial']);
    Route::get('downloadQuizTemplate', [QuizController::class, 'downloadQuizTemplate']);
    Route::post('uploadQuizTemplate', [QuizController::class, 'uploadQuizTemplate']);

    Route::post('uploadQuizTemplateAi', [QuizController::class, 'parseDocumentForAI']);

    Route::get('downloadTemplateBulk/{id}', [FormController::class, 'downloadTemplateBulk']);
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

    Route::get('runningReportFromAPI/{token}', [ReportController::class, 'runningReportFromAPI']);
    Route::get('getListAPIColection/{idReport}', [ReportController::class, 'getListAPIColection']);
    Route::post('storeSearchForAPI', [ReportController::class, 'storeSearchForAPI']);

    Route::resource('reportCols', ReportColsController::class);
});

Route::group(['prefix' => 'rpa'], function () {
    Route::resource('rpaMaster', RPAMasterController::class);
    Route::get('rpaMaster/{id}', [RPAMasterController::class, 'show']);
    Route::post('rpaMaster', [RPAMasterController::class, 'store']);
    Route::put('rpaMaster/{id}', [RPAMasterController::class, 'update']);
    Route::delete('rpaMaster/{id}', [RPAMasterController::class, 'destroy']);

    Route::resource('rpaHist', \App\Http\Controllers\API\RPA\RPAHistController::class);
});

// Custom API For STXI
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
        Route::post('uploadFC', [yeidPOConfirmController::class, 'uploadFC']);

        // End YEID PO Confirmation

        // Start CD/CU Price MRI
        Route::resource('ymiCDCU', YMICDCUController::class);
        Route::post('ymiCDCUPage', [YMICDCUController::class, 'getData']);
        Route::post('uploadPriceList', [YMICDCUController::class, 'uploadPriceList']);
        Route::post('registerPOMRI', [YMICDCUController::class, 'registerPO']);
        Route::post('exportExcelPriceList', [YMICDCUController::class, 'exportExcel']);


        Route::post('ymiQuoList', [YMIQuotantionController::class, 'getData']);
        Route::post('exportPriceList', [YMIQuotantionController::class, 'exportPriceList']);

        Route::resource('ypoDailyConf', YPODailyConfController::class);
        // End CD/CU Price MRI

        // Start Auto create barcode TYO
        Route::resource('tyoAutoBarcode', TYOAutoBarcodeController::class);
        Route::group(['prefix' => 'tyoAutoBarcodes'], function () {
            Route::post('downloadExcel/{id}', [TYOAutoBarcodeController::class, 'downloadExcel']);
            Route::post('downloadBarcodeRange/{fdate}/{ldate}/{type}', [TYOAutoBarcodeController::class, 'downloadBarcodebyDate']);
        });

        Route::resource('labelPrint', labelPrintController::class);
        Route::group(['prefix' => 'labelPrints'], function () {
            Route::post('search', [labelPrintController::class, 'searchItems']);
            Route::post('searchGIT', [labelPrintController::class, 'searchGIT']);
            Route::post('splitSPQData', [labelPrintController::class, 'splitData']);
            Route::post('searchAllItemFromGRN', [labelPrintController::class, 'searchAllItemFromGRN']);
            Route::post('searchAllInvByItem/{item}', [labelPrintController::class, 'searchAllInvByItem']);

        });

        Route::post('exportYMIDeliverySchedule', [YMIDeliveryScheduleCompController::class, 'export']);
    });

    Route::group(['prefix' => 'log'], function () {

        Route::get('INSWGetDataMaster/{filter?}/{siza?}', [INSWDataController::class, 'getListMaster']);
        Route::get('INSWGetDataDetail/{filter}', [INSWDataController::class, 'getData']);
        Route::get('INSWGetData/{filter?}/{size?}', [INSWDataController::class, 'getListHSCode']);
        Route::get('runINSWSyncData/{filter?}', [INSWDataController::class, 'syncINSWData']);
        Route::get('runINSWSyncDataHeader/{filter?}', [INSWDataController::class, 'syncINSWDirectHeader']);
        Route::get('resyncUnsyncedRegulationDet', [INSWDataController::class, 'resyncUnsyncedRegulationDet']);

        Route::post('uploadData', [Ceisa40UploaderController::class, 'uploadData']);
        Route::post('getNopen', [Ceisa40UploaderController::class, 'getNopen']);
        Route::post('getDetPerusahaan', [Ceisa40UploaderController::class, 'getDetPerusahaan']);
        Route::post('syncCeisatoITInventory', [Ceisa40UploaderController::class, 'syncCeisatoITInventory']);
        Route::post('getHeaderCeisa', [Ceisa40UploaderController::class, 'getHeader']);

        Route::get('downloadExcelCeisa40/{noAju}/{bc}/{id}', [Ceisa40UploaderController::class, 'downloadExcel']);
        Route::get('syncCeisa/{noAju}/{bc}/{id}', [Ceisa40UploaderController::class, 'syncCeisaToWebBased']);

        Route::resource('ceisaMon', CeisaMonitoringController::class);
        Route::post('searchApi', [CeisaMonitoringController::class, 'searchApi']);
        Route::get('ceisaMonDet/{noAju}/{noDaftar}/{idHeader?}', [CeisaMonitoringController::class, 'show']);

        Route::get('testData/{db}/{data}', [Ceisa40UploaderController::class, 'test']);
        Route::get('interfaceBC', [CeisaMonitoringController::class, 'interfaceBCDOCMEGAtoWEB']);
        Route::get('interfaceByDate/{fdate}/{ldate}/{isInterMega?}/{isInterCeisa?}', [Ceisa40UploaderController::class, 'syncByDate']);
        Route::get('syncBCNo/{bcno}/{bcdate}', [Ceisa40UploaderController::class, 'syncBCNo']);

        Route::get('syncStatusCeisaByIDHeader/{id}', [Ceisa40UploaderController::class, 'syncStatusCeisaByIDHeader']);
        Route::get('syncStatusCeisaAll', [Ceisa40UploaderController::class, 'syncStatusCeisaAll']);

        // Upload Data HS Code
        Route::resource('HSCode', HSCodeUploadController::class);
        Route::post('uploadAttachment', [HSCodeUploadController::class, 'uploadAttachment']);
        Route::post('HSCodeFilter', [HSCodeUploadController::class, 'HSCodeFilter']);
        Route::get('testRecurs', [HSCodeUploadController::class, 'testHeaderData']);
        Route::post('exportData/{hist?}/{lastDonwload?}', [HSCodeUploadController::class, 'exportData']);
        Route::post('exportDataPDF', [HSCodeUploadController::class, 'exportDataPDF']);
        Route::post('HSCodeSendApproval', [HSCodeUploadController::class, 'sendApproval']);
        Route::post('updateApprovalHSCode', [HSCodeUploadController::class, 'updateApprovalHSCode']);

        // Route::resource('HSCode', HSCodeReportController::class);
        Route::post('HSCodeINSWFilter', [HSCodeReportController::class, 'HSCodeFilter']);
        Route::get('HSCodeBeaDetail', [HSCodeReportController::class, 'HSCodeBeaDetail']);
        Route::get('HSCodeRegulationDet/{hsCode}', [HSCodeReportController::class, 'HSCodeRegulationDet']);

        Route::get('autoExportData/{withHist}', [HSCodeUploadController::class, 'autoExportData']);

        Route::post('filterQRIncData', [WISController::class, 'filterQRIncData']);
        Route::post('autocompleteQRIncData', [WISController::class, 'autocompleteQRIncData']); //new
    });

    Route::group(['prefix' => 'pu'], function () {
        Route::resource('PAApproval', PAApprovalController::class);
        Route::get('PAApproval/{id}/{username}/{table}', [PAApprovalController::class, 'show']);
        Route::get('approvePartInfo/{id}/{username}', [PAApprovalController::class, 'approvePartInfo']);
    });

    Route::group(['prefix' => 'bim'], function () {
        Route::resource('cirten', CircullarTenController::class);
        Route::post('uploadCirten', [CircullarTenController::class, 'uploadCirTenFolder']);
        Route::get('generateDocument/{ten}/{isExport?}', [CircullarTenController::class, 'generateDocument']);
        Route::get('listModelFromHTM/{ten}', [CircullarTenController::class, 'listModelFromHTM']);
        Route::get('sendToDMS/{ten}', [CircullarTenController::class, 'sendToDMS']);
        Route::get('sendToDMSNew/{ten}', [CircullarTenController::class, 'sendToDMSNew']);
        Route::get('findModelCode/{item}', [CircullarTenController::class, 'findItem']);
        Route::get('addModelDetail/{ten}/{item}', [CircullarTenController::class, 'addModelDetail']);

        Route::resource('cirtenUpdate', CirtenUpdateController::class);

        Route::get('resubmitCirten/{ten}/{username?}', [CirtenUpdateController::class, 'resubmitCirten']);
        Route::get('tenList/{date}', [CirtenUpdateController::class, 'showByDateTen']);
        Route::get('syncTenList/{date}', [CirtenUpdateController::class, 'syncTenList']);
        Route::get('generateDocumentUp/{ten}/{username?}', [CirtenUpdateController::class, 'generateDocument']);
        Route::get('cekViewPrint/{ten}', [CirtenUpdateController::class, 'cekViewPrint']);
        Route::get('cekFilePDF/{ten}', [CirtenUpdateController::class, 'cekFilePDF']);
        Route::get('deleteModel/{tenid}/{model}', [CirtenUpdateController::class, 'deleteSelectedModel']);

        Route::get('sendToDMSNew/{ten}', [CircullarTenController::class, 'sendToDMSNew']);

        Route::get('viewListItemDesc/{ten}', [CirtenUpdateController::class, 'viewListItemDesc']);
        Route::get('getDataMRPWeekDatas/{fdate}/{ldate}', [MRPWeeklyBasedController::class, 'getData']);
        // Route::post('getDataMRPWeek', [MRPWeeklyBasedController::class, 'getReport']);
        Route::match(['post', 'head'], 'getDataMRPWeek', [MRPWeeklyBasedController::class, 'getReport']);
        Route::get('getReportTest/{mrpDate}/{firstDate}/{poRelDate?}/{poIssDate?}/{mrpCutoffDate?}/{weekCount?}/{lt?}', [MRPWeeklyBasedController::class, 'getReportTest']);


        // CirtenUpdateController
    });

    Route::group(['prefix' => 'pc'], function () {
        Route::get('syncBOMtoPSI', [autoSyncBOMtoPSIController::class, 'syncBOM']);
        Route::get('syncBOMtoPSIByItem/{item}', [autoSyncBOMtoPSIController::class, 'syncBOMbyItem']);
        Route::post('syncBOMMultipleItem', [autoSyncBOMtoPSIController::class, 'syncWithoutJobs']);
        Route::get('syncAllNotInterfaced', [autoSyncBOMtoPSIController::class, 'syncAllNotInterfaced']);
        Route::get('syncSGLStock', [autoSyncBOMtoPSIController::class, 'updateStockSGL']);

        Route::get('autoMailOSDOList', [autoEmailWMSConfirmation::class, 'sendEmailFun']);
    });

    Route::group(['prefix' => 'it'], function () {
        Route::resource('scan', PartScannerController::class);
        Route::post('generateLabel', [PartScannerController::class, 'ZPLGenerate']);
        Route::post('renderLabel', [PartScannerController::class, 'renderLabel']);
        Route::get('listLabels', [PartScannerController::class, 'listLabels']);
    });

    Route::group(['prefix' => 'ocd'], function () {
        Route::resource('autoScanKitting', PartScannerController::class);
        Route::post('getWHFromMega', [PartScannerController::class, 'getWHFromMega']);
        Route::post('getBGFromMega', [PartScannerController::class, 'getBGFromMega']);
        Route::post('getDOFromMegaWMS', [PartScannerController::class, 'getDOFromMegaWMS']);
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
Route::resource('mobileGencode', MobileGencodeController::class);
Route::resource('labelManager', LabelManagerController::class);

Route::post('forgot-password', [AuthController::class, 'forgot_password']);
Route::post('reset-password/{token}', [AuthController::class, 'submitResetPasswordForm']);

Route::get('redis', function () {
    try {
        $redis = \Redis::connect('192.168.100.32', 6379);
        return response('redis working');
    } catch (\Predis\Connection\ConnectionException $e) {
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

    Redis::publish('portalv2', json_encode([
        'app' => 'testing',
        'message' => "You have new notification Testing notif",
        'type' => 'info',
        'data' => []
    ]));

    Redis::publish('test-channel', 'a test message');

    $prefix = config('database.redis.options.prefix');
    $channel = $prefix . 'test-channel';

    return "Done. (published on $channel)";
});

Route::get('local/temp/{path}', function (string $path) {
    return Storage::disk('local')->download($path);
})->name('local.temp');
