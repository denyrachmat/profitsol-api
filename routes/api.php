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

    Route::get('verify/{username}/{token}', 'PORTAL\RegisterController@verify');
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

    Route::get('getmenuallbyparent/{cek}/{exept?}', 'PORTAL\MenuController@ceklistmenubyparent');

    // Content 
    Route::post('/storecontent', 'PORTAL\DocumentController@store');
    Route::get('/getcontentdatadef', 'DMS\Core\ContentManageController@contentDefineData');
    Route::get('/getcontentall', 'PORTAL\DocumentController@index');
    Route::get('/getcontentall/{id_menu}', 'PORTAL\DocumentController@index');
    Route::get('/deletecontent/{id_content}', 'PORTAL\DocumentController@deletecontent');    

    // Print Cover
    Route::post('/generatepdf', 'PORTAL\DocumentController@printcover');
    
    // Circular Ten
    Route::post('/uploadfilecirten', 'PORTAL\Customs\CircullarTenController@uploadCirtenAttachment');
    Route::get('/showattachment/{pathid}/{name?}', 'PORTAL\Customs\CircullarTenController@getfiles');
    Route::post('/storecirten', 'PORTAL\Customs\CircullarTenController@store');
    
    Route::post('/deletefiles', 'PORTAL\Customs\CircullarTenController@deletefiles');
    Route::get('/cekallfiles2', 'PORTAL\Customs\CircullarTenController@cekallfileswithpath');
    Route::get('/cekallfiles2/{user}', 'PORTAL\Customs\CircullarTenController@cekallfileswithpath');
});

Route::group(['prefix' => 'dms'], function () {
    // Depan
    Route::post('registeruser', 'DMS\Auth\RegisterController@create');
    Route::post('resetpassword', 'DMS\Auth\RegisterController@ResetPassword');
    Route::post('login', 'DMS\Auth\LoginController@Login');

    Route::get('verify/{username}/{token}', 'DMS\Auth\RegisterController@verify');

    // -- Settings --

    // User
    Route::get('registereduser', 'DMS\Auth\RegisterController@GetAllUser');
    Route::get('getalldomain', 'DMS\Auth\RegisterController@GetAllDomain');

    Route::get('updateuserrole/{username}/{newrole}', 'DMS\Auth\RegisterController@updateUserRole');
    Route::get('updateuseractivation/{username}', 'DMS\Auth\RegisterController@updateuseractivation');

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
    Route::get('/getdashboard/{user}', 'DMS\Core\DashboardController@listnotif');
    Route::get('/readnotif/{userto}/{apprvid}/{ctnid}', 'DMS\Core\DashboardController@readnotif');

    // Folder Manage
    Route::get('/getdocs/{user}/{id}', 'DMS\Core\DocsLocationController@getlist');
    Route::get('/getdocs/{user}', 'DMS\Core\DocsLocationController@getlist');
    Route::post('/docsadd', 'DMS\Core\DocsLocationController@store');
    Route::get('/deletedoc/{id}', 'DMS\Core\DocsLocationController@deletefolder');

    Route::get('/getallfolder/{user}', 'DMS\Core\DocsLocationController@getlistfolder');

    Route::post('/docsupload', 'DMS\Core\DocsManageController@uploadDocument');
    Route::post('/docsupload/{iddoc}', 'DMS\Core\DocsManageController@uploadDocument');

    Route::get('/getfiles/{user}/{id}', 'DMS\Core\DocsManageController@getfiles');
    Route::get('/getfiles/{user}', 'DMS\Core\DocsManageController@getfiles');
    Route::get('/deletefiles/{iddoc}', 'DMS\Core\DocsManageController@deleteDocument');

    Route::get('/showpdf/{user}/{doc}/{full}', 'DMS\Core\DocsManageController@showpdf');
    Route::get('/showpdf/{user}/{doc}', 'DMS\Core\DocsManageController@showpdf');

    Route::get('/showoriginalpdf/{doc}/{flag?}/{full?}', 'DMS\Core\DocsManageController@showpdforiginal');

    Route::get('/toggleapprovedocflag/{iddoc}/{val}', 'DMS\Core\DocsManageController@updateflagapprvdoc');

    Route::get('/resendrejecteddoc/{iddoc}', 'DMS\Core\DocsManageController@resendrejecteddoc');
    
    Route::get('/sharedoc/{iddoc}', 'DMS\Core\DocsManageController@sharedoc');

    // Approval Manage    
    Route::get('/apprvdoc/{doc}/{author}', 'DMS\Core\ApprovalController@ApproveDoc');

    Route::get('/getallapprvrole', 'DMS\Auth\RoleController@groupFetch');
    Route::post('/addapproval', 'DMS\Core\ApprovalController@ApprovalSetup');
    Route::post('/approvalsent', 'DMS\Core\ApprovalController@ApprovalSent');
    Route::post('/approvalsentbycontent', 'DMS\Core\ApprovalController@ApprovalSentByContent');
    Route::get('/getapprovallist', 'DMS\Core\ApprovalController@AllApprovalList');
    Route::post('/updateapproval', 'DMS\Core\ApprovalController@updateApprovalList');

    
    Route::get('/emailtesting/{user}', 'DMS\Core\DocsManageController@emailsender');

    // Approval List
    Route::get('/getallapprvoutstanding/{user}/{level?}', 'DMS\Core\ApprovalController@outstandingApproval');
    Route::get('/getallapprvoutstandingbyapprover/{user}/{inoutbox?}', 'DMS\Core\ApprovalController@outstandingApprovalByApprover');
    Route::get('/getdocsenttoapprover/{user}', 'DMS\Core\ApprovalController@listDocSenttoApprover');

    // Document List
    Route::get('/getalldocumentbydivision', 'DMS\Core\DashboardController@getalldocumentbyrole');

    // Content 
    Route::post('/storecontent', 'DMS\Core\ContentManageController@store');
    Route::get('/getcontentdatadef', 'DMS\Core\ContentManageController@contentDefineData');
    Route::get('/getcontentall', 'DMS\Core\ContentManageController@index');
    Route::get('/getcontentall/{tag}/{app}', 'DMS\Core\ContentManageController@index');

    Route::post('/storeapprvcontent', 'DMS\Core\ContentManageController@StoreApprovalContent');

    //Message Approval
    Route::get('/getapprovallist/{user}', 'DMS\Core\ApprovalController@ApprovalList');

    //Print Cover
    Route::post('/printcover', 'DMS\Core\DocsManageController@printcover');

    //Circular Ten
    // Route::post('/storecirten', 'DMS\Customs\CircullarTenController@store');
    // Route::get('/testpdf', 'DMS\Customs\CircullarTenController@testSnappy');
    // Route::get('/testfolder/{id}', 'DMS\Customs\CircullarTenController@testfolder');

    // Route::get('/getallcirten', 'DMS\Customs\CircullarTenController@getcirten');

    // Route::get('/cekallfiles/{filename}', 'DMS\Customs\CircullarTenController@checkfile');
    // Route::post('/deletefiles', 'DMS\Customs\CircullarTenController@deletefiles');
});
