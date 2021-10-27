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

    Route::get('/getcontentedit/{id}', 'PORTAL\DocumentController@getContent');

    // Print Cover
    Route::post('/generatepdf', 'PORTAL\DocumentController@printcover');

    // Circular Ten
    Route::post('/uploadfilecirten', 'PORTAL\Customs\CircullarTenController@uploadCirtenAttachment');

    Route::post('/uploadtogetdet', 'PORTAL\Customs\CircullarTenController@uploadtogetdet');

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

    Route::get('portalloginoveride/{username}/{token}', 'DMS\Auth\LoginController@portalloginoveride');

    // -- Settings --

    // User
    Route::get('registereduser', 'DMS\Auth\RegisterController@GetAllUser');
    Route::get('getalldomain', 'DMS\Auth\RegisterController@GetAllDomain');

    Route::get('updateuserrole/{username}/{newrole}', 'DMS\Auth\RegisterController@updateUserRole');
    Route::get('updateuseractivation/{username}', 'DMS\Auth\RegisterController@updateuseractivation');

    Route::post('updateprofile', 'DMS\Auth\RegisterController@updateuserprofile');

    Route::get('deletesignature/{username}', 'DMS\Auth\RegisterController@deletesignature');

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
    Route::get('/updatedoclocation/{user}/{iddoc}/{idloc}', 'DMS\Core\DocsManageController@moveDocument');

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

    Route::post('/sharingdocument', 'DMS\Core\DocsManageController@storeSharedFolder');
    Route::get('/getalltags', 'DMS\Core\DocsManageController@getAllTags');

    Route::post('/updatetags', 'DMS\Core\DocsManageController@updateTags');

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
    Route::post('/filtermail/{user}/{inout}', 'DMS\Core\ApprovalController@filtermail');

    Route::get('/getdocsenttoapprover/{user}/{apprstat?}/{date?}/{doc?}', 'DMS\Core\ApprovalController@listDocSenttoApprover');
    Route::get('/getdataapprovalbydoc/{user}/{doc?}', 'DMS\Core\ApprovalController@listDocSenttoApproverByDoc');

    Route::post('/downloaddocumentstatus', 'DMS\Core\ApprovalController@downloadDocstatus');

    // Document List
    Route::get('/getalldocumentbydivision', 'DMS\Core\DashboardController@getalldocumentbyrole');
    Route::get('/getalloutstanding/{user}', 'DMS\Core\DashboardController@getOutstandingByUser');
    Route::get('/getnewlibdocs', 'DMS\Core\DashboardController@newDocumentLib');

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

    Route::get('/testingOcr', 'DMS\Core\LogicalController@testingOCR');
    Route::post('/saveLogical', 'DMS\Core\LogicalController@saveLogical');
    Route::get('/testingCo', 'DMS\Core\LogicalController@TesterCo');

    //View List of Approved Doc
    Route::get('/showapprovedlist/{user}/{stat?}/{date?}', 'DMS\Core\ApprovalController@getDocByApprover');

    //Scheduller
    Route::group(['prefix' => 'scheduller'], function () {
        Route::get('/getallpendingapproval', 'DMS\Core\ApprovalController@checkPendingApproval');
    });
});

