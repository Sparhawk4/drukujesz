<?php
include_once('abstractParser.php');
include_once(_PS_MODULE_DIR_.'/Db.php');
class PolcanCSV extends AbstractParser{
	
	
	public static function getURL($id_xml){
		return('http://esklep.polcan.pl/pozycja_katalogowa.php?poz='.$id_xml);
	}
	
	public static function getName($csvRowJson){
		$csvRow = json_decode($csvRowJson , true);
		return	$csvRow[3];
	}
	
	
	
	/*
	 * 	w produktach ktore już są w sklepie znajduje produkty kompatybilne 
	 * 
	 */
	/*
	 [classname] => PolcanCSV
     [id_xml] => 23274
     [cena] => 115.11
     [quantity] => 15
     [json] => ["6509B008","Canon","Materia\u0142y eksploatacyjne do drukarek atramentowych","Tusz Canon CLI-551 CMYK MultiPack iP7250\/MG5450\/MG6350","115.11",15,"23274"]
     [id_product] => 
     [sourceURL] => http://esklep.polcan.pl/pozycja_katalogowa.php?poz=23274
	*/	
	public  static function findCompatybileProducts($csvProducts){
		$db  = Db::getInstance();
		// identyfikacja po kodzie produktu 
		$kody = array(); 
		$kodyStr = '';
		foreach($csvProducts as $product){
			$row = json_decode($product['json'] , true);
			$kody[mb_strtolower($row[0])] = $row[6];
			$kodyStr.= '\''.pSql($row[0]).'\',';
		} 
		$kodyStr = rtrim( $kodyStr , ',');	
		$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  ,  LOWER( p.`kod_produktu` )  AS lowkod  FROM `' ._DB_PREFIX_.'product` p 
		WHERE p.`kod_produktu` IS NOT NULL AND  p.`kod_produktu` in ('.$kodyStr . ')';
		
		$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  ,  LOWER(  ps.`synonym` )  AS lowkod  
					FROM `' ._DB_PREFIX_.'product` p 
					LEFT JOIN a_product_synonym ps  ON ps.kod_produktu  = p.kod_produktu 
					WHERE p.`kod_produktu` IS NOT NULL AND  ps.`synonym` in ('.$kodyStr . ')
					GROUP BY p.`kod_produktu` 
					';			
		
		$res =  $db->executeS($sql);
		// wstawienie do ps_importerps_product_map
		//echo '<pre>'.print_r($res, true).'</pre>'; 	die;	
		
		$values = '';
		if($res){
			foreach($res as $row){
				$values .= '(\'PolcanCSV\','. $kody[$row['lowkod']] .','. $row['id_product']. '),' ;			
			}
			$values = rtrim( $values , ',');	
			$sql = 'INSERT INTO `'._DB_PREFIX_.'importerps_product_map`(`classname`, `id_xml`, `id_product`) VALUES '.$values;
			$res =  $db->execute($sql);
		}

		//echo '<pre>'.print_r($res, true).'</pre>'; 
		//die;
	}
	
	
	public static $rowCount = 7;
	public $csvArray;
	public $prestaProductsMap = array();
	public $drukujesz24_noweCategory;
	
		
	public $dbExt;
	public function __construct($token){
		parent::__construct($token);
		$this->dbExt = DbExt::getDb();	
		$cat  = Category::searchByName(Configuration::get('PS_LANG_DEFAULT'), 'drukujesz24_nowe') ;		
		$this->drukujesz24_noweCategory = $cat[0]['id_category'];
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
	
	function mb_trim($str){		
		return preg_replace("/(^\s+)|(\s+$)/u", "", $str);	
	}
	
	public function parse(){	
		
	//	$Products = Product::getProducts(1, 0, null, 'name', 'ASC', false, (bool) 1);
	//	echo '<pre>'.print_r($Products,true).'</pre>'; 
	//	die;		
				
		$db  = Db::getInstance();				
		$this->dbExt-> exec("set names utf8");		
		$defaultLanguage = intval(Configuration::get('PS_LANG_DEFAULT'));
		


		$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  FROM `' ._DB_PREFIX_.'product` p WHERE p.`kod_produktu` IS NOT NULL';
		$res =  $db->executeS($sql);
		$str = '';
		foreach($res as $row){
			$str.= '\'' . pSQL($row['kod_produktu']) . '\',';
		}
		if(!empty($str)){
			$str = rtrim($str , ',');
			//$sql =  'SELECT  p.`kod_produktu`  FROM `a_product` p WHERE p.`kod_produktu`  IN ( ' .$str. ')';
			
			$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  FROM `' ._DB_PREFIX_.'product` p 						
					WHERE p.`kod_produktu` IS NOT NULL 
					AND  p.`kod_produktu` NOT IN (SELECT `kod_produktu`  FROM `a_product`)';
			$result =  $db->executeS($sql);
			//if(count($result)!== count($res) ){
			if(count($result)){	
					echo '<pre>'.print_r($sql ,  true) . print_r($result ,  true).'</pre>';//  die;
					echo 'w ps_product są produkty niepolączone z produktami katalogu Błąd';
					die;
			}			
		}
		$insertStr = '';											
		foreach($this->csvArray as $key => &$row){
			$kod  = $this->mb_trim($row[0]);
			if(empty($kod)){
				$this->errorMsg .= 'Linia '. ($key+1). ' pliku pusy kod produku <br/>';
				die;
			}			
			$row[0] = $kod;
			$row[3] = strtr($row[3], '<>;=#{}', "       "); 	
			$row[5] = intval($row[5]);
			$row[5] = max(0 ,$row[5]);
			$row[5] = min(40 , $row[5]);
			/*if($row[5] === 'stale')
				$row[5] = 40;
			else	
				$row[5] = 20;
				*/ 
			$insertStr .= '(\''. pSQL($row[0]).'\',\''.pSQL($row[1]).'\',\''.pSQL($row[3]).'\','.$row[4].','.$row[5].','.$row[6].'),';
			unset($this->csvArray[$key]);
			
		}		
		
		
		$insertStr = rtrim($insertStr , ',');
		$sql = 'DROP  TABLE IF EXISTS `tmptable`; 
			CREATE TABLE `tmptable`  (
					`kod_xml` varchar(32) NOT NULL,					
					`producer` varchar(32) NOT NULL,
					`name` varchar(128) NOT NULL,
					`wholesale_price` decimal(20,6) NOT NULL,
					`quantity` int(10) NOT NULL,					
					`xml_id` int(11) NOT NULL,					
					PRIMARY KEY (`xml_id`)
			) ENGINE=MEMORY DEFAULT CHARSET=utf8;
			INSERT INTO `tmptable`(`kod_xml`, `producer`, `name`, `wholesale_price`, `quantity`, `xml_id`) VALUES '.		$insertStr .';'
			;
		$res =  $db->execute($sql);	
		$insertStr ='';
		$sql = 'DROP  TABLE IF EXISTS `tmpmerge`; 
		CREATE TABLE `tmpmerge` (
				`presta_id` int(10)  DEFAULT NULL,
				`kod_produktu` varchar(32) DEFAULT NULL,				
				`kod_xml` varchar(32) NOT NULL,					
				`producer` varchar(32) NOT NULL,
				`name` varchar(128) NOT NULL,
				`wholesale_price` decimal(20,6) NOT NULL,
				`quantity` int(10) NOT NULL,
				`xml_id` int(11) NOT NULL,					
				PRIMARY KEY (`xml_id`)
		) ENGINE=MEMORY DEFAULT CHARSET=utf8 ;		
		INSERT INTO `tmpmerge` 
			SELECT  p.`id_product`, p.`kod_produktu` ,  t.*   FROM `tmptable` AS t		
				LEFT JOIN `a_product_synonym` AS s ON t.`kod_xml` = s.`synonym`
				LEFT JOIN `' ._DB_PREFIX_.'product` AS p ON p.`kod_produktu` = s.`kod_produktu`	;
		';
		$res =  $db->execute($sql);	
		$sql = 'DROP  TABLE IF EXISTS `tmptable`;';
		$res =  $db->execute($sql);	

		$sql = 'SELECT * FROM `tmpmerge` ORDER BY `kod_xml` ASC';
		$res =  $db->executeS($sql);
		
		
		$productsMap = array();
		foreach($res as $k=>$row){
			$productsMap[$row['xml_id']] =  $row;
			unset($res[$k]);
		}

		// sprawdzam czy są duplikaty w NOWO DODAWANYCH JAK SĄ to alert  
		$headArray = array();		

		$sqls = array( 'SELECT `kod_xml`,  GROUP_CONCAT(`xml_id`) AS ids ,  COUNT(*) c FROM `tmpmerge` WHERE  `kod_produktu` IS NULL   GROUP BY `kod_xml` HAVING c > 1 ' , 		
				'SELECT `kod_produktu`,  GROUP_CONCAT(`xml_id`) AS ids ,  COUNT(*) c FROM `tmpmerge` WHERE  `kod_produktu` IS NOT NULL   GROUP BY `kod_produktu` HAVING c > 1 ' );
		
		
		foreach($sqls as $sql){
			$mergeArray =  $db->executeS($sql);													
			foreach($mergeArray as $k => $row){
				$ids  = explode(',' ,$row['ids']);
				$curentId  = array_pop($ids);
				$price  =  $productsMap[$curentId]['wholesale_price'];
				foreach($ids as $id){
					if($productsMap[$id]['wholesale_price'] < $price){
						$price = $productsMap[$id]['wholesale_price'];						
						$productsMap[$curentId]['disabled'] = true;
						$headArray[] = $productsMap[$curentId];
						unset($productsMap[$curentId]);
						$curentId = $id;
					}else{
						$productsMap[$id]['disabled'] = true;
						$headArray[] = $productsMap[$id];
						unset($productsMap[$id]);
					}					
				}						
				$headArray[] = $productsMap[$curentId];
				unset($productsMap[$curentId]);
			}
		}
		foreach($productsMap as $curentId => $row){
			if(!empty($row['kod_produktu'])){
				$headArray[] = $productsMap[$curentId];
				unset($productsMap[$curentId]);
			}			
		}
		foreach($productsMap as $curentId => $row){		
			$headArray[] = $productsMap[$curentId];
			unset($productsMap[$curentId]);		
		}		
		$sql = 'DROP  TABLE IF EXISTS `tmpmerge`;';
		$res =  $db->execute($sql);		
		

		
		foreach($headArray as $key => $row){			
			$attributesCombination = array();
			$parserProduct  = array();
			$parserProduct['xml_kod_produktu'] = $row['kod_xml'];
			if(empty($row['kod_produktu']))
				$parserProduct['kod_produktu'] = $row['kod_xml'];
			else 	
				$parserProduct['kod_produktu'] = $row['kod_produktu'];
			$parserProduct['attributesCombination'] = $attributesCombination;			
			$parserProduct['quantity']  = $row['quantity'];
			$parserProduct['wholesale_price'] =  $row['wholesale_price'];
			
			$parserProduct['id_category'] = $this->drukujesz24_noweCategory;

			$parserProduct['on_sale'] = (int)($parserProduct['quantity']>0? true : false);
			$parserProduct['specyfic_price'] = $parserProduct['wholesale_price'];
			$parserProduct['imgs'] = array();
			
			$producer  = trim((string) $row['producer']);
			if(!empty($producer)){
				$producer  = $this->getManufacturerNameMap($producer);
				$parserProduct['manufactuer'] = $producer;
				$this->manufacturers[$producer] = 0;
			}
						
			$parserProduct['name'][$defaultLanguage ] = strtr($row['name'], '<>;=#{}', "       "); 	
			$parserProduct['description'][$defaultLanguage ] = '';
			$parserProduct['short_description'][$defaultLanguage] = '';
						
			$parserProduct['reference'] = $parserProduct['kod_produktu'];
			$parserProduct['ean13'] =  '';
			
			if(!empty($row['presta_id']))		
				$parserProduct['prestaId'] = $row['presta_id'];		
			if(!empty($row['disabled']))			
				$parserProduct['disabled'] = true;
			$parserProduct['xml_id'] = $row['xml_id'];	
			
			$parserProduct['attributesxml'] = array();
			$parserProduct['featuresIds'] = array();
					
			$this->products[] = $parserProduct;
			unset($headArray[$key]);			
		}		
		return;
			
		
		
		
		echo count($productsMap);
		
		echo '<pre>'.print_r($headArray, true).'</pre>';
		echo '<br>'.memory_get_peak_usage(true)/(1024*1024);
		echo '<br>'.memory_get_usage(true)/(1024*1024);		
		DIE;
						
				
				
		
		
		$kodyProduktu = array();
		$insertStr = '';
		echo '<pre>'.print_r($this->csvArray, true).'</pre>';
		die;
		$index = 0; 
		foreach($this->csvArray as $key => $row){
			$kod  = $this->mb_trim($row[0]);
			if(empty($kod)){
				$this->errorMsg .= 'Linia '. ($key+1). ' pliku pusy kod produku <br/>';
				die;
			}
			$kodyProduktu[] = $index;
			$insertStr .= '('. $index.',\''. pSQL($kod) . '\'),';
			
			$attributesCombination = array();
			$parserProduct  = array();
			$parserProduct['xml_kod_produktu'] = $kod;
			$parserProduct['kod_produktu'] = $kod;
			$parserProduct['attributesCombination'] = $attributesCombination;			
			$parserProduct['quantity']  =  10;				
			$parserProduct['wholesale_price'] =  $row[4];

			
			$parserProduct['id_category'] = $this->drukujesz24_noweCategory;

			$parserProduct['on_sale'] = (int)($parserProduct['quantity']>0? true : false);			
			$parserProduct['specyfic_price'] = $parserProduct['wholesale_price'];
			$parserProduct['imgs'] = array();
			
			$producer  = trim((string) $row[1]);
			if(!empty($producer)){
				$producer  = $this->getManufacturerNameMap($producer);
				$parserProduct['manufactuer'] = $producer;
				$this->manufacturers[$producer] = 0;
			}
						
			$parserProduct['name'][$defaultLanguage ] = strtr($row[3], '<>;=#{}', "       "); 	
			$parserProduct['description'][$defaultLanguage ] = '';
			$parserProduct['short_description'][$defaultLanguage] = '';
						
			$parserProduct['reference'] = $kod;
			$parserProduct['ean13'] =  '';
			
			//$parserProduct['tags']='';
			
			$this->products[] = $parserProduct;
			$index++;
			//if($index>12) break;			
			
		}
		echo '<pre>'.print_r($this->products, true).'</pre>';
		die;
		
		$insertStr = rtrim($insertStr , ',');

		$sql = 'DROP  TABLE IF EXISTS `tmptable`; 
			CREATE TABLE `tmptable`  (
					`id` int(11) NOT NULL,										
					`kod` varchar(32) NOT NULL,					
					PRIMARY KEY (`kod`)
			) ENGINE=MEMORY DEFAULT CHARSET=utf8;	
			INSERT INTO `tmptable` (`id` , `kod`) VALUES '.		$insertStr .';'
			;
		$res =  $db->execute($sql);	
				
		$sql =  'SELECT  p.`id_product`, p.`kod_produktu` ,  t.`id`    FROM `tmptable` AS t		
				LEFT JOIN `a_product_synonym` AS s ON t.`kod` = s.`synonym`
				LEFT JOIN `' ._DB_PREFIX_.'product` AS p ON p.`kod_produktu` = s.`kod_produktu`
				WHERE p.`kod_produktu` IS NOT NULL';		
		$res =  $db->executeS($sql);	
		$indexToRowMap = array();
		$mergeArray = array();
		foreach($res as $row){
			$product = $this->products[$row['id']];
			$product['kod_produktu']  = $row['kod_produktu']; 
			$product['reference'] = $row['kod_produktu'];
			$product['prestaId'] = $row['id_product'];
			$this->products[$row['id']] = $product;
			$mergeArray[$row['kod_produktu']][] = $row['id'];
		}
		$sql = 'DROP  TABLE IF EXISTS `tmptable`;';
		$db->execute($sql);	
		
		//echo '<br>$this->products'.count($this->products);
		
		$headArray = array();
		$notMergeArray = array();
		// łącznie produktów po cenach 
		foreach($mergeArray as $key => $ids){			
			if(count($ids) > 1){				
				$curentId  = array_pop($ids);
				$price  =  $this->products[$curentId]['wholesale_price'];
				foreach($ids as $id){
					if($this->products[$id]['wholesale_price'] < $price){
						$price = $this->products[$id]['wholesale_price'];						
						$this->products[$curentId]['disabled'] = true;
						$headArray[] = $this->products[$curentId];
						unset($this->products[$curentId]);
						$curentId = $id;
					}else{
						$this->products[$id]['disabled'] = true;
						$headArray[] = $this->products[$id];
						unset($this->products[$id]);
					}					
				}
				$headArray[] = $this->products[$curentId];
				unset($this->products[$curentId]);
			}else{
				$curentId  = array_pop($ids);
				$notMergeArray[]  = $this->products[$curentId];
				unset($this->products[$curentId]);
			}
		} 		
		foreach($notMergeArray as $v){
			$headArray[] = $v; 
		}
		foreach($this->products as $v){			
			$headArray[] = $v; 
		}
		$this->products = 	$headArray;
return;
/*		foreach($this->products as &$v){			
			$v ['disabled'] = true;
		}
*/		
		echo '<br>$this->products'.count($this->products);
		die;		
		echo '<pre>'.print_r($this->products, true).'</pre>';
		die;
		return;
		echo '<br>$this->products'.count($headArray);
	echo '<br>$this->products'.count($this->products);
		echo '<pre>'.print_r($this->products, true).'</pre>';		
		//products[key]['prestaId'] =prestaProductsMap[key]; 
		
		echo '<br>'.memory_get_peak_usage(true)/(1024*1024);
		echo '<br>'.memory_get_usage(true)/(1024*1024);
		
		die;


				
		$str = '';
		$kodyProduktuMap = array();
		foreach($kodyProduktu as $v ){
			$str.= '\'' . pSQL($v) . '\',';
			$kodyProduktuMap[$v] = $v;
		}	
		$str = rtrim($str , ',');
		$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  ,  GROUP_CONCAT(s.`synonym`  SEPARATOR \'/delimiter/\') AS synonym
			FROM `' ._DB_PREFIX_.'product` p 
			LEFT JOIN `a_product_synonym` AS s ON p.`kod_produktu` = s.`kod_produktu`		
			WHERE p.`kod_produktu` IN (' . $str . ')
			GROUP BY p.`kod_produktu`
			';	
			
		echo $sql;		
		$res =  $db->executeS($sql);
		// w res są te które są polączone 		
		echo '<br />produkty połaczne z katalogiem: '. count($res).'</br>';
		$synonymMap = array();
		foreach($res as $v){
			$syn  = explode('/delimiter/' , $v['synonym']);
			foreach($syn as $s){
				//$synonymMap[$s] = 
			}			
		}
		
		
		
		
		/*
		$prestacount = count($res);
		
		
		$sql =  'SELECT  p.`kod_produktu`  FROM `a_product`';
		$res =  $db->executeS($sql);
		
		
		$c = 0;  
		foreach($res as $row){
			$c++; 
		}
		echo $c;
		*/
		
		echo count($kodyProduktu).'<br />';
		echo '<pre>'.print_r($this->products, true).'</pre>';
		echo '<pre>'.print_r($kodyProduktu, true).'</pre>';
		die;
		
		
		$sql =  'SELECT  p.`id_product`, p.`kod_produktu`  FROM `' ._DB_PREFIX_.'product` p WHERE p.`kod_produktu` in (SELECT c.`kod_produktu` FROM a_product c )';
		
			
		
		if( ($prestaProducts =  $db->ExecuteS($sql)) === false){
			echo $db->getMsgError();
			return;
		}
		
		$prestaProductsMap = array();
		foreach($prestaProducts as $row){
			if(empty($row['kod_produktu'])) continue; 
			$this->prestaProductsMap[$row['kod_produktu']] = $row['id_product']; 			
		}
		
		//print_r($this->prestaProductsMap);
		
/*		
		[0] => LT300CL
		[1] => Brother
		[2] => Akcesoria
		[3] => Brother LT-300CL szuflada na papier 500 arkuszy
		[4] => 623.25
		[5] => 1-2 dni
		[6] => 13836
*/		

		print_r($this->csvArray);
		

		//echo '<pre>' . print_r($this->products , true) . '</pre>';
		return;		
		
		
		$stmt  =  $this->dbExt->query('SELECT  a.* ,  p.*
				FROM `a_product`  AS a 				
				LEFT JOIN products AS p ON a.`id_sklep` =  p.`products_id` 
				');

		$stmt->execute();
		$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
	/*	$p = array();
		foreach($products as $key=>$product){
			if($product['kod_produktu'] =='Q2610A'){
				$p[] = $product;
			}
			if($product['kod_produktu'] =='Q2610D'){
				$p[] = $product;
			}
		}
		$products = $p;		
		/*$max =9;
		$count = 0;
		foreach($products as $key=>$product){
			if($count> $max )
				unset($products[$key]);
				$count++;
			//if($product['kod_produktu'] =='Q2610A')	
		}
		*/
		
		$stmt  =  $this->dbExt->query('SELECT c.`categories_id`  ,  c.`parent_id`  ,  cd.`categories_name`
		FROM `categories`  AS c
		LEFT JOIN   categories_description AS cd ON c.`categories_id` =  cd.`categories_id` and cd.`language_id` = 1'
		);
		$stmt->execute();
		$categories  = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$categoriesMap = array();		
		$this->categories = array();		
		foreach($categories  as $row){
			$categoriesMap[$row['categories_id']] = $row;
			
			$this->categories[$row ['categories_id']]['parent']  =  $row['parent_id']; 
			$this->categories[$row ['categories_id']]['name']  =  $row ['categories_name']; 		
		}
		//echo '<pre>'.print_r($categoriesMap, true).'</pre>';
		$tmpCats =  $this->categories;
		//self::xmlCategoriesTree($this->categories);
		self::getSubCategories($this->categoriesTree ,$tmpCats, 0 );
		if(!empty($tmpCats)){
			echo 'error categories not in tree ' ;
			echo '<pre>'.print_r( $tmpCats, true),'</pre>';
			die;
		}
		
		//echo '<pre>'.print_r($this->categoriesTree, true).'</pre>';
		//echo '<pre>'.print_r($this->categories, true).'</pre>';
		//echo '<pre>'.print_r($this->categoriesTree, true).'</pre>';
		$this->synchroniseCategories();
		//echo '<pre>'.print_r($this->categories, true).'</pre>';
		
		//die;
		//unset($categories);
		
		$stmt  =  $this->dbExt->query('SELECT m.`manufacturers_id` , m.`manufacturers_name` from manufacturers as m');
		$stmt->execute();
		$manufactuers  = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$manufactuersMap = array();
		foreach($manufactuers  as $row){
			$manufactuersMap[$row['manufacturers_id']] = $row['manufacturers_name'];
		}
		//unset($manufactuers);
		
				
		$stmt  =  $this->dbExt->query('SELECT * from products_to_categories '); 
		$stmt->execute();
		$prodCategories  = $stmt->fetchAll(PDO::FETCH_ASSOC);	
		
		$prodCategoriesMap = array();
		foreach($prodCategories  as $row){
			$prodCategoriesMap[$row['products_id']] = $row ['categories_id'];
			
		}
		//print_r($prodCategoriesMap);
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
			$parserProduct['reference'] = $product['kod_produktu']; 
			$parserProduct['ean13'] =  ''; 
			
		
			$producer  =  $manufactuersMap[$product['manufacturers_id']]; 
			if(!empty($producer)){
				$producer  = $this->getManufacturerNameMap($producer);
				$parserProduct['manufactuer'] = $producer; 
				$this->manufacturers[$producer] = 0;
			}									
		
		
			$parserProduct['name'][1] = $product['name']; 
			$parserProduct['description'][1] = $product['description']; 

			$tags  = self::getTagsString($parserProduct['description'][1]); 
			if(strlen($tags))
				$parserProduct['tags'] = $tags; 															
			$parserProduct['short_description'][1] = '';
			
			
			$parserProduct['category'] = $categoriesMap[  $prodCategoriesMap[ $product ['products_id']] ]['categories_name'];
			$parserProduct['id_category'] = $this->categories[$prodCategoriesMap[ $product ['products_id']] ]['id_category'];
			
			
			//echo '<pre>'.print_r($parserProduct, true).'</pre>';
			//die;
			
			
			$this->products[(String)$product['kod_produktu']] = $parserProduct; 				
			
			unset($products[$key]);			
		}
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
?>
