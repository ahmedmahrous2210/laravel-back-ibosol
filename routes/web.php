<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () use ($router) {
    //return $router->app->version();
    return "<p>Welcome to IBO API Portal </p>";
});
//  $router->post('/custAuth', 'IBOUserController@custAuth');
//  $router->post('/custAuthActiCode', 'IBOUserController@custAuthActiCode');
//  $router->post('/add-streamlist', 'IBOUserController@addStreamlistActivationCode');
//  $router->post('/add-user', 'IBOUserController@addUser');
//  $router->post('/update-status', 'IBOUserController@updateStatus');
//  $router->post('/user-list', 'IBOUserController@users');
//  $router->post('/edit-user', 'IBOUserController@editUser');
//  $router->post('/user', 'IBOUserController@getUserById');
//  $router->post('/acti-code-list', 'IBOUserController@getStreamListActi');
//  $router->post('/search-user', 'IBOUserController@serachUser');


//  //app-update
//  $router->post('/add-appupdate', 'IBOAppUpdateController@addAppUpdate');
//  $router->post('/appupdate-list', 'IBOAppUpdateController@getAppUpdateList');

//  //message
//  $router->post('/add-message', 'IBOMessageController@addMessage');
//  $router->post('/message-list', 'IBOMessageController@messageList');

//  //reseller+admin
//  $router->post('/adminLogin', 'IBOResellerController@login');
//  $router->post('/resellerList', 'IBOResellerController@resellerList');
//  $router->post('/getResellerById', 'IBOResellerController@getResellerById');
//  $router->post('/updateReseller', 'IBOResellerController@updateReseller');
//  $router->post('/updateStatusRes', 'IBOResellerController@updateStatus');


 //common for all moduleApi -
 $router->group(['middleware' => ['webEnc']], function ($router) {
     $router->post('/api/v1/adminLogin', 'CommonController@login');
     $router->post('/api/v1/commonUpdateStatus', 'CommonController@updateStatus');
     $router->post('/api/v1/add-reseller', 'CommonController@addReseller');
     $router->post('/api/v1/reseller-list', 'CommonController@resellerList');
     $router->post('/api/v1/add-user', 'CommonController@addUser');
     $router->post('/api/v1/user-list', 'CommonController@getUserList');
     $router->post('/api/v1/user', 'CommonController@getUserById');
     $router->post('/api/v1/edit-user', 'CommonController@editUser');
     $router->post('/api/v1/get-reseller', 'CommonController@getResellerById');
     $router->post('/api/v1/edit-reseller', 'CommonController@editReseller');
     $router->post('/api/v1/search-user', 'CommonController@searchUser');
     $router->post('/api/v1/add-credit', 'CommonController@addCreditPoint');
     $router->post('/api/v1/res-assign-list', 'CommonController@resellerListAssigment');
     $router->post('/api/v1/res-switch-assign-list', 'CommonController@resellerSwitchListAssigment');
	$router->post('/api/v1/change-role', 'CommonController@changeRoleToReseller');

     $router->post('/api/v1/assign-subreseller', 'CommonController@assigmentParentRes');
     $router->post('/api/v1/user-acti-log', 'CommonController@getUserActiTranLogs');
     $router->post('/api/v1/credit-tran-logs', 'CommonController@getCreditShareTranLogs');
     $router->post('/api/v1/add-playlist-common', 'CommonController@addPlaylistDetail');
     $router->post('/api/v1/reset-playlist', 'CommonController@resetPlaylist');
     $router->post('/api/v1/edit-mac', 'CommonController@editMac');
     //$router->post('/api/v1/dashboard-count', 'CommonController@dashboardCounts');
     $router->post('/api/v1/remove-res', 'CommonController@removeReseller');
     $router->post('/api/v1/client-detail', 'CommonController@clientDetail');
     $router->post('/api/v1/debit-credit', 'CommonController@debitCreditPointFromReseller');
     $router->post('/api/v1/dashboard-count', 'DashboardController@getCounts');



    $router->post('/api/v1/create-application', 'CommonController@createApplication');
    $router->post('/api/v1/applications', 'CommonController@applications');
    $router->post('/api/v1/app-list', 'CommonController@applicationListTree');

    //reseller-app-settings
    $router->post('/api/v1/create-res-app', 'CommonController@createResellerApplication');
    $router->post('/api/v1/res-applications', 'CommonController@resellerApplications');
    $router->post('/api/v1/get-res-app', 'CommonController@getResApp');
    $router->post('/api/v1/edit-res-app', 'CommonController@editResApp');
    $router->post('/api/v1/remove-res-app', 'CommonController@removeAppSetting');
    $router->post('/api/v1/get-res-credit', 'CommonController@getResellerCreditPoint');
    $router->post('/api/v1/update-password', 'CommonController@UpdatePassword');
    $router->post('/api/v1/user-acti-report', 'CommonController@getUserActiReports');
    $router->post('/api/v1/res-tran-report', 'CommonController@getResTranReports');
    $router->post('/api/v1/add-res-notif', 'CommonController@createResNotification');
    $router->post('/api/v1/res-notif', 'CommonController@resellerNotifList');

    $router->post('/api/v1/get-res-notif', 'CommonController@getActiveResNotif');
    $router->post('/api/v1/get-res-notif-count', 'CommonController@getActiveResNotifCount');
    $router->post('/api/v1/get-res-notif-all', 'CommonController@getActiveResNotifAll');
    //disable-mac
    $router->post('/api/v1/disable-mac', 'CommonController@disableMac');
    $router->post('/api/v1/client-list', 'ClientsController@clientList');
    $router->post('/api/v1/add-client', 'ClientsController@addClient');
    $router->post('/api/v1/add-credit-client', 'ClientsController@addCreditPoint');
    $router->post('/api/v1/debit-credit-client', 'ClientsController@debitCreditPointFromClient');
    $router->post('/api/v1/client-tran-logs', 'ClientsController@ClientTransLogs');
    $router->post('/api/v1/recharge-credit', 'CommonController@rechargeCredit');
    $router->post('/api/v1/acti-report-rese', 'CommonController@getUserActiReportByReseller');
    $router->post('/api/v1/sub-res-list', 'CommonController@subResellerList');
    $router->post('/api/v1/edit-reseller-web-logo', 'CommonController@editResellerWebLogo');
    $router->post('/api/v1/social-widget', 'CommonController@socialWidget');
    $router->post('/api/v1/get-social-widget', 'CommonController@getSocialDetails');
    $router->post('/api/v1/get-chart-data', 'DashboardController@getGraphData');
    $router->post('/api/v1/get-disable-all-mac', 'IBOResellerController@disableAllResellerMac');

    $router->post('/api/v1/add-iboprotv-code', 'IBOResellerController@getStoreIBOPROCode');

    $router->post('/api/v1/get-iboprotv-codelist', 'IBOResellerController@getIboProCodeList');

    $router->post('/api/v1/ibopro-add-edit-playlist', 'IBOResellerController@addIboProTvPlaylist');
    $router->post('/api/v1/ibopro-add-change-status', 'IBOResellerController@changeIboProCodeStatus');
    $router->post('/api/v1/get-multi-app-activate', 'IBOResellerController@getActivateMultiApps');
    $router->post('/api/v1/credit-share-password', 'IBOResellerController@validateResellerSharePassCode');
    $router->post('/api/v1/add-ticket', 'TicketController@addTicket');
    $router->post('/api/v1/my-tickets', 'TicketController@myTickets');
    $router->post('/api/v1/register-credit-share-pass', 'IBOResellerController@setCreditSharePass');
    $router->post('/api/v1/change-ticket-status', 'TicketController@changeStatus');
    $router->post('/api/v1/all-tickets', 'TicketController@getAllOpenTicket');
    $router->post('/api/v1/admin-ticket-reply', 'TicketController@updateAdminRemark');
    $router->post('/api/v1/search-reseller', 'IBOResellerController@searchReseller');
    $router->post('/api/v1/credit-share-pass', 'IBOResellerController@updateCreditSharePassword');
    $router->post('/api/v1/search-mac', 'CommonController@searchMac');

    $router->post('/api/v1/get-payment-link', 'PassipayController@passiPayGetPaymentUrl');
    $router->post('/api/v1/get-payment-details', 'PassipayController@passiPayGetPaymentDetails');

    $router->post('/api/v1/get-orders-list', 'PassipayController@getMyOrders');
});

 $router->post('/api/v1/credit-tran-logs123', 'CommonController@getCreditShareTranLogs');