Route::group(['prefix' => 'hrms'], function () {

    // Send default password
    Route::get('sendDefaultPass', 'HRMS\Auth\RegisterController@sendDefaultPass');
    Route::get('sendDefaultPass/{user}', 'HRMS\Auth\RegisterController@sendDefaultPass');

    // Register
    Route::post('registeruser', 'HRMS\Auth\RegisterController@create');
    Route::get('verify/{username}/{token}', 'HRMS\Auth\RegisterController@verify');
    Route::get('cekHasilParsing', 'HRMS\Auth\RegisterController@parsePassword');

    // Login
    Route::post('login', 'HRMS\Auth\LoginController@Login');
    Route::post('resetpassword', 'HRMS\Auth\RegisterController@ResetPassword');
    Route::get('portalloginoveride/{username}/{token}', 'HRMS\Auth\LoginController@portalloginoveride');

    // Deep Login

    // Menu Manage
    Route::get('getmenulist/{url}', 'HRMS\Auth\MenuController@show');
    Route::get('getmenu/{id}', 'HRMS\Auth\MenuController@cekmenuid');
    Route::get('getmenuall/{cek}', 'HRMS\Auth\MenuController@index');
    Route::post('menuadd/{method}', 'HRMS\Auth\MenuController@getmenu');

    // Role
    Route::get('/getallrole', 'HRMS\Auth\RoleController@index');
    Route::get('/getrole/{id}', 'HRMS\Auth\RoleController@cekroleid');
    Route::post('/roleadd/{met}', 'HRMS\Auth\RoleController@store');

    /*
    -----------------------------------
    CORE FUNCTION
    -----------------------------------
    */
    Route::get('/testing', 'HRMS\Core\FormController@testing');

    Route::get('/getmapping', 'HRMS\Core\FormController@getFormMapping');
    Route::get('/getformbytokenpublic/{id}', 'HRMS\Core\FormController@getFormDataByToken');
    Route::get('/getformbytokenpublic/{id}/{ansid}', 'HRMS\Core\FormController@getFormDataByToken');
    Route::post('/storemappingcontentform', 'HRMS\Core\FormController@storeFormMappingContent');
    Route::post('/storeformhist', 'HRMS\Core\FormController@storeFormHist');

    Route::get('/getdomain', 'HRMS\Core\domainController@index');

    // Need to authorization login
    Route::middleware('auth:apihrms')->group(function ()
    {
        Route::get('/testing', 'HRMS\Core\FormController@testing');
    });


        // Auth Detail
        Route::get('/getuserlist', 'HRMS\Core\userController@index');

        // Form and Component Creator
        Route::post('/storeform', 'HRMS\Core\FormController@storeForm');
        Route::get('/getform', 'HRMS\Core\FormController@getFormByID');
        Route::get('/getform/{id}', 'HRMS\Core\FormController@getFormByID');
        Route::post('/storelogics', 'HRMS\Core\FormController@storeLogics');
        Route::post('/storeformmapping', 'HRMS\Core\FormController@storeFormMapping');
        Route::get('/getformbytoken/{id}', 'HRMS\Core\FormController@getFormDataByToken');
        Route::get('/getallform/{username}/{met}', 'HRMS\Core\FormController@getAllForm');
        Route::post('/exportTrainingValue', 'HRMS\Core\FormController@exportTrainingValue');
        Route::get('/deletelatestform/{token}/{users}', 'HRMS\Core\FormController@deleteHistory');

        // getTrainingForm
        Route::get('/getTrainingForm', 'HRMS\Core\FormController@getTrainingForm');
        Route::get('/getTrainingForm/{users}', 'HRMS\Core\FormController@getTrainingForm');

        Route::post('/storebio', 'HRMS\Core\PersonalController@storeBio');

        // Mapping Organization
        Route::post('/storedomain', 'HRMS\Core\domainController@save');

        Route::get('/getdivision', 'HRMS\Core\DivisionController@index');
        Route::post('/storedivision', 'HRMS\Core\DivisionController@save');

        Route::get('/getocc', 'HRMS\Core\occController@index');
        Route::post('/storeocc', 'HRMS\Core\occController@save');

        Route::post('/defineuserocc', 'HRMS\Core\organizationController@save');

        // E-Letter
        Route::post('/saveelettertemp', 'HRMS\Core\eLetterController@save');
        Route::get('/getletter', 'HRMS\Core\eLetterController@index');
        Route::get('/getletter/{id}', 'HRMS\Core\eLetterController@index');

        Route::get('/getform', 'HRMS\Core\eLetterController@getlistform');
});
