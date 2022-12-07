<?php

use App\Http\Controllers\API\DMS\DocumentController;
use App\Http\Controllers\API\DMS\FolderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PORTAL\AuthController;
use App\Http\Controllers\API\PORTAL\ProfileController;
use App\Http\Controllers\API\PORTAL\ProfilesController;
use App\Http\Controllers\API\PORTAL\UsersController;
use App\Http\Controllers\API\PORTAL\AppController;
use App\Http\Controllers\API\PORTAL\RoleController;
use App\Http\Controllers\STXI\EMS2\deliveryMethodToPSIController;
use App\Http\Controllers\STXI\EMS2\INSWDataController;
use App\Http\Controllers\STXI\EMS2\poSummaryController;
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

Route::group(['prefix' => 'portal', 'middleware' => 'auth:sanctum', 'verify' => true], function() {

    // Settings Menu
    Route::resource('users', UsersController::class);

    Route::resource('profiles', ProfilesController::class);

    Route::resource('apps', AppController::class);
    Route::get('appsParent', [AppController::class, 'indexParentOnly']);

    Route::resource('roles', RoleController::class);

    // Dashboard
    Route::post('profile', [ProfileController::class, 'store'])->middleware('verified');
    Route::get('countryList', [ProfileController::class, 'getCountryList']);
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

Route::group(['prefix' => 'div'], function () {
    Route::group(['prefix' => 'ems2'], function () {
        // Start DLV Method SMT
        Route::get('itemSearch/{filter}', [deliveryMethodToPSIController::class, 'searchItemMaster']);
        Route::get('spq', [deliveryMethodToPSIController::class, 'SPQIndex']);
        Route::post('spq', [deliveryMethodToPSIController::class, 'SPQCreateUpdate']);
        Route::delete('spq/{id}', [deliveryMethodToPSIController::class, 'SPQDeleteData']);
        Route::get('spqChecker/{qty}/{qtyDel}/{model}', [deliveryMethodToPSIController::class, 'DLVCalSPQRes']);

        Route::get('dlv/{date?}', [deliveryMethodToPSIController::class, 'DLVIndex']);
        Route::get('dlvEmail/{date}', [deliveryMethodToPSIController::class, 'DLVSendEmail']);
        Route::post('dlv', [deliveryMethodToPSIController::class, 'DLVWithBarcode']);
        Route::post('dlvStore', [deliveryMethodToPSIController::class, 'DLVStore']);
        Route::get('dlvExport', [deliveryMethodToPSIController::class, 'DLVExport']);
        Route::get('dlvDelete/{date}/{loc?}/{item_num?}', [deliveryMethodToPSIController::class, 'deleteDelivery']);

        Route::post('uploadSPQ', [deliveryMethodToPSIController::class, 'UploadSPQ']);
        Route::get('syncBOMToPSI', [deliveryMethodToPSIController::class, 'syncBOMToPSI']);
        Route::get('DLVStockDelivery/{date}/{item?}', [deliveryMethodToPSIController::class, 'DLVStockDelivery']);

        Route::get('fifoData/{date?}/{item?}/{saved?}/{byItemOnly?}/{dateFifoStart?}', [deliveryMethodToPSIController::class, 'fifoUpdateDLV']);
        Route::get('getFifoData/{date?}/{item?}', [deliveryMethodToPSIController::class, 'showFifoDLV']);

        Route::get('exportDOExcel/{date}/{item?}', [deliveryMethodToPSIController::class, 'exportDOExcel']);
        Route::get('getNextDN/{date}', [deliveryMethodToPSIController::class, 'deliveryLatestNo']);
        Route::post('deliveryToTYO', [deliveryMethodToPSIController::class, 'deliveryToTYO']);

        Route::post('uploadWeeklyPOData', [deliveryMethodToPSIController::class, 'uploadWeeklyPOData']);
        Route::get('getUploadedWeeklyPO/{date}', [deliveryMethodToPSIController::class, 'getUploadedWeeklyPO']);
        Route::get('exportWeeklyReport/{date}', [deliveryMethodToPSIController::class, 'ExportWeeklyReport']);
        // End DLV Method SMT

        // Start PO Summary
        Route::post('uploadRawPO', [poSummaryController::class, 'uploadPO']);
        Route::get('POGetData/{date}', [poSummaryController::class, 'POGetData']);
        Route::get('POGetDataDet/{date}', [poSummaryController::class, 'POGetDataDet']);
        Route::get('POExportData/{date}', [poSummaryController::class, 'exportPO']);
        Route::get('PODetExportData/{date}', [poSummaryController::class, 'exportPODet']);
        // End PO Summary
    });

    Route::group(['prefix' => 'log'], function () {
        Route::get('INSWGetData/{filter}', [INSWDataController::class, 'getData']);
    });
});

Route::group((['prefix' => 'scheduller']), function () {

});

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('countryList', [ProfileController::class, 'getCountryList']);
