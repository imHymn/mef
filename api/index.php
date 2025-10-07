<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Bramus\Router\Router;

$router = new Router();

// Users routes
require_once __DIR__ . '/controller/AccountController.php';
require_once __DIR__ . '/controller/ReusableController.php';
require_once __DIR__ . '/controller/PlannerController.php';
require_once __DIR__ . '/controller/DeliveryController.php';
require_once __DIR__ . '/controller/AssemblyController.php';
require_once __DIR__ . '/controller/QCController.php';
require_once __DIR__ . '/controller/RMController.php';
require_once __DIR__ . '/controller/StampingController.php';
require_once __DIR__ . '/controller/FGController.php';
require_once __DIR__ . '/controller/ExportController.php';
require_once __DIR__ . '/controller/FinishingController.php';
require_once __DIR__ . '/controller/AnnouncementController.php';
$userController = new AccountController();
$reusableController = new ReusableController();
$plannerController = new PlannerController();
$deliveryController = new DeliveryController();
$assemblyController = new AssemblyController();
$qcController = new QCController();
$rmController = new RMController();
$fgController = new FGController();
$exportController = new ExportController();
$finishingController = new FinishingController();
$stampingController = new StampingController();
$announcementController = new AnnouncementController();
// REUSABLE
$router->get('/reusable/getCustomerandModel', 'ReusableController@getCustomerandModel');
$router->post('/reusable/updateAccountMinimal', 'ReusableController@updateAccountMinimal');
$router->post('/reusable/reset_timein', 'ReusableController@reset_timein');

// ANNOUNCEMENT
$router->get('/getAnnouncement', 'AnnouncementController@getAnnouncement');
$router->post('/createAnnouncement', 'AnnouncementController@createAnnouncement');
$router->post('/updateAnnouncement', 'AnnouncementController@updateAnnouncement');
$router->post('/deleteAnnouncement', 'AnnouncementController@deleteAnnouncement');

// ACCOUNTS
$router->post('/login', 'AccountController@login');
$router->post('/register', 'AccountController@register');

$router->get('/accounts/resetUsers', 'AccountController@resetUsers');
$router->get('/accounts/getAccounts', 'AccountController@getAccounts');
$router->get('/accounts/getAssemblyAccounts', 'AccountController@getAssemblyAccounts');
$router->get('/accounts/getQCAccounts', 'AccountController@getQCAccounts');
$router->get('/accounts/getStampingAccounts', 'AccountController@getStampingAccounts');
$router->get('/accounts/getFinishingAccounts', 'AccountController@getFinishingAccounts');
$router->get('/accounts/getPaintingAccounts', 'AccountController@getPaintingAccounts');

$router->get('/accounts/updateQRGenerated', 'AccountController@updateQRGenerated');
$router->post('/accounts/updateAccount', 'AccountController@updateAccount');
$router->post('/accounts/deleteAccount', 'AccountController@deleteAccount');
$router->post('/accounts/changePassword', 'AccountController@changePassword');

// PLANNER
$router->get('/planner/getMaterial', 'PlannerController@getMaterial');
$router->get('/planner/getFormHistory', 'PlannerController@getFormHistory');
$router->get('/planner/getComponents', 'PlannerController@getComponents');
$router->post('/planner/submitForm', 'PlannerController@submitForm');
$router->post('/planner/submitForm_allCustomer', 'PlannerController@submitForm_allCustomer');
$router->post('/planner/submitForm_specificCustomer', 'PlannerController@submitForm_specificCustomer');
$router->post('/planner/deleteMultipleForm', 'PlannerController@deleteMultipleForm');
$router->get('/planner/getPreviousLot', 'PlannerController@getPreviousLot');

// DELIVERY
$router->get('/delivery/getPendingDelivery', 'DeliveryController@getPendingDelivery');
$router->get('/delivery/getDeliveryHistory', 'DeliveryController@getDeliveryHistory');
$router->get('/delivery/getTruck', 'DeliveryController@getTruck');
$router->post('/delivery/assignTruck', 'DeliveryController@assignTruck');
$router->post('/delivery/sku_delivery', 'DeliveryController@sku_delivery');
$router->post('/delivery/component_delivery', 'DeliveryController@component_delivery');