$router->post('/api/v1/device-reg', 'IBOIPTVController@commonApiData');
// $router->post('/api/v1/reg-device', 'IBOIPTVController@getReg');
$router->post('/api/v1/get-detail', 'IBOIPTVController@getMacDetails');
//$router->post('/api/v1/activate-box', 'IBOIPTVController@@boxActivationNew');
$router->post('/api/v1/activate-box-new', 'IBOIPTVController@boxActivationNew');
$router->post('/api/v1/get-notif', 'IBOIPTVController@getNotification');

$router->post('/api/v1/check-him', 'BoxActivationAPIController@searchUser');
$router->post('/api/v1/decrypt', 'BoxActivationAPIController@decrypt');
$router->post('/api/v1/encrypt', 'BoxActivationAPIController@encrypt');
$router->get('/api/v1/mongo', 'CommonController@getMongoWorking');
$router->get('/api/v1/send-email', 'IBOResellerController@sendVerifyLinkEmail');
 //end for client apis
//$router->group(['middleware' => ['enc']], function ($router) {
 //   $router->post('/api/v1/enc/check-mac', 'BoxActivationAPIController@searchUser');
 //   $router->post('/api/v1/enc/activate-box', 'BoxActivationAPIController@boxActivationClient');
//});

//end for zen-tv-client apis
//$router->group(['middleware' => ['enc']], function ($router) {
    //$router->post('/api/v1/enc/check-mac', 'BoxActivationAPIController@searchUser');
   // $router->post('/api/v1/enc/activate-box-zen', 'BoxActivationAPIController@boxActivationZen');
//});

// $router->post('/api/v1/update-expire', 'CommonController@updateBoxExpiryBulk');
$router->post('/api/v1/my-android-app', 'IBOIPTVController@forIbrahimOnly');
//latest for New IBOIPTV ANDROID BOXES


$router->group(['middleware' => ['enc']], function ($router) {
    $router->post('/api/v1/android-reg', 'IBOIPTVController@mainIBOIPTVBoxRegistration');
});
