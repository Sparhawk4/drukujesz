<?php

include_once('../../config/config.inc.php'); 
include_once('../../init.php'); 

//include_once('./controllers/admin/AdminImportmergeController.php');

//echo getCWD();  die;
//$controller = new AdminImportmergeController();
//$controller->init();
//include_once('../../modules/shopimporter/shopimporter.php');


error_reporting(E_ALL);
@ini_set('display_errors', 'on');


if (!Tools::getValue('data') || !Tools::getValue('apiFunc') || !Tools::getValue('ajax')){
	print_r($_GET);
	
	var_dump( (bool)Tools::getValue('data') ) ;


	echo 'oooooooo';
	die;
}
//$classname = Tools::getValue('classname'); 
$function  = Tools::getValue('apiFunc');
$data   = Tools::getValue('data');

//include_once('.'.DIRECTORY_SEPARATOR .'classes' .DIRECTORY_SEPARATOR . $classname.'.php');
include_once('importpsApi.php');
$importer = new ImportPsApi('');
if(! method_exists ( $importer , $function )){
	echo 'method not exist  ' . $function; 
	die;
}


$importer->$function(Tools::getValue('data'));
 
