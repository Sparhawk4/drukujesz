<?php

include_once('../../config/config.inc.php');
include_once('../../init.php');
//include_once('../../modules/shopimporter/shopimporter.php');

error_reporting(E_ALL);
@ini_set('display_errors', 'on');

//print_r($_POST);
if (!Tools::getValue('data') || !Tools::getValue('apiFunc') ||  !Tools::getValue('classname') || !Tools::getValue('ajax') || Tools::getValue('token') != sha1(_COOKIE_KEY_.'importps')){
	print_r($_POST);

	echo 'oooooooo';
	die;
}
$classname = Tools::getValue('classname'); 
$function  = Tools::getValue('apiFunc');
$data   = Tools::getValue('data');


if( !file_exists('.'.DIRECTORY_SEPARATOR .'classes' .DIRECTORY_SEPARATOR . $classname.'.php')){
	die;
}
include_once('.'.DIRECTORY_SEPARATOR .'classes' .DIRECTORY_SEPARATOR . $classname.'.php');
include_once('importpsApi.php');
$importer = new ImportPsApi($classname);
if(! method_exists ( $importer , $function )){
	echo 'method not exist  ' . $function; 
	die;
}
/*print_r($_POST); die;// Tools::getValue('data')
echo json_decode(Tools::getValue('data'));*/

$importer->$function(Tools::getValue('data'));


//echo $classname.$function. $data;   
//echo json_encode('ok');

//function addManufactur()
