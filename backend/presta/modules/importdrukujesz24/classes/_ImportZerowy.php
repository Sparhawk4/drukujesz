<?php
error_reporting(E_ALL);
set_time_limit ( 120 );
include_once('abstractParser.php');
include_once(_PS_MODULE_DIR_.'/Db.php');
class ImportZerowy extends AbstractParser{
	public 	function mb_trim($str){		
		return preg_replace("/(^\s+)|(\s+$)/u", "", $str);	
	}
		
	public $dbExt;
	public function __construct($token){
		parent::__construct($token);
		$this->dbExt = DbExt::getDb();		
	} 	
	
	private function getIsoMap(){
		$map = array(); 
		foreach($this->languages as $id => $lang){
			$map[$lang['iso_code']] =  $id; 
		}		
		return $map;
	}
	
	public function getSubCategories(array &$catNode ,array  &$categories, $catParrent ){
		foreach($categories as $xmlCatKey  => $category){
			if($category['parent'] ==  $catParrent ){
				$catNode[$xmlCatKey] = array();
				// $subCategories
				unset($categories[$xmlCatKey]);
				
			}	
		}
		foreach( $catNode as $xmlCatKey => &$subCategories){
			self::getSubCategories($subCategories , $categories , $xmlCatKey); 
		}			
	}
	
	
	public function xmlCategoriesTree(array &$categories){
		$categoriesTree = array();
		foreach($categories as $xmlCatKey  => $category){
			if($category['parent'] ==  0){
				$categoriesTree[$category['parent']][$xmlCatKey] = array();			
				unset($categories[$xmlCatKey]);  
			}
		}	
		foreach( $categoriesTree[0] as $xmlCatKey => &$subCategories){
			self::getSubCategories($subCategories , $categories , $xmlCatKey); 			
		}
		return $categoriesTree;
	}	

	public static  function getTagsString($text){
		$str = str_replace( array('\'', '"'  ,  '/' , '\\' , '.' , ',' , ';' , ':' , '?' , '!') , ' ' , strip_tags(html_entity_decode($text))); 
		$str = preg_replace(array( '/\d/' ) , ' ' , $str);
		$arr  = preg_split(  '/[\s]+/'  , $str);
		foreach($arr as $key =>&$val){
			if(strlen($val)<5){
				unset( $arr[$key]); 
				continue; 
			}else{
				$val =   mb_strtolower($val); 					
			}
		}
		$arr = array_unique($arr);
		$str = implode(',' ,$arr);
		return($str); 
	}			
	
	public static function getInstance(){

		echo 'ret instance ';
	}		
	
	
	public function parseCatTree($cat , &$catTree , $parentId ){
		$catTree[(string)$cat['id']]['name'] = (String)$cat->name;
		$catTree[(string)$cat['id']]['parent'] = $parentId;
		foreach($cat->category as $category)
			$this->parseCatTree($category , $catTree  , (string)$cat['id'] )  ; 		
	}
	
	
	
	
	
