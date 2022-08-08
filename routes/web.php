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
	return $router->app->version();
});

//$router->get('/key', function() {
//  return \Illuminate\Support\Str::random(32);
//}); this route is used to generate APP_KEY

$router->group(['prefix' => 'v1'], function () use ($router) {
	$router->POST('/hotel/add', ['uses' => 'HotelController@add']);
	$router->POST('/hotel/list', ['uses' => 'HotelController@list']);
	$router->POST('/hotel/delete', ['uses' => 'HotelController@delete']);
	$router->POST('/hotel/changestatus', ['uses' => 'HotelController@changestatus']);
	$router->POST('/hotel/view', ['uses' => 'HotelController@view']);
	$router->POST('/hotel/deletefile', ['uses' => 'HotelController@deletefile']);
	$router->POST('/hotel/update', ['uses' => 'HotelController@update']);
	$router->POST('/hotel/listall', ['uses' => 'HotelController@listall']);	
	$router->POST('/destination/view', ['uses' => 'UserController@destview']);
	$router->POST('/destination/deletefile', ['uses' => 'UserController@deletefile']);

	/**Add alert */
	$router->POST('/alert/add', ['uses' => 'UserController@adddestinationalert']);
	$router->POST('/alert/list', ['uses' => 'UserController@listalert']);
	$router->POST('/alert/viewalert', ['uses' => 'UserController@viewalert']);
	$router->POST('/alert/deletealert', ['uses' => 'UserController@deletealert']);

/**Cron */

	$router->POST('getserviceendpoint', ['uses' => 'PublicUserController@getserviceendpont']);
	$router->POST('setserviceendpoint', ['uses' => 'PublicUserController@setserviceendpoint']);

	$router->GET('cron/emailcron', ['uses' => 'PublicUserController@sendemail']);
	$router->GET('downloadticket/{ticketname}', ['uses' => 'PublicUserController@downloadticket']);

	$router->GET('genratesampleemail/{id}/{email}', ['uses' => 'PublicUserController@genratesampleemail']);

//users group routes......

	$router->POST('groupuser/add', [
		'uses' => 'GroupUserController@register',
	]); //Group Registration

	$router->POST('groupuser/list', [
		'uses' => 'GroupUserController@list',
	]); //Group Listing

	$router->POST('groupuser/edit', [
		'uses' => 'GroupUserController@edit',
	]); //Group edit

	$router->POST('groupuser/view', [
		'uses' => 'GroupUserController@view',
	]); //get single user

	$router->POST('groupuser/delete', [
		'uses' => 'GroupUserController@delete',
	]); //get single user

	$router->POST('groupuser/status', ['uses' => 'GroupUserController@status',
	]); //get change status

	$router->POST('user/create', [
		'uses' => 'UserController@register',
	]); //user Registration

	$router->POST('user/login', [
		'uses' => 'UserController@login',
	]); //User Login
	$router->POST('user/getusertoken', [
		'uses' => 'UserController@getusertoken',
	]); //User Login
	//'middleware' => ['firstMiddleware','secondMiddleware']
	$router->GET('user/logout', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@logout',
	]); //user logout

	$router->POST('user/logout', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@logout',
	]); //user logout

	$router->POST('user/view', ['uses' => 'UserController@list', 'middleware' => ['auth']]);

