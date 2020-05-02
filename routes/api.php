<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'portal'], function () {
    // Route::get('testing', 'PORTAL\LoginController@testing');
    Route::post('registeruser', 'PORTAL\RegisterController@create');
    Route::post('resetpassword', 'PORTAL\RegisterController@ResetPassword');
    Route::post('login', 'PORTAL\LoginController@Login');

    Route::get('verify/{username}/{token}','PORTAL\RegisterController@verify');
    Route::get('registereduser', 'PORTAL\RegisterController@GetAllUser');

    Route::get('getmenulist/{url}', 'PORTAL\MenuController@show');    
    Route::get('getmenu/{id}', 'PORTAL\MenuController@cekmenuid');
    Route::get('getmenuall/{cek}', 'PORTAL\MenuController@index');
    Route::post('menuadd/{method}', 'PORTAL\MenuController@getmenu');

    Route::get('/getallrole', 'PORTAL\RoleController@index');
    Route::get('/getrole/{id}', 'PORTAL\RoleController@cekroleid');
    Route::post('/roleadd/{met}', 'PORTAL\RoleController@store');

    Route::get('/getdivisi/{divid}', 'PORTAL\DivisiController@index');
    Route::get('/getdivisi', 'PORTAL\DivisiController@index');
});

Route::group(['prefix' => 'dms'], function () {
    // Depan
    Route::post('registeruser', 'DMS\Auth\RegisterController@create');
    Route::post('resetpassword', 'DMS\Auth\RegisterController@ResetPassword');
    Route::post('login', 'DMS\Auth\LoginController@Login');
    
    Route::get('verify/{username}/{token}','DMS\Auth\RegisterController@verify');

    // -- Settings --
    
    // User
    Route::get('registereduser', 'DMS\Auth\RegisterController@GetAllUser');    
    
    // Menu
    Route::get('getmenulist/{url}', 'DMS\Auth\MenuController@show');    
    Route::get('getmenu/{id}', 'DMS\Auth\MenuController@cekmenuid');
    Route::get('getmenuall/{cek}', 'DMS\Auth\MenuController@index');
    Route::post('menuadd/{method}', 'DMS\Auth\MenuController@getmenu');

    // Role
    Route::get('/getallrole', 'DMS\Auth\RoleController@index');
    Route::get('/getrole/{id}', 'DMS\Auth\RoleController@cekroleid');
    Route::post('/roleadd/{met}', 'DMS\Auth\RoleController@store');

    // -- Core --
    // Dashboard
    Route::get('/getdashboard/{user}', 'DMS\Core\DocsLocationController@getlist');
    Route::get('/getnotification/{user}', 'DMS\Core\DashboardController@listnotif');
    Route::get('/readnotif/{idhist}', 'DMS\Core\DashboardController@readnotif');

    // Folder Manage
    Route::get('/getdocs/{user}/{id}', 'DMS\Core\DocsLocationController@getlist');
    Route::get('/getdocs/{user}', 'DMS\Core\DocsLocationController@getlist');
    Route::post('/docsadd', 'DMS\Core\DocsLocationController@store');

    Route::post('/docsupload', 'DMS\Core\DocsManageController@uploadDocument');
    Route::post('/docsupload/{iddoc}', 'DMS\Core\DocsManageController@uploadDocument');

    Route::get('/getfiles/{user}/{id}', 'DMS\Core\DocsManageController@getfiles');
    Route::get('/getfiles/{user}', 'DMS\Core\DocsManageController@getfiles');
    Route::get('/deletefiles/{iddoc}', 'DMS\Core\DocsManageController@deleteDocument');

    Route::get('/showpdf/{user}/{doc}/{full}', 'DMS\Core\DocsManageController@showpdf');
    Route::get('/showpdf/{user}/{doc}', 'DMS\Core\DocsManageController@showpdf');
    
    // Approval Manage    
    Route::get('/apprvdoc/{doc}/{author}', 'DMS\Core\ApprovalController@ApproveDoc'); 

    Route::get('/getallapprvrole', 'DMS\Auth\RoleController@groupFetch');
    Route::post('/addapproval', 'DMS\Core\ApprovalController@ApprovalSetup');
    Route::post('/approvalsent', 'DMS\Core\ApprovalController@ApprovalSent');
    Route::post('/approvalsentbycontent', 'DMS\Core\ApprovalController@ApprovalSentByContent');

    // Approval List
    Route::get('/getallapprvoutstanding/{user}/{level?}', 'DMS\Core\ApprovalController@outstandingApproval');
    Route::get('/getallapprvoutstandingbyapprover/{user}', 'DMS\Core\ApprovalController@outstandingApprovalByApprover');
    Route::get('/getdocsenttoapprover/{user}', 'DMS\Core\ApprovalController@listDocSenttoApprover');

    // Document List
    Route::get('/getalldocumentbydivision', 'DMS\Core\DashboardController@getalldocumentbyrole');

    // Content 
    Route::post('/storecontent', 'DMS\Core\ContentManageController@store');
    Route::get('/getcontentdatadef', 'DMS\Core\ContentManageController@contentDefineData');
    Route::get('/getcontentall', 'DMS\Core\ContentManageController@index');

    //Message Approval
    Route::get('/getapprovallist/{user}', 'DMS\Core\ApprovalController@ApprovalList');
});