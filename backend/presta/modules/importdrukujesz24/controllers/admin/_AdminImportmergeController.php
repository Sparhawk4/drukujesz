<?php
class AdminImportmergeController extends ModuleAdminController {

	public $shopManufacturers = array();  
	public $parserProvision = 1.05;

	public function __construct()
	{		
		$this->display = 'view';
		$this->meta_title = $this->l('import');
		$this->base_tpl_view = 'view';
		parent::__construct();
		return;
				
		
		
		$p  = Configuration::get('IMPORTDRUKUJESZ24__EXCEL_PROV');
		if (!$p or $p < 1) {
			$this->parserProvision = 1.05;							
			$r = Configuration::updateValue('IMPORTDRUKUJESZ24__EXCEL_PROV' , $this->parserProvision);
		} 		
						
		//$this->bootstrap = true;
		$this->display = 'view';		
		$this->meta_title = $this->l('Import');
		
		$this->base_tpl_view = 'view';		
		
		parent::__construct();
		
		
		
		
	}		
	
	private function deleteOfferSource($class){
		$db  = Db::getInstance();
		$sql =  'DELETE FROM `'._DB_PREFIX_.'importerps_parser_offer` WHERE `classname` = \''.pSql($class).'\'';
		return $db->execute($sql);
		
	}
	
	private function getNewProducts(){
		$newProducts = array();
		$db  = Db::getInstance();
		$sql =  'SELECT  p.* , pm.`id_product`  , p.`json`  FROM `' ._DB_PREFIX_.'importerps_parser_offer`  AS  p   
				left  join '._DB_PREFIX_.'importerps_product_map as pm ON (pm.`classname` = p.`classname`  AND pm.`id_xml`=   p.`id_xml` )
				where pm.`id_product` IS NULL 
				order by p.classname , p.`id_xml` ' ;
			$newProducts =  $db->executeS($sql);
			foreach($newProducts as $k =>$p){
				$class = $p['classname']; 		
				$newProducts[$k]['sourceURL'] = $class::getURL($p['id_xml']);
				//$newProducts[$k]['xmlName'] = $class::getName($p['json']);
				//unset($newProducts[$k]['json']);				
			}
		return $newProducts;
	}
	
	private function getMegredProducts(){
		$idLang = 1;
		$id_shop = 1;
		$megredProducts = array();
		$db  = Db::getInstance();
		$sql =  'SELECT  p.`classname`, p.`id_xml`, p.`cena`, p.`quantity`  , pm.`id_product` , pl.`name` as  shopName , p.`json` FROM `' ._DB_PREFIX_.'importerps_parser_offer` AS  p   
			left  join '._DB_PREFIX_.'importerps_product_map as pm ON (pm.`classname` = p.`classname`  AND pm.`id_xml`=   p.`id_xml` )
			left  join '._DB_PREFIX_.'product_lang as pl ON (pl.`id_product` = pm.`id_product`  AND pl.`id_lang`=  '.$idLang.'  AND  `id_shop` = '.$id_shop.')
			where pm.`id_product` IS NOT NULL 			
			order by  pm.`id_product`  ,   p.cena  ASC';			
		$megredProducts =  $db->executeS($sql);		
		foreach($megredProducts as $k =>$p){
			$class = $p['classname']; 		
			$megredProducts[$k]['sourceURL'] = $class::getURL($p['id_xml']);
			//$megredProducts[$k]['xmlName'] = $class::getName($p['json']);
			//unset($megredProducts[$k]['json']);
			
		}		
		return $megredProducts; 
	}
	