//$router->POST('user/view', ['middleware' => 'auth','uses' => 'UserController@list']); //list all user

	$router->POST('user/roleCreate', [/*'middleware' => */
		'uses' => 'UserController@roleCreate',
	]); //create role

	$router->POST('user/roleList', [/*'middleware' => */
		'uses' => 'UserController@roleList',
	]); //list role

	$router->POST('user/searchUser', [/*'middleware' => */
		'uses' => 'UserController@searchUser',
	]); //searchUser

	$router->POST('user/roleDelete', [/*'middleware' => */
		'uses' => 'UserController@roleDelete',
	]); //delete role

	$router->POST('user/permissionList', [/*'middleware' => */
		'uses' => 'UserController@permissionList',
	]); //List permission

	$router->POST('routGuard/create', [/*'middleware' => */
		'uses' => 'UserController@createRout',
	]); //insert data into acl_root tabole

	$router->POST('user/givePermission', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@givePermission',
	]); //give permission permission

	$router->POST('user/createPermission', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@createPermission',
	]); //createPermission

	$router->POST('user/removePermssion', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@removePermssion',
	]); //removePermssion

	$router->POST('user/viewPermissionRole', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@viewPermissionRole',
	]); //removePermssion

	$router->POST('user/settings', [
		'uses' => 'UserController@settings',
	]); //removePermssion

	$router->POST('getData', [
		'uses' => 'UserController@getData',
	]); //removePermssion

	$router->POST('getMenu', [
		'uses' => 'UserController@getMenu',
	]); //get Menu

	$router->POST('getUgrpData', [
		'uses' => 'UserController@getUgrpData',
	]); //removePermssion

	$router->POST('user/edit', ['middleware' => ['acl'],
		'uses' => 'UserController@edit',
	]); //edit user

	$router->PUT('user/view/user_get', ['middleware' => ['acl'],
		'uses' => 'UserController@getuser',
	]); //get single user

	$router->POST('user/delete', ['middleware' => ['acl'],
		'uses' => 'UserController@deluser',
	]); //get single user

	$router->POST('user/status', ['uses' => 'UserController@status', 'middleware' => ['acl'],
	]); //change staus user

	$router->POST('user/getcounterlist', ['uses' => 'UserController@getcounterlist',
	]); //change staus user

	$router->POST('test', ['uses' => 'UserController@encrypt', 'middleware' => 'create', 'middleware' => ['acl'],
	]); //test encryption

	$router->POST('user/forget', ['uses' => 'UserController@forget', 'middleware' => ['acl'],
	]); //forget password

	$router->POST('user/reset', ['middleware' => ['acl'], 'uses' => 'UserController@reset',
	]); //get reset password

	$router->POST('user/passwordreset', ['uses' => 'UserController@resetPassword', 'middleware' => ['acl'],
	]); //get single user