	public function parse(){		
		
		
		include_once(_PS_MODULE_DIR_.'importdrukujesz24/classes/HtmlCleaner.php');	
		include_once(_PS_MODULE_DIR_.'importdrukujesz24/classes/Product.php');	
		$this->dbExt-> exec("set names utf8");
		$db = $this->dbExt;
		
/*		
		$stmt  =  $db->query('TRUNCATE `a_oem_synonym`');
		$stmt->execute();;
		
		$stmt  =  $db->query('TRUNCATE `a_color_synonym`');
		$stmt->execute();;		
		
		$stmt  =  $db->query('TRUNCATE `a_color`');
		$stmt->execute();				
*/

/*					
//		$stmt  =  $db->query('TRUNCATE`a_product`');
//		$stmt->execute();;
		
//		$stmt  =  $db->query('TRUNCATE `a_product_oem`');
//		$stmt->execute();;		
*/ 
	
/*		
		$stmt  =  $db->query('TRUNCATE `a_product_synonym`');		
		$stmt->execute();
		
		$stmt  =  $db->query('TRUNCATE`a_printer`');
		$stmt->execute();;
		
		$stmt  =  $db->query('TRUNCATE`a_oem_printer`');
		$stmt->execute();;
*/		
		//echo _PS_MODULE_DIR_; die;
		
	
	$stmt  =  $db->query('SELECT c.`categories_id`  ,  c.`parent_id`  ,  cd.`categories_name`
		FROM `categories`  AS c 
		LEFT JOIN   categories_description AS cd ON c.`categories_id` =  cd.`categories_id` and cd.`language_id` = 1'
	);	
	$stmt->execute();	
	
	$categories  = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$categoriesMap = array();
	foreach($categories  as $row){	
		$categoriesMap[$row['categories_id']] = $row;
	}
	$categories = array();
			
	
	$stmt  =  $db->query('SELECT  pd.*, p.* , c.`categories_id` FROM `products`  AS p 
		LEFT JOIN products_description AS pd ON pd.`products_id` =  p.`products_id` and pd.`language_id` = 1 
		LEFT JOIN products_to_categories AS c ON c.`products_id` =  p.`products_id`
		WHERE  c.`categories_id` IS NOT NULL 
		'
		);		
	$stmt->execute();	
	$allProducts = $stmt->fetchAll(PDO::FETCH_CLASS, 'ProductImport'); 		
	$allIds = array();
	foreach($allProducts as $p)	{
		$allIds[] = $p->products_id;
	}
	unset($allProducts);

	$stmt  =  $db->query('SELECT  pd.*, p.* , c.`categories_id` FROM `products`  AS p 
		LEFT JOIN products_description AS pd ON pd.`products_id` =  p.`products_id` and pd.`language_id` = 1 
		LEFT JOIN products_to_categories AS c ON c.`products_id` =  p.`products_id`
		WHERE  c.`categories_id` IS NOT NULL 
		AND `products_model` <> ""
		'
		);		
	$modelsProduct = $stmt->fetchAll(PDO::FETCH_CLASS, 'ProductImport'); 		
	$modelsIds = array();
	foreach($modelsProduct as $p)	{
		$modelsIds[] = $p->products_id;
	}
	unset($modelsProduct);
		
	$emptyIds =  array_diff($allIds, $modelsIds);
	
	$stmt  =  $db->query('SELECT  pd.*, p.* , c.`categories_id` FROM `products`  AS p 
		LEFT JOIN products_description AS pd ON pd.`products_id` =  p.`products_id` and pd.`language_id` = 1 
		LEFT JOIN products_to_categories AS c ON c.`products_id` =  p.`products_id`
		WHERE   p.`products_id` IN (' . implode(',' , $emptyIds) . ')
		'
		);		
	$stmt->execute();	
	$emptyProducts = $stmt->fetchAll(PDO::FETCH_CLASS, 'ProductImport'); 		
	
	
	$stmt  =  $db->query('SELECT  pd.*, p.* , c.`categories_id` FROM `products`  AS p 
		LEFT JOIN products_description AS pd ON pd.`products_id` =  p.`products_id` and pd.`language_id` = 1 
		LEFT JOIN products_to_categories AS c ON c.`products_id` =  p.`products_id`
		WHERE  c.`categories_id` IS NOT NULL 
		AND `products_model` <> ""
		GROUP BY p.`products_model` 
		'
		);		
	$uniqueProduct = $stmt->fetchAll(PDO::FETCH_CLASS, 'ProductImport'); 			
	$uniqueIds = array();
	foreach($uniqueProduct as $p)	{
		$uniqueIds[] = $p->products_id;
	}	
	
	$notUniueIds =  array_diff($modelsIds, $uniqueIds);
	
	$stmt  =  $db->query('SELECT  pd.*, p.* , c.`categories_id` FROM `products`  AS p 
		LEFT JOIN products_description AS pd ON pd.`products_id` =  p.`products_id` and pd.`language_id` = 1 
		LEFT JOIN products_to_categories AS c ON c.`products_id` =  p.`products_id`
		WHERE   p.`products_id` IN (' . implode(',' , $notUniueIds) . ')
		'
		);		
	$stmt->execute();		
	$notUniueProduct = $stmt->fetchAll(PDO::FETCH_CLASS, 'ProductImport'); 		

	foreach($notUniueProduct as &$product){
		$product->products_model = 'notUnique'.$product->products_model;		
		$uniqueProduct[] = $product;
	}
	unset($notUniueProduct);
	unset($notUniueIds);



	$stmt  =  $this->dbExt->query('SELECT m.`manufacturers_id` , m.`manufacturers_name` from manufacturers as m');
	$stmt->execute();
	$manufactuers  = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$manufactuersMap = array();
	foreach($manufactuers  as $row){
		$manufactuersMap[$row['manufacturers_id']] = $row['manufacturers_name'];
	}
	$manufactuersMap[0] = 'UNKNOWN';
	
	$unknownModelsIds = array();
	
	
	//echo '<br>'.count($uniqueProduct);
	//echo '<br>'.count($emptyProducts);
	
	
	
	foreach($emptyProducts  as &$p){
		$matches = array();
			$ret  = preg_match ( '/\[.*\]/' , $p->products_name , $matches );
			if($ret ===1){				
				$kod  = trim(substr ($matches[0]  , 1, strlen($matches[0])-2));				
				$p->products_model  = 'prod_'.$manufactuersMap[$p->manufacturers_id].'_'.$kod;
				$uniqueProduct[] = $p;
			}else{
				$unknownModelsIds[]  = $p->products_id;				
			}		
	}
	//echo '<br>'.count($uniqueProduct);
	//die;
	unset($emptyProducts);
	unset($emptyIds);
		
	libxml_use_internal_errors(true);
	libxml_clear_errors();
	
	$tableRows = array();
	$spans = array();
	$producers = array();
	$productTypes = array();


	$allPrinters  = array();
	$allPrintersModelMap = array();


	
	foreach($uniqueProduct as $key=>$product){
		$product->products_model = trim($product->products_model);		
		if(empty($product->products_model)){
			unset($uniqueProduct[$key]);
		}else{
			$html =$product->products_description;
			// <!DOCTYPE html>
			$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$html.'</body></html>';						 
			$html = HtmlCleaner::cleanHtml($html);
			$html = html_entity_decode($html, ENT_COMPAT , 'UTF-8' );	
			libxml_clear_errors();
			$doc = new DOMDocument();
			$doc->loadHTML($html);
			$xpath = new DOMXPath($doc);

			$body = $doc->getElementsByTagName('body')->item(0);						
			$nodeList = $xpath->query('table' , $body);
			if($nodeList == false or $nodeList->length != 1){	
				//echo '<br/>'.$product->products_id. ' ' .$product->products_model. ' error0'.__FILE__ . __LINE__ ;
				//echo '<br/>'.$product->products_description;
				//echo '<pre>'.print_r( $product, true) . '</pre>'; 
				unset($uniqueProduct[$key]); 
				continue;				 
			}
			$table = $nodeList->item(0);
			$trs = $xpath->query('tbody/tr' , $table);
			
			$tableSize = $trs->length;			
			for ($i = 1; $i < $tableSize; $i++) {
				$td = $xpath->query('td[1]' , $trs->item($i))->item(0);
				$attr = $td->nodeValue;
				$attr = $this->mb_trim( $td->nodeValue);
				$tableRows[$attr][] =  $key;
				if($attr == 'Kompatybilność z drukarkami'){ // tabela  
					// $td = $xpath->query('td[2]/table' , $trs->item($i))->item(0);
					$nl = $xpath->query('td[2]/table/tbody/tr/td' , $trs->item($i));
					if($nl === false){
						echo '<br/> brak tabeli z drukarkami '.$product->products_id. ' @ ' .$product->products_model;						
					}else{
						foreach($nl as $printerNode){
							$r  = parsePrinterTd($printerNode , $doc , $xpath);
							$product->printers  = array_merge($product->printers , $r);
							array_unique($product->printers);								
						}												
					} 

				}else{								
					$td = $xpath->query('td[2]' , $trs->item($i))->item(0);
					$val = $td->nodeValue ;
					$val =  $this->mb_trim($td->nodeValue);
					if(!empty($attr) AND !empty($val)){
						$product->attributes[$attr] =  $val; 
					}
				}
			}
			
			$allPrinters = array_merge($allPrinters , $product->printers); 
			foreach($product->printers as $val){
				$allPrintersModelMap[$val] = $product;
			}	
			//echo '<pre>'.print_r($product->printers, true).'</pre>';
									
			// $nodeList = $xpath->query('tbody/tr/td[1]' , $table);
			
									
			$nodeList = $xpath->query('span' , $body);
			if($nodeList === false){
				echo '<br> nie ma span error 1 '.__FILE__ . __LINE__;
				continue;
			}	
			$str = '';
			foreach($nodeList as $span){					
				$str .= $this->mb_trim(html_entity_decode($span->nodeValue)). ' ';
				//$str .= trim(html_entity_decode($span->nodeValue)). ' ';
			}
			$str = $this->mb_trim($str);
			if(!empty($str)){	
				//$tokens = mb_split("\s+" , $str);
				$tokens = explode(' ',$str);	
				$tokensLen = count($tokens);		
				/*$tokens  = preg_split("/\s+/u", $str);  // b��d w mojej wersji php
				if(is_array($tokens)){
					$tokensLen = count($tokens);
					end($tokens);
					$end = key($tokens);
					if($tokensLen < $end+1){
						$tokensLen--;
						unset($tokens[$end]);
					}  	
				} */				
				for($i =0; $i<$tokensLen ; $i++){
					if( ($strLen = mb_strlen($tokens[$i])) === false ){
						echo '<br/>mb_strlen error'.$product->products_id. ' ' .$product->products_model. ' token: '. $tokens[$i] .' ' .__FILE__ . __LINE__ ;
						$product->synonyms[] = $tokens[$i];
					}else{
						if($strLen <= 4){
							// sprawdzam czy nastęny to same liczby 
							if($i+1 <$tokensLen){
								if(mb_ereg_match("\d*", $tokens[$i+1]) AND  ($strLen  = mb_strlen($tokens[$i])) !== false AND  $strLen<=5 ) {
									$product->synonyms[] = $tokens[$i].' '.$tokens[$i+1];
									$i++;
									continue;
								}
							}							
						}
						$product->synonyms[] = $tokens[$i];
					}
				}		
			}
		
			//  PRODUCENCI TYPY 
			if(empty($product->categories_id)){
				echo '<br>produkt :'. $product->products_id . 'nie ma kategorii';
				$product->categories_id = null;
			}else{
				$i = getCategoryParent($product->categories_id, $categoriesMap); 
				if($i === false){
					echo '<br>produktu nie ma w $categoriesMap:'. $product->products_id . 'nie ma kategorii ???ma  nieprzypisaną kategorię???';
					$product->categories_id = null;
				}else{
					$catName = $this->mb_trim($categoriesMap[$i]['categories_name']);
					$product->producent =  $catName;
					
					$categories[$catName] = null;
					//$producers[$product->producent]['subCat'] = array();
				}	
				if(array_key_exists($product->categories_id, $categoriesMap)){
					$catName = $this->mb_trim($categoriesMap[$product->categories_id]['categories_name']);
					$categories[$catName] = null;
					$product->type = $catName;
					$productTypes[$product->type]  = null;
					
					$producers[$product->producent]['subCat'][$product->categories_id] = $catName;
				}				
			}						
		}
	}
	$products = $uniqueProduct;
	unset($uniqueProduct);
	 
	
	
	$defaultLang = Configuration::get('PS_LANG_DEFAULT');
	$homeDirId = (int)Configuration::get('PS_HOME_CATEGORY');
	$shopCat = Category::getChildren( $homeDirId , $defaultLang , false); 
	$shopCatFirstLevel = array();
	foreach($shopCat as $cat){
		$shopCatFirstLevel[mb_strtolower($cat['name'])] = $cat;		
	}
	
	$catXmlToPrestaMap = array();
	foreach($producers as $p => $val){
		if(!array_key_exists(mb_strtolower($p) , $shopCatFirstLevel)){
			$cat = new Category(); 
			$cat->name[$defaultLang] =	$p;
			$cat->link_rewrite[$this->defaultLang] = Tools::str2url($p);
			$cat->id_parent = $homeDirId; 
			$cat->add();
			$producers[$p]['idCat'] =  $cat->id;
		}else{
			$producers[$p]['idCat'] =  $shopCatFirstLevel[mb_strtolower($p)]['id_category'];
		}
		$presteSub = Category::getChildren( $producers[$p]['idCat'] , $defaultLang , false); 	
		$presteSubMap = array();
		foreach($presteSub as $cat){
			$presteSubMap[mb_strtolower($cat['name'])] = $cat;		
		}
		
		foreach($val['subCat'] as $xmlId => $name){
			if(!array_key_exists(mb_strtolower($name) , $presteSubMap)){
				$cat = new Category(); 
				$cat->name[$defaultLang] =	$name;
				$cat->link_rewrite[$this->defaultLang] = Tools::str2url($name);
				$cat->id_parent = $producers[$p]['idCat']; 
				$cat->add();
				$catXmlToPrestaMap[$xmlId]  = $cat->id;
			}else{
				$catXmlToPrestaMap[$xmlId]  = $presteSubMap[mb_strtolower($name)]['id_category'];;
			}				
		}
	}
		
		
		$productArray = array();
		foreach($products as $key=>$product){
			$productArray[] =  (array) $product;	
		}
		$products = $productArray;
		
		
		
		foreach($products as $key=>$product){
			//echo '<pre>'.print_r($product, true).'</pre>';
			//die;
			$attributesCombination = array(); 
			$parserProduct  = array(); 
			$parserProduct['attributesCombination'] = $attributesCombination;
			$parserProduct['quantity']  =  $product['products_quantity'];
			
			$parserProduct['wholesale_price'] =  $product['products_price'];
			$parserProduct['on_sale'] = (int)($parserProduct['quantity']>0? true : false); 
			$parserProduct['specyfic_price'] = $parserProduct['wholesale_price'];															
			if(!empty($product['products_image'])){
				$parserProduct['imgs'][] = 'http://www.drukujesz24.pl/images/'.$product['products_image'];				
			}
			$parserProduct['reference'] = $product['products_model']; 
			$parserProduct['ean13'] =  ''; 
			
		
			$producer  =  $manufactuersMap[$product['manufacturers_id']]; 
			if(!empty($producer)){
				$producer  = $this->getManufacturerNameMap($producer);
				$parserProduct['manufactuer'] = $producer; 
				$this->manufacturers[$producer] = 0;
			}									
		
		
			$parserProduct['name'][1] = $product['products_name']; 
			$parserProduct['description'][1] = $product['products_description']; 

			$tags  = self::getTagsString($parserProduct['description'][1]); 
			if(strlen($tags))
				$parserProduct['tags'] = $tags; 															
			$parserProduct['short_description'][1] = '';
			
			
			$parserProduct['category'] =  $product['type'] ; //  $categoriesMap[  $prodCategoriesMap[ $product ['products_id']] ]['categories_name'];
			$parserProduct['id_category'] =  $catXmlToPrestaMap[$product['categories_id']]; // $this->categories[$prodCategoriesMap[ $product ['products_id']] ]['id_category'];
			
			
			//echo '<pre>'.print_r($parserProduct, true).'</pre>';
			//die;
			
			
			
			
			$parserProduct['kod_produktu'] = (String)$product['products_model']; 		
			$parserProduct['synonyms'] = $product['synonyms'];
			$parserProduct['attributesxml'] = $product['attributes'];
			$parserProduct['printers'] = $product['printers'];
					
			$this->products[(String)$product['products_model']] = $parserProduct; 		
			unset($products[$key]);			
		}
		$allPrinters  = array_unique($allPrinters);		
		natcasesort($allPrinters);
		$allPrinters = array_values($allPrinters);
		
		// drukarki 
		$manufactuerSynonymCI = $this->manufactuerSynonym;
		foreach ($this->manufacturers as $k => $zero){
			$manufactuerSynonymCI[mb_strtolower($k)] =$k; 			
		}
		//echo '<pre>'.print_r( $manufactuerSynonymCI, true),'</pre>';
		
		$printerManufactuers = array();
		$unrecognizedPrinter = array();
		$printerModelRealMap = array();		
		
		foreach($allPrinters as $k=>$model){
			$lowerModel = mb_strtolower($model);
			$find = false;
			$findName ='';			
			foreach ($manufactuerSynonymCI as $manName => $realName){
				if( strpos($lowerModel, $manName) === 0  ){
					if(empty($findName)){
						$findName = $manName;						
					}else{
						if(strlen($manName)> strlen($findName))
							$findName = $manName;
					}														
				}				
			}
			if(!empty($findName)){
				$prodName = $this->getManufacturerNameMap($manufactuerSynonymCI[$findName]);
				$printerManufactuers[$prodName] = 0;
				$printerModelRealMap[$model] = array('producerName' => $prodName , 'model' => trim(substr($model, strlen($findName))) );								
			}else{
				$unrecognizedPrinter[] = $model;				
			}			
		}
		
		$atributesGroups = array();		
		foreach ($this->products as &$product){
			foreach($product['printers'] as &$model){
				if(array_key_exists($model, $printerModelRealMap)){
					$model = $printerModelRealMap[$model];	
					//die("sd");				
				}else{
					$model = array('producerName' => null , 'model' => trim($model));
				}				
			}
			
			foreach($product['attributesxml'] as $name => $v){
				//if( array_key_exist($name , $atributesGroups ))					
						$product['features'][$name] = mb_strtolower($this->mb_trim( html_entity_decode($v)));// = null; 
					$atributesGroups[$name][] = $v;				
			}			
		}
		foreach ($atributesGroups as $name => $v){		
			$v = array_map(array($this, 'mb_trim'), $v);
			$atributesGroups[$name] = array_unique($v);
			natcasesort($atributesGroups[$name]);
		}	
		$featuresGroup = array();
		foreach ($atributesGroups as $name => $v){
			foreach($v as $vv)
				$featuresGroup[$name][mb_strtolower($this->mb_trim( html_entity_decode($vv)))] = $vv;
		}
		/*
		foreach ($atributesGroups as $name => $v){
			echo $name.'>'. count($v). '<br>';
		}			 
		*/
		
		
		/*
		foreach ($this->products as $k=> $product){
			echo '<pre>'.print_r($product , true ).'</pre>';
			break;
			
		}
		*/
		/*$colorsCI = array();
		foreach($atributesGroups['Kolor wkładu'] as $kolor){
			
			
		}
		*/
		$this->featuresGroup = $featuresGroup; 
		$this->printerManufactuers = $printerManufactuers;
		echo '<pre>'.print_r($featuresGroup, true).'</pre>';
		return;
		echo '<pre>'.print_r($featuresGroup, true).'</pre>';
		return;
		echo count($allPrinters);
		echo '<pre>'.print_r($atributesGroups, true).'</pre>';
		//echo '<pre>'.print_r($this->products, true).'</pre>';
		//$this->products[(String)$product['products_model']] = $parserProduct;
		

		
		
		//echo '<pre>'.print_r($allPrinters, true),'</pre>';
		/*
		$f = false;
		foreach($allPrinters as $model){
			if($f == false){
				echo '<pre>'.print_r( $allPrintersModelMap[$model], true),'</pre>';
				$f = true;		
			}
			
			$o = $allPrintersModelMap[$model];
			echo 'id=>'.$o->products_id.' '.$model,'<br>';			
		}
		*/
		
		echo '<pre>'.print_r( $printerManufactuers, true),'</pre>';
		//echo '<pre>'.print_r( $unrecognizedPrinter, true),'</pre>';
//		foreach($unrecognizedPrinter as $model)
//			echo '<pre>'.print_r( $allPrintersModelMap[$model], true),'</pre>';
		 		
		echo '<pre>'.print_r( $unrecognizedPrinter, true),'</pre>';
		
		//echo '<pre>'.print_r( $this->manufacturers, true),'</pre>';
		//echo '<pre>'.print_r($allPrinters, true).'</pre>';
		//die;
		//echo '<pre>'.print_r($this->products, true).'</pre>';
		//echo '<pre>'.print_r( $this->manufactuerSynonym, true),'</pre>';
		//echo '<pre>'.print_r($categoriesMap, true).'</pre>';
		//echo '<pre>'.print_r($this->categories, true).'</pre>';
		//echo '<pre>'.print_r($categories, true).'</pre>';
		
		return;	
		echo '<pre>'.print_r($categoriesMap, true).'</pre>';
		
		
		echo '<pre>'.print_r($this->categories, true).'</pre>';
		
		
		
		$tmpCats =  $this->categories; 
		
		echo '<pre>'.print_r($this->categoriesTree, true).'</pre>';
		
		self::getSubCategories($this->categoriesTree ,$tmpCats, 0 ); 		
		
		
		echo '<pre>'.print_r($this->categoriesTree, true).'</pre>';
		
		if(!empty($tmpCats)){
			echo 'error categories not in tree ' ; 
			echo '<pre>'.print_r( $tmpCats, true),'</pre>';
			die; 
		}

		$this->synchroniseCategories();		
			

		echo print_r($categories[0], true);
		echo print_r($prodCategories[0], true);
		
		
		
		//$products = $newProducts; 	
		
		echo '<pre>'.print_r($manufactuers[0], true).'</pre>';
		//echo '<pre>'.print_r($products[0], true).'</pre>';
			
		echo memory_get_peak_usage() / (1024*1024);
		echo 'koniec'. count($products); die;
		
		
		/*$product = new Product(519);
		$combinaisons = $product->getAttributeCombinaisons(_PS_LANG_DEFAULT_);
		echo '<pre>'.print_r($combinaisons, true). '</pre>';
		*/

		$isoMap = $this->getIsoMap();	
		if(!($content = self::getSourceFile())){
			return false; 
		}
		$xml = new SimpleXMLElement($content ,LIBXML_NOCDATA);		
		
		$fullUrl =  (String)$xml->full['url'];  
		$lightUrl =  (String)$xml->light['url'];  
		$categoriesUrl  = (String)$xml->categories['url'];  
		$sizesUrl =  (String)$xml->sizes['url'];  		
		$producersUrl =  (String)$xml->producers['url'];  
		$unitsUrl =  (String)$xml->units['url'];  
		$parametersUrl =  (String)$xml->parameters['url'];  
		$seriesUrl =  (String)$xml->series['url'];  
		$warrantiesUrl =  (String)$xml->warranties['url'];  
		
		if(($fullContent = file_get_contents($fullUrl)) === false) return false;
			//$fullContent = file_get_contents(dirname(__FILE__). DIRECTORY_SEPARATOR . 'full.xml');			
		if(($lightContent = file_get_contents($lightUrl)) === false) return false;
			//$lightContent = file_get_contents(dirname(__FILE__). DIRECTORY_SEPARATOR . 'light.xml');
		if(($categoriesContent = file_get_contents($categoriesUrl)) === false) return false;
			//$categoriesContent = file_get_contents(dirname(__FILE__). DIRECTORY_SEPARATOR . 'categories.xml');
		if(($sizesContent = file_get_contents($sizesUrl)) === false) return false;
			//$sizesContent = file_get_contents(dirname(__FILE__). DIRECTORY_SEPARATOR . 'sizes.xml');
		//if(($producersContent = file_get_contents($producersUrl)) === false) return false;
		//if(($unitsContent = file_get_contents($unitsUrl)) === false) return false;
		//if(($parametersContent = file_get_contents($parametersUrl)) === false) return false;
		//if(($seriesContent = file_get_contents($seriesUrl)) === false) return false;
		//if(($warrantiesContent = file_get_contents($warrantiesUrl)) === false) return false;
		
		$fullXml = new SimpleXMLElement($fullContent ,LIBXML_NOCDATA);		
		$lightXml = new SimpleXMLElement($lightContent ,LIBXML_NOCDATA);		
		$categoriesXml = new SimpleXMLElement($categoriesContent ,LIBXML_NOCDATA);				
		$sizesXml = new SimpleXMLElement($sizesContent ,LIBXML_NOCDATA);		
		//$producersXml = new SimpleXMLElement($producersContent ,LIBXML_NOCDATA);		
		//$parametersXml = new SimpleXMLElement($parametersContent ,LIBXML_NOCDATA);		
		//$seriesXml = new SimpleXMLElement($seriesContent ,LIBXML_NOCDATA);		
		//$warrantiesXml = new SimpleXMLElement($warrantiesContent ,LIBXML_NOCDATA);		
		
		//echo strlen($fullContent);
		// tylko te produkty które mają ustawine stock i ilo 
		$products = array(); 
		foreach( $lightXml->products->product as $product ){		
			$qcount = 0;  
			foreach( $product->sizes->size  as $size){
				if(isset($size->stock) and ((int) $size->stock['quantity']) >=0){
					$qcount += (int) $size->stock['quantity']; 
				}											
			}
			if($qcount > 0) 
				$products[(int)$product['id']] = null;
		}
		foreach( $fullXml->products->product as $product ){
			$id = (int)$product['id']; 
			if( array_key_exists($id, $products))
				$products[$id] = (int) $product->category['id'];		
		}		
				
		// odczyt tych które są w turystyka
		$categoryBlackMap = array();
		$str  = file_get_contents('http://sport.markowesklepy.pl/modules/importps/categories.txt');
		$c = explode("\n",$str);
		foreach($c as $v){
			$categoryBlackMap[$v] = true;
		}		
		
		$str  = file_get_contents('../modules/importps/categories.txt');
		$c = explode("\n",$str);		
		$categoryWhiteMap = array();
		foreach($c as $v){
			$categoryWhiteMap[$v] = true;
		}	
		// sprawdzam czy są rozłączne 
		foreach( $categoryWhiteMap as $k => $t){
			if(array_key_exists($k , $categoryBlackMap)){
				echo 'konflikt w plikach category.txt kategoria '. $k ; 
				die;
			}
		}
		
			
		$catTree = array();
		$categoryTreeParent = 0; 
		foreach($categoriesXml  as $cat){								
			//print_r($cat);
			$this->parseCatTree($cat , $catTree  , $categoryTreeParent);			
		}	
		unset($catTree[0]); // *kategoria tymczasowa 
		foreach( $categoryBlackMap as $k => $t){
			if(array_key_exists($k , $catTree)){
				unset($catTree[$k]);
			}
		}
		$c  = $catTree; 
		foreach($c as $k=> $v){
			if($v['name'] == 'SPORT'  or $v['name'] == 'TURYSTYKA' ){
				unset($catTree[$k]);
			}
		}

//	echo '<pre>'.print_r($catTree, true). '</pre>';   die;					
//		echo '<pre>'.print_r($products, true). '</pre>';   die;					
		$this->categories = $catTree; 		
		// echo '<br><pre>'.print_r($this->categories, true). '</pre>';   die;
		
		$tmpCats =  $this->categories; 
						
		//self::xmlCategoriesTree($this->categories); 
		self::getSubCategories($this->categoriesTree ,$tmpCats, $categoryTreeParent ); 
		if(!empty($tmpCats)){
			echo 'error categories not in tree ' ; 
			echo '<pre>'.print_r( $tmpCats, true),'</pre>';
			die; 
		}		
		
	//echo '<pre>'.print_r($this->categoriesTree, true). '</pre>';   die;						
		
		$this->synchroniseCategories();		
		// teraz pobrać wszystkie 
		
		$className = get_class($this);			
		$sql = 'SELECT `id_xml` from '._DB_PREFIX_.'importerps_category_map  WHERE classname = \''.pSQL($className).'\'';
		$results = Db::getInstance()->ExecuteS($sql);				
		$r =array();
		foreach($results  as $row){
			$categoryWhiteMap[$row['id_xml']] = true;
		}				
		// zapis 
		file_put_contents('../modules/importps/categories.txt' , implode("\n", array_keys($categoryWhiteMap)));
		

				
//		echo '<pre>'.print_r($this->categories, true). '</pre>';   die;
//		echo '<pre>'.print_r($this->categoriesTree, true). '</pre>';  die;		
		$productsIds = array();
		foreach( $fullXml->products->product as $product ){
			$catKey  = (int) $product->category['id']; 
			$id = (String)$product['id']; 
			if( array_key_exists($id , $products) and  (array_key_exists($catKey , $catTree)  or $catKey == $categoryTreeParent)  ) 
				$productsIds[$id]['full'] = $product; 
		}		
		foreach( $lightXml->products->product as $product ){		
			if(array_key_exists((String) $product['id'] , $productsIds)){ 
				$productsIds[(String)$product['id']]['light'] = $product; 
			}	
		}	
		// grupy atrybutów 
		$attributeMap = array();  
		foreach( $sizesXml->group as $group ){
			$name = str_replace( array('#' , '>') , array( 'nr ', '+') , htmlspecialchars_decode((String)$group['name'])); 
			//$this->attributesGroup[$name]['is_color_group'] = 0;
			foreach($group->size as $size){
				$id = (string) $size['id']; 
				$attr  =str_replace( array('#' , '>') , array( 'nr ', '+') ,  htmlspecialchars_decode((String)$size['name']));				
				// $this->attributesGroup[$name]['attributes'][$id] = 0;
				$attributeMap[$id] = array(				
					'name' => $attr,					
					'groupName' =>$name 
				);
			}			
		}
		
		//echo '<pre>'.print_r($attributeMap, true). '</pre>'; 		
		 //echo '<br><pre>'.print_r($this->attributesGroup, true). '</pre>';  die;				
		//echo '<pre>'.print_r($productsIds, true). '</pre>';  die;					
				
		foreach($productsIds as $prod){
			// echo '<pre>'.print_r($prod, true). '</pre>';  die;			
			$attributesCombination = array(); 
			$unitCount = 0;				
			if(!empty($prod['light'])){
				$light  = $prod['light'];
				$groupName = null;  
				foreach( $light->sizes->size  as $size){
				// uzupełinić atributes group 
					$id = (string) $size['id']; 
					$attr = $attributeMap[$id]['name'];					
					if($groupName === null){					
						$groupName  = $attributeMap[$id]['groupName'];					
					}else{
						if($groupName !== $attributeMap[$id]['groupName']){
							echo $groupName .'!=='. $attributeMap[$id]['groupName'] . ' trzeba cos z tym zrobić' ; die;
						}
					}					
					$this->attributesGroup[$groupName]['attributes'][$attr] = 0;
					$this->attributesGroup[$groupName]['is_color_group'] = 0;
					$q  = (int) $size->stock['quantity'];
					$tmpArray  = array(); 
					if(isset($size['weight'])){
						$tmpArray['weight'] = (float) ((int) $size['weight'] / 1000); 												 
					}
					
					
					$tmpArray['price'] =  (string) $light->price['net']; 
					if(isset($size->price)){
						$tmpArray['price'] =  (string) $size->price['net']; 
						//echo '<pre>'.print_r($light, true). '</pre>';  die;			
					}
					
					
					
					if($this->onlyAvalible){
						if($q){	
							$tmpArray['attributes'] = array($groupName =>$attr) ; 					
							$tmpArray['quantity'] = $q; 					
							$attributesCombination[] =$tmpArray;		
						}				
					}else{				
						$tmpArray['attributes'] = array($groupName =>$attr) ; 					
						$tmpArray['quantity'] = $q; 					
						$attributesCombination[] =$tmpArray;									
					}					
				}
			}else{
				continue;
			}
		
						
			$parserProduct  = array(); 
			$parserProduct['attributesCombination'] = $attributesCombination;
			$product = $prod['full'];
			$producer  = trim((string) $product->producer['name']); 
			if(!empty($producer)){
				$producer  = $this->getManufacturerNameMap($producer);
				$parserProduct['manufactuer'] = $producer; 
				$this->manufacturers[$producer] = 0;
			}				
			$parserProduct['category'] = (string)$product->category['id'];

			$parserProduct['wholesale_price'] = (String) $light->price['net']; 
			$parserProduct['on_sale'] =  1 ;   //(int) $product->sale; 
			$parserProduct['specyfic_price'] = (String) $light->price['net'];

			if(!empty($product->images) and  !empty($product->images->large)  ){			
				foreach($product->images->large->image as $photo){
					$parserProduct['imgs'][] = (string)  $photo['url']; 
				}		
			}			
			//print_r($product->xpath('description/name[@xml:lang = "pol"]'));   
			
			//print_r(  $product->xpath("description/name[lang('pol')]")));    
			
			
			

			
			//$data->xpath("code[lang('en')]")

			$str = $product->xpath("description/name[lang('pol')]"); 
			$parserProduct['name'][_PS_LANG_DEFAULT_] = trim((string)$str[0]); 
			
			$str =  $product->xpath("description/long_desc[lang('pol')]");			
			if(!empty($str))		
				$parserProduct['description'][_PS_LANG_DEFAULT_] = trim((string)$str[0]); 			
			else	
				$parserProduct['description'][_PS_LANG_DEFAULT_] = ''; 			
			$tags  = self::getTagsString($parserProduct['description'][_PS_LANG_DEFAULT_]); 
			if(strlen($tags))
				$parserProduct['tags'] = $tags;
				
				
				
			$str = $product->xpath("description/short_desc[lang('pol')]");	
			if(!empty($str))
				$parserProduct['short_description'][_PS_LANG_DEFAULT_] = trim((string)$str[0]);
			else			
				$parserProduct['short_description'][_PS_LANG_DEFAULT_] = '';
			//echo '<pre>'.print_r($parserProduct, true). '</pre>';  //die;			
			$this->products[(string) $product['id']] = $parserProduct; 
		}
		//echo '<br>'.count($this->products); 		
		//echo '<pre>'.print_r($this->products, true). '</pre>';  die;		
		
		
		return true;		
	}	
	
	
	public static function getSourceFile(){
		//return file_get_contents(dirname(__FILE__). DIRECTORY_SEPARATOR . self::$sourceFile); 

		if(!($content = file_get_contents('http://www.hurtowniasportowa.eu/edi/export-offer.php?client=p.libuda@markowesklepy.pl&language=pol&token=e1b91ab69523544365b342b&shop=1&type=gateway&format=xml&iof_2_4'))){

			return false; 		
		}		
		return		$content; 
		
	}
	
}



	function getCategoryParent($categoriesMapIndex , $categoriesMap){
		if( !array_key_exists($categoriesMapIndex , $categoriesMap)){
			return false;
		}
		if($categoriesMap[$categoriesMapIndex]['parent_id'] != 0){
			return getCategoryParent($categoriesMap[$categoriesMapIndex]['parent_id'],$categoriesMap);
		}
		return $categoriesMapIndex; 		
	}
	
	function mb_trim($str){		
		return preg_replace("/(^\s+)|(\s+$)/u", "", $str);	
	}
	
	function usortCallback($a , $b ){
		return strlen($a) - strlen($b);
	}	
	
	
	function parsePrinterInnerTd($node){		
		$str  = innerHtml($node);
		$str  =  html_entity_decode($str);
		//$str =  html_entity_decode($string, ENT_NOQUOTES, 'utf-8');
		
		$str  = str_replace(array('<br>', '<br />', '<br/>') , '<delimiter>' , $str);
		
		
		$r = explode('<delimiter>', $str);
		foreach ($r as $key => $v) {
			$r[$key] = strip_tags($v);
			//$r[$key] =remTags($v);			
		}
		foreach ($r as $key => $v) {
			$v = strtr($v, '\n', ' ');
			$r[$key] = preg_replace('/\s+/' , ' ' , $v);
			
		}			
		$r  = array_map('mb_trim' , $r);		
		$r = array_unique($r);
		foreach ($r as $key => $v){ 
			if(empty($v)){
				unset($r[$key]);						
			}	
		}
		//foreach ($r as $key => $v) {
		//	$v[$key] =   split('\s+' , $v , 2);
		//}
		return $r;						
	}
	
	function parsePrinterTd($td , $domDocument , $xpath){
		
		$t = $domDocument->saveXML($td);
		$t1 = $domDocument->saveXML($td ,  LIBXML_NOEMPTYTAG);
		
		
		// span jest najczęściej
		$ret = array();
			$ret = array_merge ($ret , parsePrinterInnerTd($td) );
			return array_unique($ret);
		
		
		$nl = $xpath->query('span' , $td);
		$l = $nl->length;		
		if($nl === false){
			echo '<br/> coś z html '.__FILE__ . __LINE__ ;			
		}
		if($nl->length !== 0){  // jesy kilka span
			foreach ($nl as $node){
				$ret = array_merge ($ret , parsePrinterInnerTd($node) );
			} 			
		}else{
			$ret = array_merge ($ret , parsePrinterInnerTd($td) );			
		}
		return array_unique($ret);		
	}
	
	function innerHtml( $node ) {
		$ret= '';
		$children = $node->childNodes;
		foreach ($children as $child) {
			$ret .= $child->ownerDocument->saveXML( $child );
		}	
		return $ret;
	}
	
	function remTags($str){
		return preg_replace('/<(.*?)>/u', '' ,  $str   ) ; 	
	}


?>