	private function getMegredProductsMinimalPrice(){
		$megredProducts = array();
		$db  = Db::getInstance();
		$sql =  'SELECT  p.`classname`, p.`id_xml`, p.`cena`, p.`quantity`  , pm.`id_product`  , pl.`name` FROM `' ._DB_PREFIX_.'importerps_parser_offer` AS  p   
			left  join '._DB_PREFIX_.'importerps_product_map as pm ON (pm.`classname` = p.`classname`  AND pm.`id_xml`=   p.`id_xml` )
			left join '._DB_PREFIX_.'product_lang AS pl ON pl.id_product = pm.`id_product` 
			where pm.`id_product` IS NOT NULL 
			group by  pm.`id_product` 
			order by p.cena  ASC' ;
		$megredProducts =  $db->executeS($sql);							
		foreach($megredProducts as &$p){
			$n = $p['name'];
			unset($p['name']);
			$p['name'][1] = $n;			
			$p['wholesale_price'] = $p['cena'];
			unset($p['cena']);			
			$p['parserClass'] = $p['classname'];
			unset($p['classname']);	
			$p['xml_kod_produktu'] = $p['id_xml'];
			unset($p['id_xml']);
			$p['prestaId'] = $p['id_product'];
			unset($p['id_product']);
			
		}
		//echo '<pre>'.print_r($megredProducts, true).'</pre>';  die;
		return $megredProducts; 
		//echo '<pre>'.print_r($megredProducts, true).'</pre>';  die;
		
		return $megredProducts; 
		foreach($megredProducts as &$p){
			$class = $p['classname']; 		
			$p['sourceURL'] = $class::getURL($p['id_xml']);			
		}		
		echo '<pre>'.print_r($megredProducts, true).'</pre>';  die;
		return $megredProducts; 
	}
	
	
	
	/*
	 [classname] => PolcanCSV
     [id_xml] => 23274
     [cena] => 115.11
     [quantity] => 15
     [json] => ["6509B008","Canon","Materia\u0142y eksploatacyjne do drukarek atramentowych","Tusz Canon CLI-551 CMYK MultiPack iP7250\/MG5450\/MG6350","115.11",15,"23274"]
     [id_product] => 
     [sourceURL] => http://esklep.polcan.pl/pozycja_katalogowa.php?poz=23274
	*/
	
	private function mergeNewProducts($newProducts){
		$db  = Db::getInstance();		
		$parserProductGroups = array();
		foreach($newProducts as $product){
			$parserProductGroups[$product['classname']][] = $product;
		}
		
		foreach($parserProductGroups as $parser => $parserGroup){			
			$parser::findCompatybileProducts($parserGroup);
				
			
		}		
	}
	
	/*
	 * wczytywanie nowych produktów po wczytaniu danych z jednego parsera konieczne jest ponowne połączenie nowych produktów bo mogą się już łączyć 
	 * 
	 */ 
	
	private function loadNewProduct($newProducts , &$productsToLoad , &$shopmanufactuers , &$manufacturers  , &$loadCalssName ){
		$db  = Db::getInstance();		
		$parserProductGroups = array();
		foreach($newProducts as $product){
			$parserProductGroups[$product['classname']][] = $product;
		}
		$jsonData = array();
		foreach($parserProductGroups as $parserClass => $parserGroup){						
			foreach($parserGroup as $row){
				$j = json_decode($row['json']);					
				$jsonData[] = $j;			
			}
			$parser = new $parserClass($this->token);
			$parser->csvArray= $jsonData;
			$parser->parse();
			$parser->getShopManufacturers();
			$loadCalssName = $parserClass;
			$productsToLoad = $parser->products;
			$shopmanufactuers = $parser->shopManufacturers;
			$manufacturers = $parser->manufacturers;
			
			
			return $jsonData;
			
			foreach($parserGroup as $row){
				$j = json_decode($row['json']);	
				$j['class'] = $parser;
				$jsonData[] = $j;			
			}
			
			
			$parser = new $parserClass($this->token);
			$parser->csvArray= $lines;
			$parser->parse();
			$parser->getShopManufacturers();
			
			
			
			break;	
			//Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token').'&conf=18');
		}
		
		
					
					//print_r($cat);
					
					
					
		
		
								
		return $jsonData;
	}
	
	
	
	
	public function postProcess()		
	{			
		
		$startTime = microtime(true);
		
		$productsToLoad = array();
		$shopmanufactuers = array(); 
		$manufacturers  = array();
		$loadCalssName = null;
		$jsonData = array();
		$db  = Db::getInstance();				
		$startTime = microtime(true);
		$tpl_dir = $this->module->getLocalPath().'/views/templates/admin/importmerge/helpers/view/';	
		$this->tpl_view_vars = array();
		
		$this->base_tpl_view = 'view.tpl';	
				
		$this->tpl_view_vars = array(
						'tpl_dir' =>$tpl_dir,
						'token' => $this->token,
						'currentIndex' => self::$currentIndex,
						'tpl_dir' =>$tpl_dir,
				);						
					
// ajax 				
		if($this->ajax){
			$function = Tools::getValue('apiFunc');
			if(!empty($function)){
				switch($function){
					case 'deleteOfferSource': 
						$data = Tools::getValue('data');
						$this->deleteOfferSource($data);
						$this->tpl_view_vars['curentOferSources'] =  $this->getCurentOfferSources();
						$this->base_tpl_view = 'offerSources.tpl';							
						$ret['offerSources'] = $this->renderView();
						echo json_encode($ret);
						
						
						break;
					default: 
						echo 'nieznana funkcja';
						
										
				}
				die;
				
				
				echo $function.$data;	
				
			}			
			return;
		}
		
		
		if ( !( $moduleName = Module::getModuleNameFromClass(__CLASS__) )){
				$this->_errors[] =Tools::displayError($this->l('Not set module for '.__CLASS__));
				return;
			}	
			
		$path_import = _PS_ADMIN_DIR_.'/import/'.date('YmdHis').'-';			
		$path = $path_import;

		

	
		if (Tools::isSubmit('regenerate')){
			//$this->reg();
		}
		
		if (Tools::isSubmit('mergeNewProducts')){
			$curentOferSources  = $this->getCurentOfferSources();		
				foreach($curentOferSources as $row)
					include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$row['classname'].'.php');			
			
			$newProducts = $this->getNewProducts();
			$this->mergeNewProducts($newProducts);	
			$newProducts = $this->getNewProducts();		
			//echo '<pre>'.print_r($newProducts, true).'</pre>'; 
		}		
		