//Master Routes...

	$router->POST('master/data/list', ['uses' => 'DataController@list', 'middleware' => ['acl'],
	]); //List Details of schema

	$router->POST('master/data/create', ['uses' => 'DataController@create', 'middleware' => ['acl'],
	]); //add data

	$router->POST('master/data/edit', ['uses' => 'DataController@edit', 'middleware' => ['acl'],
	]); //edit data

	$router->POST('master/data/import', ['uses' => 'DataController@insertExcel', 'middleware' => ['acl'],
	]); //edit data

	$router->POST('master/data/delete', ['uses' => 'DataController@delete', 'middleware' => ['acl'],
	]); //delete data

	$router->POST('master/data/view', ['uses' => 'DataController@view', 'middleware' => ['acl'],
	]); //delete data

	$router->POST('ticketing/data/ca/create', ['uses' => 'TicketController@counterAttraction', 'middleware' => ['acl'],
	]); //insert data into counter_attraction

	$router->POST('ticketing/data/ca/create', ['uses' => 'TicketController@counterAttraction', 'middleware' => ['acl'],
	]); //insert data into counter_attraction

	$router->POST('ticketing/data/link_counter', ['uses' => 'TicketController@linkCounter', 'middleware' => ['acl'],
	]); //link counter with user

	$router->POST('ticketing/data/list_counter', ['uses' => 'TicketController@listCounter', ['middleware' => 'acl'],
	]); //list usercounter

	$router->POST('ticketing/data/delete_counter', ['uses' => 'TicketController@deleteCounter', 'middleware' => ['acl'],
	]); //list usercounter

	$router->POST('ticketing/data/create_class', ['uses' => 'TicketController@createClass', 'middleware' => ['acl'],
	]); //creat class

	$router->POST('ticketing/data/list_class', ['uses' => 'TicketController@listClass', 'middleware' => ['acl'],
	]); //list class

	$router->POST('ticketing/data/edit_class', ['uses' => 'TicketController@editClass', 'middleware' => ['acl'],
	]); //edit  class

	$router->POST('ticketing/data/delete_class', ['uses' => 'TicketController@deleteClass', 'middleware' => ['acl'],
	]); //delete  class

	$router->POST('ticketing/data/changeclassstatus', ['uses' => 'TicketController@statusChange', 'middleware' => ['acl'],
	]); //delete  class

	$router->POST('ticketing/data/view_ticket', ['uses' => 'TicketController@viewTicket', 'middleware' => ['acl'],
	]); //view  ticket

	$router->POST('ticketing/data/list_ticket', ['uses' => 'TicketController@listTicket', 'middleware' => ['auth']]); //view  ticket

	$router->POST('ticketing/data/invalid_ticket', ['uses' => 'TicketController@invalidTicket', 'middleware' => ['auth']]); //view  ticket

	$router->POST('ticketing/getcounters', ['uses' => 'TicketController@getCounters', 'middleware' => [/*'acl'*/],
	]); //view  ticket

	$router->POST('ticketing/data/delete_ticket', ['uses' => 'TicketController@deleteTicket', 'middleware' => ['acl'],
	]); //delete ticket

	$router->POST('ticketing/data/create_ticket_class', ['uses' => 'TicketController@createTicketClass', 'middleware' => ['acl'],
	]); //create new ticket

	$router->POST('ticketing/data/searchTicket', ['uses' => 'TicketController@searchTicket', 'middleware' => ['acl'],
	]); //create new ticket

	$router->POST('ticketing/changecounterstatus', ['uses' => 'TicketController@changecounterstatus', 'middleware' => ['acl'],
	]); //counter opening and closing
	$router->GET('ticketing/exportincome', ['uses' => 'TicketController@exportincome', 'middleware' => [/*'acl'*/],
	]); //counter opening and closing

	$router->GET('ticketing/exportchangeincome', ['uses' => 'TicketController@exportchangeincome', 'middleware' => [/*'acl'*/],
	]); //counter opening and closing

	$router->POST('user/classorder', ['uses' => 'UserController@classorder', 'middleware' => [/*'acl'*/],
	]); //Open and close counter

	$router->POST('user/getclassorder', ['uses' => 'UserController@getclassorder', 'middleware' => [/*'acl'*/],
	]); //Open and close counter

	$router->POST('user/createdest', ['uses' => 'UserController@createdest', 'middleware' => [/*'acl'*/],
	]); //create destination

	$router->POST('user/destinationList', ['uses' => 'UserController@destinationList', 'middleware' => [/*'acl'*/],
	]); //list destination

	$router->POST('user/delete_dest', ['uses' => 'UserController@deleteDest', 'middleware' => [/*'acl'*/],
	]); //delete destination

	$router->POST('user/changedeststatus', ['uses' => 'UserController@statusChange', 'middleware' => ['acl'],
	]); //change status destination

	$router->POST('user/edit_dest', ['uses' => 'UserController@editDest', 'middleware' => ['acl'],
	]); //edit  destination

	$router->POST('user/destdetailsupdate', ['uses' => 'UserController@destUpdates', 'middleware' => [/*'acl'*/],
	]); //update more destination details

	$router->POST('user/getDestData', ['uses' => 'UserController@getDestData']);

	$router->POST('user/destinationsData', ['uses' => 'UserController@destinationsData', 'middleware' => [/*'acl'*/],
	]); //destination data for public app

	$router->POST('user/destinationHotels', ['uses' => 'UserController@destinationHotels', 'middleware' => [/*'acl'*/],
	]); //destination hotels

	$router->POST('user/weatherList', ['uses' => 'UserController@weatherList', 'middleware' => [/*'acl'*/],
	]); //list weather alert

	$router->POST('user/createWeatheralert', ['uses' => 'UserController@createWeatheralert', 'middleware' => [/*'acl'*/],
	]); //create weather alert

	$router->POST('user/changeweatherstatus', ['uses' => 'UserController@weatherstatusChange', 'middleware' => ['acl'],
	]); //change status weather

	$router->POST('user/delete_weather', ['uses' => 'UserController@deleteWeather', 'middleware' => [/*'acl'*/],
	]); //delete weather

	$router->POST('user/delete_weatherdest', ['uses' => 'UserController@deleteWeatherDest', 'middleware' => [/*'acl'*/],
	]); //delete weather

	$router->POST('ticketing/data/oc_counter', ['uses' => 'TicketController@openCloseCounter', 'middleware' => ['acl'],
	]); //Open and close counter

