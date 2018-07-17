<?php
class AdminSubmenu1Controller extends ModuleAdminController {


	public $shopManufacturers = array();  

	public function __construct()

	{
		$this->bootstrap = true;
		$this->display = 'view';
		$this->meta_title = $this->l('tytuł');
		
		$this->base_tpl_view = 'view';
		parent::__construct();
		
		//$this->content = 'blablabla';
		//echo _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/';
		
//		echo $this->getTemplatePath();

//$this->base_tpl_view = 'view.tpl';

//		$this->lang = (!isset($this->context->cookie) || !is_object($this->context->cookie)) ? intval(Configuration::get('PS_LANG_DEFAULT')) : intval($this->context->cookie->id_lang);

		//parent::__construct();

	}

/*
	public function initContent(){
		parent::initContent();
		
	}
*/	
	
	public function postProcess()
	{
		
		if (Tools::isSubmit('submitImportClass')){
			// $this->displayForm();
			$this->beforStartPage();
		}else{
			$this->base_tpl_view = 'view.tpl';
			$this->tpl_view_vars = array(
					'token' => $this->token,
					'currentIndex' => self::$currentIndex,
			);
		}
		
		parent::postProcess();		
	}		
	

	private function beforStartPage(){
		$parserClass = Tools::getValue('submitImportClass');
		if ( !( $moduleName = Module::getModuleNameFromClass(__CLASS__) )){
			$this->_errors[] =Tools::displayError($this->l('Not set module for '.__CLASS__));
			return;
		}
		
		include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$parserClass.'.php');
		$parser = new $parserClass( $this->token);
		$parser->parse();
		$parser->getShopManufacturers();
		$newM =  $parser->getNewManufacturers();
		
		$catToDisplay = $parser->displaySubTree($parser->categoriesTree);
		$prodTodisplay = $parser->displayProduct();
		$scriptStr ='
			<input name="submitImportClass" type="submit" value="Turystyka" class="button" onclick="importPs();" />
			<hr style="width:100%;">
				<div id="info_id">
					</div>
						<div id="errorinfo_id" style="color: red;">
					</div>		
					<hr style="width:100%;">
			<script type="text/javascript" src="../modules/importdrukujesz24/importps.js"></script>
			<script type="text/javascript">
			var globalAjaxToken = "'.sha1(_COOKIE_KEY_.'importps').'";
			var classname = "'. get_class($parser) .'";'.
			'var provision  = '.Configuration::get('IMPORTDRUKUJESZ24_PROVISION').';'.
					'var categories  = ' . json_encode($parser->categories).';'.
					'var categoriesTree  = ' . json_encode($parser->categoriesTree).';'.
					'var manufacturers  = ' . json_encode($newM).';'.
					'var products = '. json_encode($parser->products).';'.
					'var attributesGroups = '.json_encode($parser->attributesGroup).';'.
					'var printerManufactuers = '.json_encode($parser->printerManufactuers).';'.
					'var featuresGroup = '.json_encode($parser->featuresGroup).';'.
					'</script>';
		
		
		
		//print_r($newM);
		
		$this->tpl_view_vars = array(
				'shopmanufactuers' => $parser->shopManufacturers,
				'newM' =>$newM,
				'catToDisplay'=>$catToDisplay,
				'prodTodisplay'=>$prodTodisplay,
				'manufacturers' => $parser->manufacturers,
				'jsscript' =>$scriptStr
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
