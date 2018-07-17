<?php
class AdminCsvimportController extends ModuleAdminController {

	public $shopManufacturers = array();  

	public function __construct()

	{
		
		//$this->bootstrap = true;
		$this->display = 'view';
		$this->meta_title = $this->l('import csv');
		$this->base_tpl_view = 'view';
		parent::__construct();
		
	}		
	public function postProcess()
	{
		
				
		if ( !( $moduleName = Module::getModuleNameFromClass(__CLASS__) )){
				$this->_errors[] =Tools::displayError($this->l('Not set module for '.__CLASS__));
				return;
			}	
			
		$path_import  =  _PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'csv'.DIRECTORY_SEPARATOR;
		$path_import = _PS_ADMIN_DIR_.'/import/'.date('YmdHis').'-';			
		$path = $path_import;
			//print_r($_FILES['file']);
			//move_uploaded_file($_FILES['file']['tmp_name'], $path.$_FILES['file']['name']);
		
		if (Tools::isSubmit('submitFileUpload'))
		{
			$this->base_tpl_view = 'content.tpl';	
			$this->tpl_view_vars = array();
			if (isset($_FILES['file']) && !empty($_FILES['file']['error']))
			{
				switch ($_FILES['file']['error'])
				{
					case UPLOAD_ERR_INI_SIZE:
						$this->errors[] = Tools::displayError('The uploaded file exceeds the upload_max_filesize directive in php.ini. If your server configuration allows it, you may add a directive in your .htaccess.');
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$this->errors[] = Tools::displayError('The uploaded file exceeds the post_max_size directive in php.ini.
							If your server configuration allows it, you may add a directive in your .htaccess, for example:')
						.'<br/><a href="'.$this->context->link->getAdminLink('AdminMeta').'" >
						<code>php_value post_max_size 20M</code> '.
						Tools::displayError('(click to open "Generators" page)').'</a>';
						break;
					break;
					case UPLOAD_ERR_PARTIAL:
						$this->errors[] = Tools::displayError('The uploaded file was only partially uploaded.');
						break;
					break;
					case UPLOAD_ERR_NO_FILE:
						$this->errors[] = Tools::displayError('No file was uploaded.');
						break;
					break;
				}
			}
			elseif (!preg_match('/.*\.csv$/i', $_FILES['file']['name']))
				$this->errors[] = Tools::displayError('The extension of your file should be .csv.');
			elseif (!file_exists($_FILES['file']['tmp_name']) ||
				!@move_uploaded_file($_FILES['file']['tmp_name'], $path.$_FILES['file']['name']))
				$this->errors[] = $this->l('An error occurred while uploading / copying the file.');
			else
			{
				@chmod($path.$_FILES['file']['name'], 0664);
				//echo $path.$_FILES['file']['name'];
				//die;
				//Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token').'&conf=18');
			}
			if(!$this->errors){
				$lines = array();
				$fname = $path.$_FILES['file']['name'];
				ini_set("auto_detect_line_endings", true);				
				$lines  = file($fname);
				include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'PolcanCSV.php');				
				$parser = new PolcanCSV($this->token);				
				$rowCount = PolcanCSV::$rowCount;
				setlocale(LC_CTYPE, 'pl_PL.UTF-8');
				foreach($lines as $key => $line){
					$lines[$key] =  str_getcsv (  iconv( 'CP1250', 'UTF-8' ,  $line) , ';');	// ISO-8859-1 // Windows-1252
					if($rowCount != count($lines[$key]) ){
							echo '<pre>' . print_r($lines , true) . '</pre>';
							$this->errors[] = $this->l('Błąd parsowania ilośc kolumn <> '. $rowCount);
							break;						
					}					
				}
				if(!$this->errors){
					$parser->csvArray= $lines;
					
					//print_r($cat);
					
					
					$parser->parse();
					$parser->getShopManufacturers();
					if(!empty($parser->errorMsg)){
						$this->errors[] = $parser->errorMsg;						
					}					
				}
				if(!$this->errors){
					$newM = $parser->getNewManufacturers();					
					$this->tpl_view_vars['prodTodisplay'] = $parser->displayProduct();
					$this->tpl_view_vars['classname'] = get_class($parser);
					$this->tpl_view_vars['products'] =  json_encode($parser->products);
					$this->tpl_view_vars['globalAjaxToken'] =  sha1(_COOKIE_KEY_.'importps');
					$this->tpl_view_vars['shopmanufactuers'] = $parser->shopManufacturers;
					$this->tpl_view_vars['manufacturers'] = $parser->manufacturers;
					$this->tpl_view_vars['newM'] = $newM;
					$this->tpl_view_vars['jsAttributesGroups'] = json_encode($parser->attributesGroup);
					$this->tpl_view_vars['jsmanufacturers'] = json_encode($newM);					
					
					$this->tpl_view_vars['provision'] = Configuration::get('IMPORTDRUKUJESZ24_PROVISION');
				}	
				//$this->tpl_view_vars['prestaProductsMap'] = json_encode($parser->prestaProductsMap);				
				//
							//echo '<pre>' . print_r($lines , true) . '</pre>';
						
				
			}
			$this->tpl_view_vars['errorCount'] = count($this->errors);

		}else{			
			
			$this->base_tpl_view = 'view.tpl';
			$this->tpl_view_vars = array(
					'token' => $this->token,
					'currentIndex' => self::$currentIndex,
					'path_import' =>$path_import 
			);
		}		
		parent::postProcess();		
	}		
	
	
	
	
	

	private function beforStartPage(){

		if ( !( $moduleName = Module::getModuleNameFromClass(__CLASS__) )){
			$this->_errors[] =Tools::displayError($this->l('Not set module for '.__CLASS__));
			return;
		}		
		$this->tpl_view_vars = array(			
				'path_import' => _PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'csv'.DIRECTORY_SEPARATOR
				);
		$this->base_tpl_view = 'content.tpl';		
		//$this->tpl_view_vars['shopmanufactuers'] = $s;		
	}


	
	
	

	public function getShopManufacturers(){
		$this->shopManufacturers = array();
		foreach(Manufacturer::getManufacturers(false, 0, false) as $man){
			$this->shopManufacturers[$man['name']] = $man;		
		}
		return $this->shopManufacturers;		
	}	


/*	
	public function display(){
	echo 'display0';
		parent::display();
		
		echo 'display';
		

	}
*/
/*	public function renderList() {

		//$supplierArray = $this->getSuppliers();

		return $this->context->smarty->fetch(dirname(__FILE__).'/../../views/templates/admin/initial.tpl');

	}
*/
	private function getSuppliers() {

		return Supplier::getSuppliers();

	}
	
	public function setMedia()
	{		
		return parent::setMedia();
	}
	
	public function renderView(){
		return parent::renderView();				
	}
	
	public function displayForm()
    {
	
    global $currentIndex;
 
    $defaultLanguage = intval(Configuration::get('PS_LANG_DEFAULT'));
    $languages = Language::getLanguages();
	echo'
	<b>Import: </b>
	<hr style="width:100%;">
	<form action="'.$currentIndex.'&token='.$this->token.'" method="post" id="import_form" name="import_form">
		<input type="hidden" name="csv" value="'.Tools::getValue('csv').'" />		
		<div style="text-align:left; margin-top:10px;">
			<!--<input name="submitImportClass" type="submit" value="'.'Yournewstyle'.'" class="button" />			-->
			<input name="submitImportClass" type="submit" value="'.'Sport'.'" class="button" />			
		</div>
	</form>
	<hr style="width:100%;">';
    }	

}
?>
