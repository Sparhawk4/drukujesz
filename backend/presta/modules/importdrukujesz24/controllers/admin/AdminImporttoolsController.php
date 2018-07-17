<?php
class AdminImporttoolsController extends ModuleAdminController {

	public $shopManufacturers = array();  

	public function __construct()

	{
		//var_dump(Configuration::get('PS_SSL_ENABLED'));  die;
		//$this->bootstrap = true;
		$this->ssl = (boolean)Configuration::get('PS_SSL_ENABLED');
		$this->display = 'view';
		$this->meta_title = $this->l('Narzędzia');
		$this->base_tpl_view = 'view';
		parent::__construct();
		
	}		
	public function postProcess()
	{
		
		
		if ( !( $moduleName = Module::getModuleNameFromClass(__CLASS__) )){
				$this->_errors[] =Tools::displayError($this->l('Not set module for '.__CLASS__));
				return;
			}	
		
		if (Tools::isSubmit('deleteExcel')){
			$db = Db:: getInstance();				
			$sql = 'SELECT * FROM `ps_importerps_product_map` WHERE `classname` = \'ExcelParser\''; 
			$ret =  $db->executeS($sql);
			foreach($ret as $row){
				$product  = new Product($row['id_product']);
				$product->delete();
				$sql =  'DELETE FROM `ps_importerps_product_map` WHERE `classname` = \''.pSQL($row['classname']).'\' and `id_product` = \''.pSQL($row['id_product']).'\'';
				$db->execute($sql);
				
				$sql =  'DELETE FROM `ps_importerps_parser_offer` WHERE `classname` = \''.pSQL($row['classname']).'\' and `id_xml` = \''.pSQL($row['id_xml']).'\'';				
				$db->execute($sql);
				//echo $sql;
				//break ;				
			}	
			//var_dump($ret);
			echo count($ret); die;
		}
		
				
		if (Tools::isSubmit('submitImportPisaki')){
			
			$this->base_tpl_view = 'pisaki.tpl';	
			$this->tpl_view_vars = array();
			
			$lines  = json_decode(file_get_contents( _PS_MODULE_DIR_.'importdrukujesz24/pisaki.json' ) , true);
			//var_dump($lines);
			
			$eans = array();
			foreach($lines as  $index  => $line){
				$eans[(string)$line['EAN']] = $index;	
				if(strlen($lines[$index]['EAN']) > 13){
						$lines[$index]['EAN'] = substr($lines[$index]['EAN'] , 1 );							
				}														
				//$lines[$index]['EAN'] = (string)$line['EAN'];
			}

			$db = Db:: getInstance();				
			$sql = 'SELECT pp.`id_product` , pp.`ean13`  FROM `'._DB_PREFIX_.'product` AS pp
			WHERE pp.`ean13` in ( '.implode(',' , array_keys($eans)).')' ;			
			$ret =  $db->executeS($sql);	
			 
			foreach($ret as $row ){
				if(array_key_exists($row['ean13'] , $eans)){
					$lines[$eans[$row['ean13']]]['id_product']	 = $row['id_product'];					
				}				
			}
			
			//var_dump($ret );
			//var_dump($lines);  die;
			$producers = array();
			foreach($lines as $line ){
				$producers[$line['producer']	] = null;				
			}
			$manufacturers = array();
			foreach ($producers as $producer => $null ){
				if (  $manId = Manufacturer::getIdByName($producer) ){
					$producers[$producer]  = $manId;
				}else{
					$man = new Manufacturer(); 
					$man->name = $producer; 
					$man->active = 1;
					$man->add();
					$manId = $man->id;
					$producers[$producer]  = $manId;
				}								
			}			
			foreach($lines as $i => $line ){
				$lines[$i]['id_producer'] = $producers[$line['producer']];
			}
			
			
			// ketegorie 
			$id_lang = intval(Configuration::get('PS_LANG_DEFAULT'));			
			$cat =  Category::searchByNameAndParentCategoryId($id_lang , 'Artykuły piśmiennicze' , 2); 
			if(!$cat){
				$cat = new Category(); 
				$cat->name[$id_lang] = 'Artykuły piśmiennicze';
				$cat->active = 1;
				$cat->link_rewrite[$id_lang] = Tools::str2url($cat->name[$id_lang]);
				$cat->id_parent = 2 ; 				
				$cat->add();								
				$cat = array('id_category' =>$cat->id_category);
			}			
			// dodawanie kategorii produktów 
			$categories = array();
			foreach($lines as $line ){
				$categories[$line['category']] = null;				
			}						
			foreach($categories as $category => $null){
				$c =  Category::searchByNameAndParentCategoryId($id_lang , $category , $cat['id_category']); 
				if(!$c){
					$c = new Category(); 
					$c->name[$id_lang] = $category;
					$c->active = 1;
					$c->link_rewrite[$id_lang] = Tools::str2url($c->name[$id_lang]);
					$c->id_parent = $cat['id_category']; 				
					$c->add();				
					$categories[$category] = $c->id_category;
				}else{								
					$categories[$category] = $c['id_category'];
				}
			}
			foreach($lines as $i => $line ){
				$lines[$i]['id_category'] = $categories[$line['category']];
			}	
			//var_dump($cat);
			//var_dump($lines);
			//die;
			foreach($lines as $i => $line ){
				try{
				if(array_key_exists('id_product' ,$line )){
					// update price 
					$prod = new Product($line['id_product']);
					$prod->price = round($line['price']*0.61*1.15 , 2);
					$prod->name[$id_lang] =$line['scode'].' '.trim($line['name']);		
					$prod->link_rewrite[$id_lang] = Tools::str2url($prod->name[$id_lang]);
					$prod->wholesale_price = round($line['price']*0.61, 2);
					$prod->active = false;					
					$prod->save();
				}else{
					//add
											
						if(strlen($line['EAN']) > 13){
							$line['EAN'] = substr($line['EAN'] , 1 );							
						}
						
						if(strlen($line['name']) > 128){
							continue;
							
						}
						
						
					$prod = new Product();
					$prod->name[$id_lang] =$line['scode'].' '.trim($line['name']);		
					$prod->link_rewrite[$id_lang] = Tools::str2url($prod->name[$id_lang]);
					$prod->id_manufacturer = $line['id_producer'];
					$prod->ean13 = $line['EAN'];
					$prod->id_category_default= $line['id_category'];
					$prod->price = round($line['price']*0.61*1.15 , 2);
					$prod->wholesale_price = round($line['price']*0.61, 2);
					$prod->active = false;
					$prod->add();
					

					
				}
				$prod->addToCategories($line['id_category']);
				}catch(Exception $e){
						var_dump($line);
						var_dump($prod->id_product);
						Throw $e;												
				}
				
			}
			
			
				
			
			$this->tpl_view_vars = array(
					'token' => $this->token,
					'currentIndex' => self::$currentIndex,
					'products' => json_encode($lines),
					'manufacturers' => json_encode($manufacturers)
			);
			
			var_dump($lines); die;
			
			parent::postProcess();
			return;
			
			
			echo 'submitImportPisaki';
			//echo getcwd();
			die;
		}
		
		
		
		if (Tools::isSubmit('submitGenerateAssociatedProducts'))
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
				foreach($lines as $key => $line){
					$lines[$key] =  str_getcsv (  iconv( 'ISO-8859-2', 'UTF-8' ,  $line) , ';');					
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
					$this->tpl_view_vars['prestaProductsMap'] = json_encode($parser->prestaProductsMap);
					$this->tpl_view_vars['provision'] = Configuration::get('IMPORTDRUKUJESZ24_PROVISION');
				}					
							//echo '<pre>' . print_r($lines , true) . '</pre>';
						
				
			}
			$this->tpl_view_vars['errorCount'] = count($this->errors);

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