// ASSEMBLY
$router->get('/assembly/getAllAssemblyData', 'AssemblyController@getAllAssemblyData');
$router->get('/assembly/getData_toassign', 'AssemblyController@getData_toassign');
$router->get('/assembly/getAllData_assigned', 'AssemblyController@getAllData_assigned');
$router->post('/assembly/getAllModelData_assigned', 'AssemblyController@getAllModelData_assigned');

$router->post('/assembly/getSpecificData_assigned', 'AssemblyController@getSpecificData_assigned');
$router->post('/assembly/assignOperator', 'AssemblyController@assignOperator');
$router->post('/assembly/getMaterialComponent', 'AssemblyController@getMaterialComponent');
$router->post('/assembly/timeinOperator', 'AssemblyController@timeinOperator');
$router->post('/assembly/timeoutOperator', 'AssemblyController@timeoutOperator');

// QC
$router->get('/qc/getTodoList', 'QCController@getTodoList');
$router->get('/qc/getRework', 'QCController@getRework');
$router->get('/qc/getAllQCData', 'QCController@getAllQCData');


$router->post('/qc/timeinOperator', 'QCController@timeinOperator');
$router->post('/qc/timeoutOperator', 'QCController@timeoutOperator');
$router->post('/qc/timein_reworkOperator', 'QCController@timein_reworkOperator');
$router->post('/qc/timeout_reworkOperator', 'QCController@timeout_reworkOperator');


// RM WAREHOUSE
$router->get('/rm/getIssuedComponents', 'RMController@getIssuedComponents');
$router->get('/rm/getIssuedHistory', 'RMController@getIssuedHistory');
$router->post('/rm/getRMStocks', 'RMController@getRMStocks');
$router->post('/rm/issueRM', 'RMController@issueRM');
$router->post('/rm/deleteIssued', 'RMController@deleteIssued');


// STAMPING
$router->get('/stamping/getMachines', 'StampingController@getMachines');
$router->get('/stamping/getData_toassign', 'StampingController@getData_toassign');
$router->get('/stamping/getAllData_assigned', 'StampingController@getAllData_assigned');
$router->get('/stamping/getComponentInventory', 'StampingController@getComponentInventory');
$router->get('/stamping/getAllStampingData', 'StampingController@getAllStampingData');

$router->post('/stamping/getAllModelData_assigned', 'StampingController@getAllModelData_assigned');
$router->post('/stamping/getSpecificData_assigned', 'StampingController@getSpecificData_assigned');
$router->post('/stamping/assignOperator', 'StampingController@assignOperator');
$router->post('/stamping/getComponentStatus', 'StampingController@getComponentStatus');
$router->post('/stamping/timeinOperator', 'StampingController@timeinOperator');
$router->post('/stamping/timeoutOperator', 'StampingController@timeoutOperator');

// FINISHING
$router->post('/finishing/getSpecificData_assigned', 'FinishingController@getSpecificData_assigned');
$router->post('/finishing/assignOperator', 'FinishingController@assignOperator');
$router->post('/finishing/getAllModelData_assigned', 'FinishingController@getAllModelData_assigned');
$router->get('/finishing/getAllData_assigned', 'FinishingController@getAllData_assigned');
$router->get('/finishing/getData_toassign', 'FinishingController@getData_toassign');
$router->post('/finishing/timeinOperator', 'FinishingController@timeinOperator');
$router->post('/finishing/timeoutOperator', 'FinishingController@timeoutOperator');

// FG WAREHOUSE
$router->get('/fg/getReadyforPullOut', 'FGController@getReadyforPullOut');
$router->post('/fg/PullOut', 'FGController@PullOut');
$router->get('/fg/getAllComponents', 'FGController@getAllComponents');
$router->get('/fg/getPulledoutHistory', 'FGController@getPulledoutHistory');

// Export Excel
$router->post('/export/exportQCExcel', 'ExportController@exportQCExcel');
$router->post('/export/exportMPEFF', 'ExportController@exportMPEFF');


$router->run();