		if (Tools::isSubmit('deleteOfferSource')){
			$className = Tools::getValue('className');
			switch($className){
				case 'PolcanCSV': 						
					$this->deleteOfferSource($className);
					break; 
				case 'ExcelParser':
					$this->deleteOfferSource($className);
					break; 					
			}		
		}

		if (Tools::isSubmit('submitFileUpload')){
			$className  = Tools::getValue('class'); 
			if(!$className){
				$this->errors[] = Tools::displayError('Błąd formularza');
			}
			if(!$this->errors){
				$sql = 'SELECT * FROM `'._DB_PREFIX_.'importerps_parsers` WHERE `classname` = \''.pSql($className).'\'';			
				$parserClass =  $db->executeS($sql);		
				if(!$parserClass){
					$this->errors[] = Tools::displayError('Nieobsługiwany parser');
				}else{
					// provizja 
					$provision = (float)Tools::getValue('provision');
					if(!$provision or !is_float($provision) or $provision<=1){
						$this->errors[] = Tools::displayError('Błędna prowizja');
					}else{
						$sql =  'UPDATE  `' ._DB_PREFIX_.'importerps_parsers` SET  `provision`  = '.$provision.' WHERE   `classname`=  \''.$className.'\'' ;
						$res =  $db->execute($sql);						
					}			
				}								
			}
			if(!$this->errors){
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
				
			}
			if(!$this->errors){
				switch ($className){
					case 'PolcanCSV': 
						$regexPattern  = '/.*\.csv$/i';
						$regexPatternErr = 'The extension of your file should be .csv.';
					break; 
					case 'ExcelParser': 					
						$regexPattern  = '/.*\.xlsx$/i';
						$regexPatternErr = 'Rosszerzenie pliku nie powinno być xlsx';					
					break; 					
				}									
				if(!preg_match($regexPattern, $_FILES['file']['name']))
					$this->errors[] = Tools::displayError($regexPatternErr);				
				elseif (!file_exists($_FILES['file']['tmp_name']) ||!@move_uploaded_file($_FILES['file']['tmp_name'], $path.$_FILES['file']['name']))
					$this->errors[] = $this->l('An error occurred while uploading / copying the file.');	
				else{
					@chmod($path.$_FILES['file']['name'], 0664);				
				}	
					
			}
			if(!$this->errors){						
				$fname = $path.$_FILES['file']['name'];						
				$sql =  'UPDATE  `' ._DB_PREFIX_.'importerps_parsers` SET  `filename`  = \''.pSql(pathinfo($fname , PATHINFO_BASENAME) ).'\' WHERE   `classname`=  \''.$className.'\'' ;
				$res =  $db->execute($sql);				
								
				$lines = array();								
				switch ($className){
					case 'PolcanCSV': 
												
						ini_set("auto_detect_line_endings", true);				
						$lines  = file($fname);
						
						setlocale(LC_CTYPE, 'pl_PL.UTF-8');
						$rowCount = 7;		
						foreach($lines as $key => $line){							
							$icv  = @iconv( 'CP1250', 'UTF-8' ,  $line);
							if($icv === false  or $icv =='' or $icv == null){
								$this->errors[] = $this->l('Kodowanie znaków niezgodne z przyjetym formatem 1250  linia:'. ($key+1) . ' '. $line);								
								unset($lines[$key]);
								continue; 
							}							
							$lines[$key] =  str_getcsv ( $icv  , ';');	// ISO-8859-1 // Windows-1252
																					
							if($rowCount != count($lines[$key]) ){									
									//echo 'linia: '.$key.'<pre>' . print_r($lines , true) . '</pre>';									
									$this->errors[] = $this->l('Błąd parsowania ilośc kolumn <> '. $rowCount .' '.$icv.' '. print_r($lines[$key] , true).' '.$line);
									unset($lines[$key]);
									continue ;						
							}					
							
						}									
						// usunięcie tych z zerową ilością 					
						foreach($lines as $key => $line){
							$lines[$key][5] = intval($lines[$key][5]);
							if($lines[$key][5] <= 0)							
								;//unset($lines[$key]);
							else{
								$lines[$key][5] = min(40 , $lines[$key][5]);
							}	
						}					
						//echo '<br>line:'.count($lines);
						break;
					case 'ExcelParser':							
					
						require_once '../modules/'.$moduleName.'/lib/phpExcel/Classes/PHPExcel/IOFactory.php';
						$objPHPExcel = PHPExcel_IOFactory::load($path.$_FILES['file']['name']);
						foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
							//$worksheetTitle     = $worksheet->getTitle();
							$highestRow         = $worksheet->getHighestRow(); // e.g. 10
							//$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
							//$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
							//$nrColumns = ord($highestColumn) - 64;

							for ($row = 2; $row <= $highestRow; ++ $row) {
								$r = array();						
								$cell = $worksheet->getCellByColumnAndRow(0, $row);
								$r[0] = $cell->getValue();						
								
								$r[1] = 'Croton';
								$r[2] = null;
								$cell = $worksheet->getCellByColumnAndRow(2, $row);
								$r[3] = $cell->getValue();
								
								$cell = $worksheet->getCellByColumnAndRow(3, $row);  // cena czase, 0 
								$r[4] = $cell->getValue();
								if($r[4] <=0){
									continue;
									echo $row. ' '.  $r[4];
									die;
								}
								$r[5] = 40;
								$r[6] = $r[0];
								$lines[] = $r;

							}
							
						}				
						break;	

					
				}
				
				
				$values = '';				
				foreach($lines as $line){													
						$values .= '(\''.$className.'\',\''. pSql($line[6]) .'\','. $line[4] .','.$line[5].  ',\''.pSql(json_encode($line)) .'\'),' ;
				}			
				$values = rtrim( $values , ',');	
				try{
				$sql =  'DELETE FROM `'._DB_PREFIX_.'importerps_parser_offer` WHERE `classname` = \''.$className.'\'';
				$res =  $db->execute($sql);
					
				$sql =  'INSERT INTO `'._DB_PREFIX_.'importerps_parser_offer` (`classname`, `id_xml`, `cena`, `quantity` , `json`) VALUES '.$values;				
				$res =  $db->execute($sql);
				}catch(Exception $e){		
					
					
					
					echo $e->getMessage();	
					die;							
					$valuesErrCheck  = array();
					foreach($lines as $line){							
						$valuesErrCheck[] = '\''. pSql($line[6]) .'\'';
					}
					//$sqlErrCheck = 'SELECT * FROM `ps_importerps_product_map` WHERE `classname` = \''.pSql($className).'\'  and `id_xml` in ('.implode(',',$valuesErrCheck).')';
					//$resErr =  $db->executeS($sqlErrCheck);
					//print_r($resErr);
					echo $e->getMessage();
					echo  $values;
					
					print_r($db->getMsgError());

					die;
					
					
											print_r($lines);
						//die;
						
						$i = 0; 
						foreach($lines as $line){							
								//echo '<br>'.$line[6].';'.$line[5];
									$i++;
								//echo '<br>'.print_r($line, true);
								//echo '<br>'.$line[6];
						}
						echo '<br>'.$i;
						echo '<br>'.count($lines);
												
							die;																													
												
						
					
				}
								

			}
			
							
			
/*
			if(!$this->errors){												
				Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token').'&conf=18');
			}	
			*/ 
		}
		
		if (Tools::isSubmit('loadNewProduct')){
			$curentOferSources  = $this->getCurentOfferSources();		
				foreach($curentOferSources as $row)
					include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$row['classname'].'.php');						
			$newProducts = $this->getNewProducts();
			$this->mergeNewProducts($newProducts);	
			$newProducts = $this->getNewProducts();
										
			if(empty($newProducts))
				Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token'));
						
						
			$parserProductGroups = array();			
			foreach($newProducts as $product){
				$parserProductGroups[$product['classname']][] = $product;
			}
			$parserGroup = reset($parserProductGroups); 
			$parserClass  = key($parserProductGroups); 
		
			
			$jsonData = array();
			foreach($parserGroup as $row){
				$j = json_decode($row['json']);									
				$jsonData[] = $j;			
			}
			
			$parser = new $parserClass($this->token);
			$parser->csvArray= $jsonData;			
			$parser->parse();
			foreach ($parser->products as &$p){
				$p['parserClass'] = $parserClass;
			}	
			
			$parser->getShopManufacturers();
			$newM = $parser->getNewManufacturers();

			$sql =  'SELECT `provision` FROM  `'._DB_PREFIX_.'importerps_parsers` WHERE `classname`  = \''.pSql($parserClass).'\'';
			$res =  $db->executeS($sql);			
			$provision[$parserClass] = $res[0]['provision'] ;			
			//echo '<pre>'.print_r($parserClass, true).'</pre>'; 
			//echo '<pre>'.print_r($parserGroup, true).'</pre>'; 
			
			//echo '<pre>'.print_r($parser->manufacturers, true).'</pre>'; die;		
					
			//$jsonData = $this->loadNewProduct($newProducts , $productsToLoad ,  $shopmanufactuers , $manufacturers  , $loadCalssName);					

			$this->base_tpl_view = 'content.tpl';		
						
			$this->tpl_view_vars = array_merge($this->tpl_view_vars ,  array(
				'errorCount' => count($this->errors),
				
				'prodTodisplay' => $parser->displayProduct(),
				'classname' => $parserClass,
				'products' =>  json_encode($parser->products),
				'globalAjaxToken' =>  sha1(_COOKIE_KEY_.'importps'),
				'shopmanufactuers' => $parser->shopManufacturers,							
				'manufacturers' => $parser->manufacturers,
				'newM' => $newM,	
				'jsAttributesGroups' => json_encode($parser->attributesGroup),
				'jsmanufacturers'  => json_encode($newM),					
				'provision' => json_encode($provision),
					
				'afteRready'=>  self::$currentIndex.'&token='.$this->token									
			));						
		}
		if (Tools::isSubmit('loadToPresta')){  //c500s2yg
			$curentOferSources  = $this->getCurentOfferSources();					
			foreach($curentOferSources as $row){
				include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$row['classname'].'.php');								
			}	
			$productSuplier =  $this->getMegredProducts();	
			$supliers = array();
			foreach($productSuplier as &$p){
				unset($p['quantity']);			
				unset($p['sourceURL']);
				$s = $p['classname']; 
				unset($p['classname']);
				$supliers[$s][]  = array($p['id_product'],$p['id_xml'],$p['cena']);
				unset($p);
			}
									
			$products  = $this->getMegredProductsMinimalPrice();
			$defaultSupliers = array();	
			foreach($products as $p){
				$defaultSupliers[$p['parserClass']][] = $p['prestaId'];				
			}
			
						