/*$router->POST('ticketing/data/sync', ['uses' => 'TicketController@offlineSync','middleware' =>[ 'acl']
]); //Offline syncyning*/

	$router->POST('ticketing/data/sync', ['uses' => 'TicketController@dataSync', 'middleware' => [/* 'acl'*/],
	]); //Offline syncyning

	$router->POST('ticketing/data/localsync', ['uses' => 'TicketController@localsync', 'middleware' => [/* 'acl'*/],
	]); //Offline syncyning

	$router->POST('ticketing/data/verification', ['uses' => 'TicketController@verification', 'middleware' => [/*'acl'*/],
	]); //ticket verification.

	$router->POST('ticketing/data/getdata', ['uses' => 'TicketController@getDetails', 'middleware' => [/*'acl'*/],
	]); //ticket verification.

	$router->POST('ticketing/data/offlineupdateverification', ['uses' => 'TicketController@offlineupdateverification', 'middleware' => [/*'acl'*/],
	]); //view  ticket

	$router->POST('ticketing/cancel', ['uses' => 'TicketController@cancel', 'middleware' => [/* 'acl'*/],
	]); //link counter with user

	$router->POST('ticketing/changedate', ['uses' => 'TicketController@changedate', 'middleware' => [/* 'acl'*/],
	]); //link counter with user

	$router->POST('counter/getdata', ['uses' => 'TicketController@counterdata', 'middleware' => ['acl'],
	]); //ticket verification.

	$router->POST('master/data/status', ['uses' => 'DataController@statusChange', 'middleware' => ['acl'],
	]); //foreign key management

	$router->POST('file/upload', ['uses' => 'FileController@fileUpload', 'middleware' => [/* 'acl'*/],
	]); //foreign key management

	$router->POST('file/create', ['uses' => 'FileController@create',
	]); //foreign key management

	$router->POST('constant/create', ['uses' => 'ConstantController@create',
	]); //create constants

	$router->POST('constant/edit', ['uses' => 'ConstantController@edit',
	]); //edit constants

	$router->POST('constant/delete', ['uses' => 'ConstantController@delete',
	]); //delete constants

	$router->POST('constant/list', ['uses' => 'ConstantController@list',
	]); //list constants

	$router->POST('constant/viewscheduled', ['uses' => 'ConstantController@viewscheduled',
	]); //list constants

	$router->POST('price/create', ['uses' => 'PriceController@create',
	]); //create price

	$router->POST('price/edit', ['uses' => 'PriceController@edit',
	]); //edit price

	$router->POST('price/delete', ['uses' => 'PriceController@delete',
	]); //edit price

	$router->GET('ticketing/data/report', ['uses' => 'TicketController@report',
	]); //edit price

	$router->POST('ticketing/counterlist', [ //'middleware' =>[ 'acl'],
		'uses' => 'TicketController@getcounterbyuser',
	]); //get all counter list by userid

	$router->POST('ticketing/counterlistbyuser', [ //'middleware' =>[ 'acl'],
		'uses' => 'TicketController@getallcounterbyuser',
	]); //get all counter list by userid

	$router->POST('public/user/create', ['uses' => 'PublicUserController@register',
	]); //create public user

	$router->POST('public/user/login', ['uses' => 'PublicUserController@Login',
	]); //login public user

	$router->POST('public/user/login', ['uses' => 'PublicUserController@Login',
	]); //login public user

	$router->POST('public/user/logout', ['uses' => 'PublicUserController@Logout',
	]); //logout public user

	$router->POST('public/user/edit', ['uses' => 'PublicUserController@edit',
	]); //edit public user

	$router->POST('public/user/forget', ['uses' => 'PublicUserController@forget',
	]); //forget public user

	$router->POST('public/user/reset', ['uses' => 'PublicUserController@reset',
	]); //reset public user

	$router->POST('public/user/view', ['uses' => 'PublicUserController@view',
	]); //view public user

	$router->POST('public/makepayment', ['uses' => 'PublicUserController@makepayment',
	]); //view public user

	$router->POST('public/processpaytm', ['uses' => 'PublicUserController@processpaytm',
	]);

	$router->POST('public/user/destinations', ['uses' => 'PublicUserController@destinations',
	]); //view public user

	$router->POST('public/user/booking', ['uses' => 'PublicUserController@booking',
	]); //view public user

	$router->POST('acl/role/list', ['uses' => 'UserController@roleList', /*'middleware' =>[ 'acl']*/
	]); //Role list

	$router->POST('acl/role/permissionlist', /*'middleware' =>[ 'acl']*/
		['uses' => 'UserController@viewPermissionRole',
		]); //removePermssion

	$router->POST('acl/role/assignpermission', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@givePermission',
	]); //give permission permission

	$router->POST('acl/user/assignpermission', [/*'middleware' =>[ 'acl'],*/
		'uses' => 'UserController@assignPermission',
	]); //give permission permission

	$router->POST('acl/role/createpermission', ['middleware' => ['acl'],
		'uses' => 'UserController@createPermission',
	]); //createPermission

	$router->POST('acl/getcategories', [
		'uses' => 'UserController@listcategory',
	]); //get categories

	$router->POST('acl/role/searchpermission', ['middleware' => ['acl'],
		'uses' => 'UserController@searchpermission',
	]); //get categories

	$router->POST('message/send', ['middleware' => ['acl'],
		'uses' => 'MessageController@send',
	]); //send email and sms

	$router->POST('public/user/payments', ['middleware' => ['acl'],
		'uses' => 'PublicUserController@payments',
	]); //send sms

	$router->POST('public/user/createorupdateuer', [
		'uses' => 'PublicUserController@createorupdateuer',
	]); //send sms

	$router->POST('public/user/getpayments', ['middleware' => ['acl'],
		'uses' => 'PublicUserController@getPayments',
	]); //send sms

	$router->GET('zoho/{key}', ['uses' => 'ZohoController@index',
	]); //view public user

	$router->POST('summary/{key}', ['uses' => 'ZohoController@summary',
	]); //view public user

	$router->POST('summarytest/{key}', ['uses' => 'ZohoController@summarytest',
	]); //view public user

	$router->POST('getdepcollection', ['uses' => 'ZohoController@getdepcollection',
	]); //view public user

	$router->GET('collection/{key}', ['uses' => 'ZohoController@getcollection',
	]); //view public usergetcollection

	$router->GET('counterinfo/{key}', ['uses' => 'ZohoController@counterinfo',
	]); //view public user
	$router->GET('getsettings', ['uses' => 'ZohoController@getsettings',
	]); //view public user

});

/*Route::get('/debug-sentry', function () {
throw new Exception('My first Sentry error!');
});

//ticket no,time,user,attraction,totanumber
get('/debug-sentry', function () {
throw new Exception('My first Sentry error!');
});
 */