//$products  =  array_slice ( $products , 0 , 100);
			$provision = array();
			foreach($curentOferSources as $row){
				$sql =  'SELECT `provision` FROM  `'._DB_PREFIX_.'importerps_parsers` WHERE `classname`  = \''.pSql($row['classname']).'\'';
				$res =  $db->executeS($sql);			
				$provision[$row['classname']] = $res[0]['provision'] ;							
			}			
			$this->base_tpl_view = 'content.tpl';	
			$this->tpl_view_vars = array_merge($this->tpl_view_vars ,  array(
				'defaultSupliers' => json_encode($defaultSupliers),
				'supliers' =>	json_encode($supliers),
				'prodTodisplay' => $this->productToDisplay($products),
				'classname' => 'abstractParser',
				'errorCount' => count($this->errors),				
				'products' =>  json_encode($products),
				'globalAjaxToken' =>  sha1(_COOKIE_KEY_.'importps'),				
				'provision' => json_encode($provision),
					
				//'afteRready'=>  self::$currentIndex.'&token='.$this->token							
			));						
			
		}
		
		if (Tools::isSubmit('changeXmlProductMap')){
			$this->changeXmlProductMap($moduleName);
			return;
		}		
		if (Tools::isSubmit('deleteXmlProductMap')){
			$this->deleteXmlProductMap($moduleName);
			return;
		}		
		

		
		if (! (Tools::isSubmit('loadNewProduct')  or Tools::isSubmit('loadToPresta') ) ){
			$megredProducts = array();				
			$curentOferSources  = $this->getCurentOfferSources();		
				foreach($curentOferSources as $row)
					include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$row['classname'].'.php');
			//$megredProducts =  $this->getMegredProducts();				
			//$newProducts = $this->getNewProducts();
			
			$megredProducts  = $this->getMegredProducts();		
			foreach($megredProducts as $k =>$p){
				$class = $p['classname']; 		
				$megredProducts[$k]['xmlName'] = $class::getName($p['json']);
				unset($megredProducts[$k]['json']);			
			}	
			$ret['megredProducts']  =  $megredProducts;
			$newProducts = $this->getNewProducts();
			foreach($newProducts as $k =>$p){
				$class = $p['classname']; 		
				$newProducts[$k]['xmlName'] = $class::getName($p['json']);
				unset($newProducts[$k]['json']);				
			}							
			
			
			
			$this->addCss(array('/modules/'.$this->module->name.'/css.css',));
			$this->addJS(array('/modules/'.$this->module->name.'/importmerge.js',));
			$this->addJS(array('/modules/'.$this->module->name.'/jquery.form.min.js',));
			$this->addJS(array('/modules/'.$this->module->name.'/isInViewport.min.js',));
			
					
			$this->tpl_view_vars = array_merge($this->tpl_view_vars ,  array(						

					'parsers' => $this->getParsers(),
					'newProducts' =>json_encode($newProducts), 
					'megredProducts'=>json_encode($megredProducts),				
					'curentOferSources' => $curentOferSources,
			));						
		}				
		parent::postProcess();		
		//echo '<br>'.(microtime(true)-$startTime);
	}		

	private function deleteXmlProductMap($moduleName){				
		$db  = Db::getInstance();
		$classname  = Tools::getValue('classname');
		$id_xml = Tools::getValue('id_xml');				
		$parsers = $this->getParsers();		
		if(!array_key_exists($classname , $parsers)){
			die('błędne żądanie');			
		}		
		foreach($parsers as $row)
			include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$row['classname'].'.php');		
		$sql  = 'DELETE FROM '._DB_PREFIX_.'importerps_product_map WHERE `classname` = \''.pSql($classname).'\' AND `id_xml` = \''.pSql($id_xml).'\'';
		$db->execute($sql);	
		$ret = array();		
		$megredProducts  = $this->getMegredProducts();		
		foreach($megredProducts as $k =>$p){
			$class = $p['classname']; 
			$megredProducts[$k]['xmlName'] = $class::getName($p['json']);
			unset($megredProducts[$k]['json']);			
		}	
		$ret['megredProducts']  =  $megredProducts;
		$newProducts = $this->getNewProducts();
		foreach($newProducts as $k =>$p){
				$class = $p['classname']; 		
				$newProducts[$k]['xmlName'] = $class::getName($p['json']);
				unset($newProducts[$k]['json']);				
		}				
		$ret['newProducts']  = $newProducts;		 		
		die(json_encode($ret));
		//echo json_encode($ret); 
		//return;
		die((memory_get_peak_usage(true)/(1024*1024)).json_encode($ret));
	}	
	
	private function changeXmlProductMap($moduleName){				
		$classname  = Tools::getValue('classname');
		$id_xml = Tools::getValue('id_xml');				
		$id_product = Tools::getValue('id_product');		
		if(empty($id_product)){
			die(json_encode(array('error'=>'Nie podano id_product')));
		}		
		$db  = Db::getInstance();
				
		$parsers = $this->getParsers();		
		if(!array_key_exists($classname , $parsers)){
			die('błędne żądanie');			
		}		
		foreach($parsers as $row)
			include_once(_PS_MODULE_DIR_.$moduleName.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$row['classname'].'.php');				
		$sql  = 'SELECT `id_product`  FROM '._DB_PREFIX_.'product WHERE `id_product` = \''.pSql($id_product).'\'';
		$res = $db->executeS($sql);	
		if(empty($res)){
			die(json_encode(array('error'=>'Produkt o podanym `id_product` : '.$id_product.' nie istgnieje')));			
		}
		$sql  = 'SELECT * FROM '._DB_PREFIX_.'importerps_product_map WHERE `classname` = \''.pSql($classname).'\' AND `id_xml` = \''.pSql($id_xml).'\'';
		$res = $db->executeS($sql);			
		if(empty($res)){ // INSERT 
			$sql  = 'INSERT INTO '._DB_PREFIX_.'importerps_product_map  (`classname`, `id_xml`, `id_product`) VALUES (  \''.pSql($classname).'\',\''.pSql($id_xml).'\',\''.pSql($id_product).'\')';	
			$res = $db->execute($sql);
		}else{ // update 
			$sql  = 'UPDATE '._DB_PREFIX_.'importerps_product_map SET `id_product`=\''.pSql($id_product).'\'   WHERE `classname` = \''.pSql($classname).'\' AND `id_xml` = \''.pSql($id_xml).'\'';	
			$res = $db->execute($sql);
		}
		$ret = array();				
		$megredProducts  = $this->getMegredProducts();		
		foreach($megredProducts as $k =>$p){
			$class = $p['classname']; 		
			$megredProducts[$k]['xmlName'] = $class::getName($p['json']);
			unset($megredProducts[$k]['json']);			
		}	
		$ret['megredProducts']  =  $megredProducts;
		$newProducts = $this->getNewProducts();
		foreach($newProducts as $k =>$p){
			$class = $p['classname']; 		
			$newProducts[$k]['xmlName'] = $class::getName($p['json']);
			unset($newProducts[$k]['json']);				
		}				
		$ret['newProducts']  = $newProducts;		 
		die(json_encode($ret));
		//echo json_encode($ret); 
		//return;
		die((memory_get_peak_usage(true)/(1024*1024)).json_encode($ret));
	}		
	
	

	private function productToDisplay($products){
	$sql =  'SELECT  pm.`id_xml`  ,   p.`price` as price
			FROM  `'._DB_PREFIX_.'product`  p , '._DB_PREFIX_.'importerps_product_map pm		
			WHERE  pm.`id_product` = p.`id_product`'; 
		$db= Db::getInstance(); 
		if(!( $result = $db->ExecuteS($sql))){
			if(strlen($db->getMsgError()) > 0){
				echo $db->getMsgError();
				echo 'die' . __LINE__ ;
				die;		
			}
		}
		$prodPriceMap = array(); 
		foreach( $result as $row){
			$prodPriceMap[$row['id_xml']] = $row['price'];
		}
		
		
		$ret = '<div>';
			$ret.= 'Produktów: '.count( $products); 
		$ret.='</div>';
		$ret.= '<div>';
			$ret.= '<script type="text/javascript">var prodPriceMap ='. json_encode($prodPriceMap).';</script>';
			$ret.= '<table  id="productsTableId" border="0" cellpadding="0" cellspacing="0" class="table">';
			
			$ret.='<tr>
					<th>'.'<div>Lp<br /> <br /></div>'.'</th>
					<th>'.'<div>Akt<br /> <br /><input type="checkbox" name="active" checked="checked"  onchange="onActiveChange(this)"> </div>'.'</th>
					<th>'.'<div>Kod<br /> <br /></div>'.'</th>
					<th>'.'<div>Nazwa<br /> <br /></div>'.'</th>
					<th>'.'<div>Cena<br />xml<br /></div>'.'</th>
					<th>'.'<div>Cena<br />z marżą<br /></div>'.'</th>
					<th>'.'<div>Cena<br />stara<br /></div>'.'</th>
					<th>'.'<div>Cena   <input name="Ok"  value="OK" type="button" onclick="onAdd()"><br />+<input id="add_price_id" style="text-align: right;" type="text" size="7"  maxlength = "7" value="0.00"></div>'.'</th>
				</tr>';

			$ret.= '</table>';
		$ret.= '</div>';
		//echo $ret; die;
		return $ret;
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
		parent::setMedia();
//		$this->addCss(array('/modules/'.$this->module->name.'/css.css',));
//		$this->addJS(array('/modules/'.$this->module->name.'/importmerge.js',));
//		$this->addJS(array('/modules/'.$this->module->name.'/jquery.form.min.js',));
		
		//$this->addJS(array('/js/jquery/plugins/ajaxfileupload/jquery.ajaxfileupload.js',));
		
		
	}
/*
	public function renderView(){
		$this->tpl_view_vars['parserProvision'] = $this->parserProvision;
		return parent::renderView();		
	}
*/	
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
    
    /*
     *  odczytuje wszystkie wgrane pliki i odtwarza powiązanie (parser , id_xml) -> id_product
     * 
     */ 
    
	private function reg(){
		//echo getcwd();
		$files = array();
		$path = getcwd().'/import/';
		$dir  = scandir($path);
		foreach($dir as $f){
			if(is_file($path.$f)){
				$pi = pathinfo( $path.$f ,  PATHINFO_EXTENSION	);
				 if($pi == 'csv'){
					 $files[]  = $path.$f;
				 }				
			}  			
		}				
		$rowCount = 7;		
		$liensMap = array();
		$x =0;
		foreach($files as $fname){
			$lines  = file($fname);
			foreach($lines as $key => $line){
				$lines[$key] =  str_getcsv (  iconv( 'CP1250', 'UTF-8' ,  $line) , ';');	// ISO-8859-1 // Windows-1252
				if($rowCount != count($lines[$key]) ){
						echo '<pre>' . print_r($lines , true) . '</pre>';
						$this->errors[] = $this->l('Błąd parsowania ilośc kolumn <> '. $rowCount);
						break;						
				}					
			}					
			foreach($lines as $key => $line){
				if($line[6] == 15155 ){					
				//	echo '<br>'.$line[0].$fname;
					//print_r($line); 					
				} 
				$liensMap[mb_strtolower($line[0])] =  array( mb_strtolower($line[0]) ,   $line[6]);
				
			}			
			//break;
		}  
		//die;
		$lines = $liensMap;
		

		echo '<br> ilosc unikalnych w xml'.count($lines);			
	//echo '<pre>'.print_r($files , true). '</pre>'; die;

		
		
		//echo '<pre>'.print_r($lines , true). '</pre>';
		
		$db  = Db::getInstance();				
		//$this->dbExt-> exec("set names utf8");				
		$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  ,  LOWER( p.`kod_produktu` )  AS lowkod  FROM `' ._DB_PREFIX_.'product` p WHERE p.`kod_produktu` IS NOT NULL';
		$res =  $db->executeS($sql);
		$map = array();
		foreach($res as $row){
			$map[$row['lowkod']] = $row;
		}
		echo '<br>  kody produktów'.count($map);
		
		$values = '';
		$notM = array();
		$c= 0;
		foreach($lines as $key => $line){
			if(array_key_exists($line[0] , $map) ){
					$values .= '(\'PolcanCSV\','. pSql($line[1]) .','. $map[$line[0]]['id_product']. '),' ;
					$c++;
			}else{
				$notM[] = $line[0];
			}
		}		
		echo '<br>ilośc odtworzonych '.$c;
		echo '<br>'.$values;
		
		echo '<pre>'.print_r($notM , true). '</pre>';
		
		echo '<pre>'.print_r(implode('\',\'' , $notM) , true). '</pre>';
		//echo '<pre>'.print_r($res , true). '</pre>';
	}	
	/*
	 * zwraca tablicę asocjacyjną  aktualnych żródel importu indeksem jest classname
	 */ 
	private function getCurentOfferSources(){
		$db  = Db::getInstance();						
		$sql =  'SELECT  o.`classname` , ip.`filename` FROM `' ._DB_PREFIX_.'importerps_parser_offer`  As o 
			LEFT JOIN `'._DB_PREFIX_.'importerps_parsers` AS ip on o.`classname` = ip.`classname`		
			group by `classname`';
		$res =  $db->executeS($sql);
		$ret  = array();
		foreach($res as $row){
			$ret[$row['classname']] = $row;
		}				
		return $ret;
	}
	
	private function getParsers(){
		$db  = Db::getInstance();						
		$sql =  'SELECT   *  FROM `'._DB_PREFIX_.'importerps_parsers` ';
		$res =  $db->executeS($sql);
		$ret  = array();
		foreach($res as $row){
			$ret[$row['classname']] = $row;
		}
		return $ret;				
	}
}
?>
