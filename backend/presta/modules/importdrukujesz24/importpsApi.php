<?php
set_time_limit ( 180 );
	class ImportPsApi{
	
		public $_errors;
		public $classname; 
		public $startime; 
		public $global_provision;
		
		
		

		
		public function __construct($classname){
			$this->classname = $classname; 
			$this->startime = microtime(true);
			$this->global_provision = Configuration::get('PS_IMPORTPS_GLOBAL_PROVISION');
			
			
		}


		public function getOueryDialog($null){
			include_once('queryPrice.php');
			$prestaObj = new queryPrice();
			$c =  $prestaObj->getContext();
			$resp = array('email' =>$c->customer->email , 'id'=>$c->customer->id , );
			$a  = $c->customer->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
			if($a){
				$resp['phone'] = $a[0]['phone'];
			}else{
				$resp['phone'] = null;
			}	
			$resp['name'] = trim(((string)$c->customer->firstname).' '.((string)$c->customer->lastname) );
			//print_r($c->customer);
			echo json_encode($resp);			
		}
		
		
		public function sendOueryDialog($param){
			include_once('queryPrice.php');
			$prestaObj = new queryPrice();
			$prestaObj->sendOueryDialog($param);
			//print_r($param);			
		}
		
		
		public function GenerateAssociatedProducts($null){
			
			$db = Db:: getInstance();	
			
			$sql = 'SELECT pp.`id_product` , pp.`kod_produktu`  FROM `'._DB_PREFIX_.'product` AS pp
			left join `a_product` as p ON pp.`kod_produktu` = p .`kod_produktu`   			
			WHERE pp.`kod_produktu` IS NOT NULL and pp.`active` = 1 ' ;			
			
			$sql = 'SET SESSION group_concat_max_len = 1000000;';
			$ret =  $db->execute($sql);	
			
			$sql = 'SELECT  GROUP_CONCAT(  pp.`id_product`) as  id_products FROM `'._DB_PREFIX_.'product` AS pp			
			WHERE pp.`kod_produktu` IS NOT NULL AND pp.`active` = 1 ;' ;			
						
			$idProductStr = '';
			$ret =  $db->executeS($sql);
					
			$idProductStr =  $ret[0]['id_products'];
			
			//echo count(explode(',', $idProductStr));
			
			if(!empty($idProductStr)){
				$sql = 'SELECT pp.`id_product` ,  GROUP_CONCAT(pri.`id`) AS printersStr   FROM `a_printer`  as pri
				LEFT JOIN `a_oem_printer` AS op ON op.`printer_id` = pri.`id`
				LEFT JOIN `a_product_oem` AS po ON po.`oem` =  op.`oem`					
				LEFT JOIN `a_product` AS p ON p.`kod_produktu` = po .`kod_produktu`   			
				left join `'._DB_PREFIX_.'product` as pp ON pp.`kod_produktu` = p .`kod_produktu`  
				WHERE pp.`id_product` IN ('.$idProductStr.')
				GROUP BY pp.`id_product`';				
				
				//echo  $sql;
				
				$res =  $db->executeS($sql);
				
				//print_r($res);  
				$printerProductMap = array();
				foreach($res as $row){
					if(!empty($row['printersStr'])){
						$printers = explode(',', $row['printersStr']);
						foreach($printers as $printer){
							$printerProductMap[$printer][] = $row['id_product'];
							
						}
						//$vals  = array_fill(0,count($printers),$row['id_product']);
						//array_combine($printers, $vals);
					}
					
					
				}
				$sql = 'DELETE FROM `'._DB_PREFIX_.'accessory` WHERE `id_product_1` IN ( '.  $idProductStr . ' )';
				$r =  $db->execute($sql);
				
				
				$values = array();
				foreach($printerProductMap as $printerId => $products){
					$size = count($products);
					if($size >1){
						foreach($products as $p){
							foreach($products as $pr){
								if($p == $pr) continue;
								$values['('.$p.','. $pr.')'] = null;
								//$values .= '('.$p.','. $pr.'),';
							}
						}						
					}					
				}
				$values = implode(',',array_keys($values));
				//print_r($values);
				//die;
				if(!empty($values)){
					$values = rtrim($values , ',');
					$sql = 'INSERT INTO `'._DB_PREFIX_.'accessory`   (`id_product_1`, `id_product_2`)  VALUES ' .$values;
					$r =  $db->execute($sql);
				}
				
				
				/*
				echo ($values);
				print_r($printerProductMap);
				
				die;
				$sql = 'SELECT po.`oem`   FROM `a_product_oem` AS po
				left join `a_product` AS p ON p.`kod_produktu` = po .`kod_produktu`   			
				left join `'._DB_PREFIX_.'product` as pp ON pp.`kod_produktu` = p .`kod_produktu`  
				WHERE pp.`id_product` IN ('.$idProductStr.')';
				$res =  $db->executeS($sql);						
				print_r($res);
				*/				
			}
			echo json_encode(array('status' =>0  , 'data'=>'Ok'));
			die;

			
			
			
			$sql = 'SELECT pp.`id_product` , pp.`kod_produktu`  FROM `'._DB_PREFIX_.'product` AS pp
			left join `a_product` as p ON pp.`kod_produktu` = p .`kod_produktu`   			
			WHERE pp.`kod_produktu` IS NOT NULL and pp.`active` = 1 ' ;			
			
			die; 
			
			$toDel = array();
			$toAdd = array();
			foreach($ret as $row){				
				$toDel[] = $row['id_product'];
				$sql = '
				SELECT pp.`id_sklep` , '.$row['id_product'].' as id_product  from `a_product` as pp 
				LEFT JOIN `a_product_oem` AS ppo ON ppo.`kod_produktu` =  pp.`kod_produktu`
				LEFT JOIN `a_oem_printer` AS ppop ON ppop.`oem` = ppo.`oem`
				WHERE ppop.`printer_id` IN (				
				SELECT  pri.`id`   FROM  `a_printer` as pri	
				LEFT JOIN `a_oem_printer` AS op ON op.`printer_id` = pri.`id`
				LEFT JOIN `a_product_oem` AS o ON o.`oem` =  op.`oem`				
				LEFT JOIN `a_product` AS p ON p.`kod_produktu` = o.`kod_produktu`
				WHERE p.`id_sklep` = '.$row['id_product'].') AND pp.`id_sklep` <> '.$row['id_product']				
				;
				$result =  $db->executeS($sql);			
				$toAdd[] = $result;
				
				
				//print_r($result);				
			}
			// usuwam 			
			$sql = 'DELETE FROM `'._DB_PREFIX_.'accessory` WHERE `id_product_1` IN ( '.implode(',',$toDel) . ' )';
			$r =  $db->execute($sql);			
			
			// wstawiam
			$sql = 'INSERT INTO `'._DB_PREFIX_.'accessory`   (`id_product_1`, `id_product_2`)  VALUES ';
			$doAction  = false;
			foreach($toAdd as $arrayOfRows){				
				foreach($arrayOfRows as $row){
					$sql .= '('. $row['id_product'].','. $row['id_sklep'] .'),';
					$doAction = true;
				}	
			}
			if($doAction === true){
				$sql = rtrim($sql , ',');
			}
			$r =  $db->execute($sql);			
			echo json_encode(array('status' =>0  , 'data'=>'Ok'));
			
			die;
			
			
				$sql = 'SELECT  p.`kod_produktu` , p.`id_sklep` ,   pri.*  FROM  `a_product`  AS p
				LEFT JOIN `a_product_oem` AS o ON o.`kod_produktu` =  p.`kod_produktu`
				LEFT JOIN `a_oem_printer` AS op ON op.`oem` = o.`oem`
				LEFT JOIN `a_printer` AS pri ON pri.`id` = op.`printer_id`
				WHERE p.`id_sklep` = '.$row['id_product'];				
				$result =  $db->executeS($sql);			
				print_r($result);
			
			
					
			
			$sql =  'UPDATE `'._DB_PREFIX_.'product` SET   `active` = 0  WHERE  `id_product`  in
			(SELECT id_product FROM   '._DB_PREFIX_.'importerps_product_map WHERE classname = \''.pSQL($this->classname).'\'' .$set.  ' ) ' ;	
			
			
		}
		
		
		public function addPrinterForOem($obj){
			$db = Db:: getInstance();				
			$sql = 'SELECT `id`   FROM a_printer WHERE `model`=  \''.pSQL($obj['model']).'\' AND `producer_id` = '.$obj['producer_id'];
			$ret =  $db->executeS($sql);			
			if(!count($ret)){
				// dodaje drukarkę 
				$sql = 'INSERT INTO `a_printer`(`model` , `producer_id`) VALUES ( \''.pSQL($obj['model']).'\'  , '.$obj['producer_id'].' )';
				$db->execute($sql);
				$id = $db->Insert_ID();
			}else{
				$id = $ret[0]['id'];				
			}
			$sql = 'INSERT INTO `a_oem_printer`(`oem` , `printer_id` ) VALUES ( \''.pSQL($obj['oem']).'\'  , '.$id.')';
			$ret = $db->execute($sql);
			echo json_encode(array('status' =>0  , 'data'=>array('id'=>$id ,  'model'=>$obj['model'])));
			
		}
		
		
		public function delPrinterFromOem($obj){
			$db = Db:: getInstance();
			$sql = 'DELETE FROM `a_oem_printer`  WHERE `oem`  = \''.pSQL($obj['oem']).'\'  AND `printer_id` =   \''.pSQL($obj['id']).'\'';
			$ret =  $db->execute($sql);
			echo json_encode(array('status' =>0  , 'data'=>$ret));
		}		
		
		
		public function getPrintersForOem($obj){
			$db = Db:: getInstance();
			
			
			$sql = 'SELECT p.`id` , p.`model` ,  pr.`short_name`   FROM a_printer AS p
					LEFT JOIN a_producer AS pr ON pr.`producer_id` = p.`producer_id`
					LEFT JOIN  a_oem_printer AS po ON po.`printer_id` = p.`id`    
					WHERE po.`oem` =   \''.pSQL($obj['oem']).'\'';
			$ret =  $db->executeS($sql);
			echo json_encode(array('status' =>0  , 'data'=>$ret));
			
		}

		public function delKodProductOem($obj){
			$db = Db:: getInstance();
			$sql = 'DELETE FROM `a_product_oem`  WHERE `kod_produktu`  = \''.pSQL($obj['kod_produktu']).'\'  AND `oem` =   \''.pSQL($obj['oem']).'\'';
			$ret =  $db->execute($sql);
			echo json_encode(array('status' =>0  , 'data'=>$ret));
		}
		
		
		public function addKodProductOem($obj){
			$db = Db:: getInstance();
			/// czy oem istnieje 			
			$sql  = 'SELECT  * FROM a_oem_synonym WHERE `synonym`  =  \''.pSQL($obj['oem']).'\'';
			$ret =  $db->executeS($sql);
			//print_r( $ret);
			if(!count($ret)){				
				// dodaje oem 
				$sql = 'INSERT INTO `a_oem_synonym`(`synonym`, `oem`) VALUES ( \''.pSQL($obj['oem']).'\' , \''.pSQL($obj['oem']).'\')';
				$ret =  $db->execute($sql);
			}else{
				$obj['oem'] = $ret[0]['oem'];				
			}
			try {																	
				$sql = 'INSERT INTO `a_product_oem`(`kod_produktu`, `oem`) VALUES ( \''.pSQL($obj['kod_produktu']).'\' , \''.pSQL($obj['oem']).'\')';
				$ret =  $db->execute($sql);
			} catch (Exception $e) {
				$sql = 'SELECT * FROM `a_product_oem`  WHERE  `kod_produktu`= \''.pSQL($obj['kod_produktu']).'\'  AND  `oem` =  \''.pSQL($obj['oem']).'\'';
				$ret =  $db->executeS($sql);
				if(count($ret) ==1){
					echo 'ten produkt ma już zadany Oem';
				}else{
					echo $e->getMessage();
				}
				return;
			}	
			echo json_encode(array('status' =>0  , 'data'=>$ret));
		}
		
		public function addKodProductSynonym($obj){
			
			$db = Db:: getInstance();
			try {
				$sql = 'INSERT INTO `a_product_synonym`(`kod_produktu`, `synonym`) VALUES ( \''.pSQL($obj['kod_produktu']).'\' , \''.pSQL($obj['synonym']).'\')';			
				$ret =  $db->execute($sql);
			} catch (Exception $e) {
				$errStr = 'Błąd<br>';
				$sql = 'SELECT  * FROM  `a_product_synonym` WHERE  `synonym` = \''.pSQL($obj['synonym']).'\'';			
				$ret =  $db->executeS($sql);
				if(count($ret) == 1){
					$errStr .= 'Synonim:  '.$obj['synonym'].' juz istnieje w a_product_synonym: '.$ret[0]['synonym'] ;
					$errStr .= '<br>kod produktu: '. $ret[0]['kod_produktu'];
					$sql = 'SELECT `id_product` FROM  ps_product WHERE `kod_produktu` = \''.pSQL($ret[0]['kod_produktu']).'\'';
					$ret1 =  $db->executeS($sql);
					if(count($ret1) == 1){
														
							$errStr .= '<br><a target="_blank1"  style="color:blue" href="'.$obj['link'].$ret1[0]['id_product'].'">link do produktu </a>';
							//Baza produktów zawiwiera dokładnie 1  kodu produktu: '.$ret[0]['kod_produktu']; 															
					}else{
						if(count($ret1) == 0){
							$errStr .= '<br>Baza produktów nie zawiwiera kodu produktu: '.$ret[0]['kod_produktu']; 								
							$errStr .= '<br>Błąd jest w a_product_synonym';
						}else{
							$errStr .= '<br>Baza produktów zawiwiera >1 prduktów o kodu produktu: '.$ret[0]['kod_produktu']; 								
							$errStr .= '<br>Błąd jest w ps_product';
						}						
					}
					echo $errStr;
				}
				return;				
				//echo print_r($ret , true);
				// prawdopodobnie błąd 
				// echo 'Caught exception1: ',  $e->getMessage(), "\n";				
			}
			echo json_encode(array('status' =>0  , 'data'=>$ret));			
		}

		public function delKodProductSynonym($obj){
				
			$db = Db:: getInstance();
			$sql = 'DELETE FROM `a_product_synonym` WHERE  `synonym` =  \''.pSQL($obj['synonym']).'\'';
			$ret =  $db->execute($sql);
			echo json_encode(array('status' =>0  , 'data'=>$ret));
		}
		
		
		public function togleZamiennik($obj){			 
			//print_r($obj); die;
			$db = Db:: getInstance();
			if( $obj['zamiennik'] == 'false'){
				$sql = 'UPDATE `a_product` SET `zamiennik` = NULL   WHERE  `kod_produktu` =  \''.pSQL($obj['kod_produktu']).'\'';				 
			}else{
				$sql = 'UPDATE `a_product` SET `zamiennik` = 1  WHERE  `kod_produktu` =  \''.pSQL($obj['kod_produktu']).'\'';
			}
			$ret =  $db->execute($sql);
			echo json_encode(array('status' =>0  , 'data'=>$ret));
		}		
				
		public function addManufactuer(array $manufactuer){
			if (Manufacturer::getIdByName($manufactuer['name'])){
				echo 'Manufacturer '. $manufactuer['name'] .' just exist' ; 
				return;
			}
			$man = new Manufacturer(); 
			$man->name = $manufactuer['name']; 
			$man->active = 1;
			If( $man->add()){
				echo json_encode(array('status' =>0  , 'id'=>$man->id));
			}else{
				echo 'Error addManufactuer' ; 				
			}		
		}
		public function updateActive($param){
			$param = json_decode($param , true);
			$db  = Db::getInstance(); 
			
			// dostawczy 
			$sql =  'UPDATE `'._DB_PREFIX_.'product` SET   `id_supplier` = 0  WHERE  `id_product`  in
				(SELECT id_product FROM   '._DB_PREFIX_.'importerps_product_map group by `id_product` )' ;				
			$db->Execute($sql);			
			
			$sql =  'DELETE FROM  `'._DB_PREFIX_.'product_supplier` WHERE  `id_product`  in
				(SELECT id_product FROM   '._DB_PREFIX_.'importerps_product_map group by `id_product` )' ;				
			$db->Execute($sql);				
								
			$values = '';
			$suppliersMap = array();
			foreach($param['supliers'] as $supName => $products){
				if(! ($supId = Supplier::getIdByName($supName))){
					$sup  = new	Supplier();
					$sup->name = $supName;
					$sup->active = 1;
					$sup->save();
					$supId = $sup->id;
				}else{
					$sup  = new	Supplier($supId);
					$sup->active = 1;
					$sup->save();					
				}		
				$suppliersMap[$supName] = $supId;
				foreach($products as $p){
					$values .= '('.$p[0].','.$supId.',\''.pSql($p[1]).'\','.$p[2].',1),';					
				}				
			}
			//echo '<pre>'.print_r($values, true).'</pre>'; 
			//echo '<pre>'.print_r($param, true).'</pre>'; 
			//die;
			if(!empty($values)){
				$values  = rtrim($values, ',');
				$sql =  'INSERT IGNORE INTO `'._DB_PREFIX_.'product_supplier` (`id_product`, `id_supplier`, `product_supplier_reference`, `product_supplier_price_te`, `id_currency`) VALUES '.  $values  ;	
				$db->Execute($sql);				
			}
			foreach($param['defaultSupliers'] as $supName => $ids){
				$sql =  'UPDATE `'._DB_PREFIX_.'product` SET   `id_supplier` = '.$suppliersMap[$supName].'  WHERE  `id_product`  in	('. implode(',',$ids) .')' ;				
				$db->Execute($sql);							
			}
			
			// reszta	
				
			$sql =  'UPDATE `'._DB_PREFIX_.'product` SET   `active` = 0  WHERE  `id_product`  in
			(SELECT id_product FROM   '._DB_PREFIX_.'importerps_product_map group by `id_product` )' ;
			if(! $db->Execute($sql)){					
				echo $db->getMsgError();
				return;
			}
			if(count($param['active'])){
				$sql =  'UPDATE `'._DB_PREFIX_.'product` SET   `active` = 1  WHERE  `id_product`  in
				( ' .implode(',' ,$param['active']   ).  '  )' ;			
			}
			if(! $db->Execute($sql)){					
				echo $db->getMsgError();
				return;
			}	
			
			
			
			echo json_encode(array('status' =>0 ));	
			
			//print_r($param);die;
		}
		
		
		public function updateActiveManufactuers(array $param){
		
			$sql =  'SELECT m.`id_manufacturer`,  COUNT(*) AS count  FROM `'._DB_PREFIX_.'manufacturer` m , `'._DB_PREFIX_.'product` p 
					WHERE p.`id_manufacturer` = m.`id_manufacturer`  AND p.`active` = 1 GROUP BY m.`id_manufacturer`'; 
									
			$db  = Db::getInstance(); 						
			if( ($result =  $db->ExecuteS($sql)) === false){
				echo $db->getMsgError();
				return;
			}	
			
			$set = 	''; 			
			foreach($result as $row){
				$set .= '\''.pSQL($row['id_manufacturer']).'\','; 		
			}
						
			$sql =  'UPDATE `'._DB_PREFIX_.'manufacturer` SET   `active` = 0  WHERE 1';				
			if(! $db->Execute($sql)){					
				echo $db->getMsgError();
				return;
			}	
						
			if(strlen($set)){
				$set = substr($set , 0 ,  strlen($set)-1); 
				$set = ' id_manufacturer IN (' . $set . ' )';
				$sql =  'UPDATE `'._DB_PREFIX_.'manufacturer` SET   `active` = 1  WHERE '.$set;	
				if(! $db->Execute($sql)){					
					echo $db->getMsgError();
					return;
				}					
			}
			echo json_encode(array('status' =>0 ));					
		}		
		
		
		public function updateActiveCategory(array $param){
			
			$cat  = Category::searchByName(Configuration::get('PS_LANG_DEFAULT'), 'drukujesz24_nowe') ;		
		//echo 'max_execution_time'.ini_get('max_execution_time');die;
			$db  = Db::getInstance(); 
			$sql =  'SELECT c.`id_category`,  c.`id_parent` , c.`active` ,  COUNT(*) AS count  FROM `'._DB_PREFIX_.'category` c , `'._DB_PREFIX_.'category_product` cp , `'._DB_PREFIX_.'product` p   
				WHERE c.`id_category` = cp.`id_category` AND  cp.`id_product` = p.`id_product` AND  c.`id_category` <> 1 AND  p.`active` = 1 GROUP BY c.`id_category` ';
				
			if( ($activeProductsCategories =  $db->ExecuteS($sql)) === false){
				echo $db->getMsgError();
				return;
			}	
			
			foreach($activeProductsCategories as $k=>$c){
				if($cat[0]['id_category'] == $c['id_category'])
					unset($activeProductsCategories[$k]);
			}							
			$sql =  'SELECT c.`id_category`,  c.`id_parent` , c.`active` FROM `'._DB_PREFIX_.'category` c WHERE c.`id_category` <> 1 ' ;
			if( ($categories =  $db->ExecuteS($sql)) === false){
				echo $db->getMsgError();
				return;
			}							
			$categoriesMap = array(); 			
			foreach($categories as $category){
				$categoriesMap[$category['id_category']] = $category; 			
			}
			
			$toBeActive = array(); 						
			$togleCategories = array(); 			
									
			foreach($activeProductsCategories as $activeCat){
				$toBeActive[$activeCat['id_category']] = 1; 
				$cat = $categoriesMap[$activeCat['id_category']]; 
				if($cat['id_parent'] !=1){
					do{
						$cat = $categoriesMap[$cat['id_parent']]; 
						$toBeActive[$cat['id_category']] = 1; 
					}while($cat['id_parent'] !=1);
				}
			}	
			
			//echo '<pre>'.print_r($categories, true).'</pre>'; 
			//die;										

			foreach($categories as $category){
				if(array_key_exists($category['id_category'] ,$toBeActive)){
					if($category['active'] == 0)
						$togleCategories[$category['id_category']] =1;
				}else{
					if($category['active'] == 1)
						$togleCategories[$category['id_category']] =1;
				}			
			}			
			
			//echo '<pre>'.print_r($categoriesMap, true).'</pre>'; 
			//die;										
			foreach($togleCategories as $id => $val){
				$cat = new Category($id);
				$cat->toggleStatus();
			}			
			echo json_encode(array('status' =>0 ));				
		}				
		
		
		public function addAttributesGroups(array $param){
		
			
			
			$defLang = 1; 
			$ag  = AttributeGroup::getAttributesGroups($defLang); 
			$attrGroups = array(); 
			foreach($ag as $attrGroup){
				$attrGroups[$attrGroup['name']] = $attrGroup; 
			}	
			foreach($param  as $xmlGroupName => $value){			
				if(!array_key_exists($xmlGroupName , $attrGroups)){
					$group = new AttributeGroup(); 
					$group->name[$defLang]= $xmlGroupName; 
					$group->public_name[$defLang]= $xmlGroupName; 
					$group->is_color_group= $value['is_color_group']; 
					$group->add(); 					
				}
			}
							
			$ag  = AttributeGroup::getAttributesGroups($defLang); 
			$attrGroups = array(); 
			foreach($ag as $attrGroup){
				$attrGroups[$attrGroup['name']] = $attrGroup; 
			}				
			
			// atrybuty 
			$result = array(); 
			foreach($param  as $xmlGroupName => $value){				
				$id_attribute_group = $attrGroups[$xmlGroupName]['id_attribute_group'];
				
				$result[$xmlGroupName]['id_attribute_group'] = $id_attribute_group; 
				
				$ag = AttributeGroup::getAttributes($defLang, $id_attribute_group);
				$attrGroup = array(); 
				foreach( $ag as $attr){
					$attrGroup[$attr['name']] = $attr;				
				}				
				foreach($value['attributes'] as $attributeName => $zero){
					
					if(!array_key_exists($attributeName ,$attrGroup)){
						$obj = new Attribute(); 							
						$obj->id_attribute_group = $id_attribute_group;
						$obj->name[$defLang] = $attributeName;
						$obj->color =  0;
						if (($fieldError = $obj->validateFields(true, true)) === true AND ($langFieldError = $obj->validateFieldsLang(true, true)) === true){
							$obj->add();							
							$attrGroup[$attributeName]['id_attribute'] = $obj->id;
						}else{
							echo 'attribute add erroro' ; die; 
						}
					}
				}				
				foreach($attrGroup as $name => $val ){
					$result[$xmlGroupName]['attributes'][$name] = $val['id_attribute'];
				
				}
				$result[$xmlGroupName]['is_color_group'] = 	$value['is_color_group'];
			}			
			//echo 'grupy attrybutów  sklepu <pre>'.print_r($result , true) . '</pre>';  die;
			echo json_encode($result);			
			return;	
		}		
		
		public static  function getMeta($text){
			$str = str_replace( array( '<', '>'  ,  ';' , '=' , '#' , '{' , '}') , '' , strip_tags(html_entity_decode($text ,ENT_QUOTES ,'UTF-8' ))); 
			$len =  strlen($str);
			if($len  > 255)
				$str = mb_substr($str , 0 , 255);
			return $str;
		}

		
		/*
		 *  dodaje produkty w paczkach wykorzystywane przy  imporcie z csv 
		 * 	w ten sposób importowane są tylko produkty drukarkowe 			
		 *  jak produkt już istnieje to nie dodaje nowego produktu tylko updatuje ceny 
		 * 	nowe produkty trafiają do kategorii drukujesz24_nowe mają i maja być nieaktywne 
		 */ 
		
		

		
		public function addProductPack($params){
			//var_dump($params);   die;
			$params = json_decode($params , true);

			$defLang = intval(Configuration::get('PS_LANG_DEFAULT'));
			$out = '';
			foreach($params as $param){
				//print_r($param); die;
				if(!array_key_exists('prestaId',$param['value'])){
					//
					ob_start();
					$this->addProduct($param);					
					$out .= ob_get_clean();
					//print_r($param);
					//echo $out ; die; 					
										
				}else{
					$product = new Product($param['value']['prestaId']);					
					$product->price = $param['value']['price'];					
					$product->save(false);
					StockAvailable::setQuantity($product->id, null, $param['value']['quantity']);  
					Hook::exec('actionProductSave', array('id_product' => $product->id));
				}				
			}			
			echo json_encode(array('status' =>1  , 'data'=>' '. round( (microtime(true) - $this->startime) , 3 ) . ' s' , 'ob'=> $out));
			return;
			echo $this->classname;
			print_r($param);
			
		}
		
		function disableDomainProducts($null){
			$db  = Db::getInstance(); 			
			$sql = 'UPDATE `ps_product` SET `active`= 0  WHERE `kod_produktu` is not NULL' ;			
			$db->Execute($sql);
			echo json_encode(array('status' =>1  , 'data'=>'Ok'));
		}
		
		
		function addAColors($array){
			//print_r($array);
			//die;
				
			$db  = Db::getInstance(); 			
			$sql = 'INSERT IGNORE INTO `a_color`(`color`)  VALUES  ' ;
			$str= '';
			foreach($array as $key => $val){
				$psql = pSQL($val); 
				$sql .= '(\''.$psql.'\'),';
				$str .=  '\''.$psql.'\',';				
			}
			$sql = rtrim($sql , ',');	
			$str = rtrim($str , ',');	
			$db->Execute($sql);
			//echo  '                      '.$sql;
			//echo '                      '.$str. '                      ';
			$sql = 'SELECT * FROM  `a_color`  WHERE `color` IN (' .$str. ')' ;				
				$result  = 	$db->ExecuteS($sql);
				$ret = array();
				foreach($result as $row){					
					$ret[mb_strtolower($row['color'])] = $row;
				}	
				
			//echo 'ddddd';			
			//print_r($ret);
			
				//die;	
			$sql = 'INSERT IGNORE INTO `a_color_synonym`(`color_id`, `synonym`) VALUES  ' ;			
			foreach($array as $key => $val){
				if(!array_key_exists($key ,  $ret)){
					$sql1 = 'SELECT * FROM  `a_color`  WHERE `color` =\'' .pSQL($key). '\'' ;
					$r = $db->ExecuteS($sql1);					
					$ret[$key] = $r[0];
				}
				$psql = pSQL($ret[$key]['color']); 	
				$sql .= '('.$ret[$key]['id'].',\''.$psql.'\'),';
			}	
			$sql = rtrim($sql , ',');	
			$db->Execute($sql);
			foreach($ret as $key => &$val){
				$val = $val['id'];
			}	
			
			echo json_encode(array('status' =>0 , 'ret'=>$ret));			
		}	
		
		public function addProductFeatures($array){
			
			$id_lang = intval(Configuration::get('PS_LANG_DEFAULT'));			
			$result = Feature::getFeatures($id_lang);
			$shopFeatures = array();
			foreach($result as $val){
				$shopFeatures[mb_strtolower($val['name'])] = $val['id_feature'];
			}
			// sprawdzam czy takie feature istnieje 
			$features = array();
			foreach($array as $feature => $vals){
				if(!array_key_exists(mb_strtolower($feature) ,     $shopFeatures)){
					$f  = new Feature() ;					
					$f->name[$id_lang] = $feature;
					$f->add();					
					$features[$feature] = $f->id;
				}else{
					$features[$feature] = $shopFeatures[mb_strtolower($feature)];
				}	
			}
			$featuresVals = array();
			foreach($array as $feature => $vals){
				$id_feature = $features[$feature];
				$result  = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature);				
				$shopFeaturesVasl = array();
				foreach($result as $val){
					$shopFeaturesVasl[mb_strtolower($val['value'])] = $val['id_feature_value'];
				}
				foreach($vals as $featureValKey => $v){
					if(!array_key_exists( $featureValKey,     $shopFeaturesVasl)){
						$f  = new FeatureValue();					
						$f->id_feature = $features[$feature];						
						$f->value[$id_lang] = $v;
						if($f->add())					
							$featuresVals[$feature][$featureValKey] = $f->id;
						else{
							echo 'brak nie udało sie dodać '.$featureValKey. '  ' .(int)$f->id;
							die;
						}		
					}else{
						$featuresVals[$feature][$featureValKey] = $shopFeaturesVasl[$featureValKey];
					}					
				}
			}			
			echo json_encode(array('status' =>0 , 'ret'=>array( 'features' => $features ,  'featuresVals' => $featuresVals )));
		}
		
		
		public function addPrinterManufactuers($array){				
				$db  = Db::getInstance(); 							
				$sql = 'INSERT IGNORE INTO `a_producer`(`name`, `short_name` )  VALUES  ' ;
					$printersStr = '';
					foreach($array as $printer => $x){
						$printerSql = pSQL($printer); 
						$sql .= '(\''.$printerSql.'\',\''.$printerSql.'\' ),';
						$printersStr .=  '\''.$printerSql.'\',';
					}
					$sql = rtrim($sql , ',');					
					$printersStr = rtrim($printersStr , ',');
					$db->Execute($sql);
				$sql = 'SELECT * FROM  `a_producer`  WHERE `name` IN (' .$printersStr. ')' ;				
				$priMan  = 	$db->ExecuteS($sql);
				$ret = array();
				foreach($priMan as $row){					
					$ret[$row['name']] = $row['producer_id'];
				}	
				echo json_encode(array('status' =>0 , 'ret'=>$ret));
				//print_r($priMan);
				die;							
		}	
		
		/*
		 *	dodaje produkt  
		 * 
		 */
		public function addProduct(array $param){
			$defLang =  1; 
			//echo '<pre>'.print_r($param , true).'</pre>pre>';   die;
			//ERC09

				
			$key = $param['name'];
			
			
			$id_xml = $param['value']['xml_id'];
			
			$sql = 'SELECT * from '._DB_PREFIX_.'importerps_product_map  WHERE classname = \''.pSQL($this->classname).
			'\'  AND id_xml = \''.pSQL($id_xml).'\'';
			//echo $sql; die;
			if ($results = Db::getInstance()->ExecuteS($sql)){
				if(count($results) !=1){
					echo 'product Map count error  '; 
					return;				
				}
				foreach($results as $row)
					$product_id = $row['id_product']; 
			}else{			
				$product_id= 0;
			}
			
			
			if($product_id){
				$product = new Product($product_id); 
				if(empty($product->id)){
					$sql = 'DELETE from '._DB_PREFIX_.'importerps_product_map  WHERE classname = \''.pSQL($this->classname).
					'\'  AND id_xml = \''.pSQL($id_xml).'\'';
					if(! Db::getInstance()->Execute($sql)){
						echo 'error produkt nie isnitje w sklepie ale istnije w map - usunięto go ze sklepu ręccznie';
						echo 'i w odatku nie udao sie usunać z map';
						return;
					}else{
						$product = new Product(); 								
					}	
					$product_id = 0;					
				}
			}else{
				$product = new Product(); 					
			}
			
			$product = new Product(); 
			foreach($param['value']['name'] as $id_lang => $val){
				$product->name[$id_lang] = self::getMeta($param['value']['name'][$id_lang]);
				$product->short_description[$id_lang] = $param['value']['short_description'][$id_lang];						
				$product->description[$id_lang] = $param['value']['description'][$id_lang];
				$product->meta_description[$id_lang] = self::getMeta($product->description[$id_lang]);
				$product->link_rewrite[$id_lang] = Tools::str2url($product->name[$id_lang]);				
			}
			if(!empty($param['value']['kod_produktu']))
				$product->kod_produktu = $param['value']['kod_produktu'];
			$product->reference = Tools::str2url($param['value']['reference']);
			
			//$product->redirect_type = '';
						
			if(array_key_exists('quantity' , $param['value'])){
				$product->quantity = $param['value']['quantity'];
			}
			if(array_key_exists('ean13' , $param['value'])  and !empty($param['value']['ean13'])){
				$product->ean13 = $param['value']['ean13'];
			}			
			
			$product->wholesale_price = (( array_key_exists('wholesale_price' , $param['value']) and $param['value']['wholesale_price'] > 0 ) ?  $param['value']['wholesale_price'] : 0 );
			//$product->price = $product->wholesale_price * $this->global_provision; 
			$product->price = (( array_key_exists('price' , $param['value']) and $param['value']['price'] > 0 ) ?  $param['value']['price'] : 0 );
			$product->id_tax_rules_group = 1;
			
			
			

			//3935-5
			$product->indexed = 1;
			$product->active = 1;			
			
			if(array_key_exists('noActive',$param['value'])){
				$product->active = 0;			
			}
			$product->id_category_default = $param['value']['id_category'];
			if(array_key_exists('manufactuer', $param['value']  )){ 
				if (  !($product->id_manufacturer =  Manufacturer::getIdByName($param['value']['manufactuer']))  ){
					echo 'Manufacturer '. $param['value']['manufactuer'].' not exist' . strlen($param['value']['manufactuer']); 
					return;
				}
			}else{
				$product->id_manufacturer = 0;
			}							
			$product->redirect_type = '';			
			//echo '<pre>'.print_r($product , true).'</pre>'; die;
			
			if ($product->save())
			{	
				$db  = Db::getInstance(); 	
				echo '<pre>'.print_r('$product' , true).'</pre>';
				
				$sql =  'UPDATE `a_product` SET `color_id` =  '.(array_key_exists( 'a_color_id' , $param['value'])?  $param['value']['a_color_id'] : 'NULL' ).' ,`wydajnosc`= '. (array_key_exists('Wydajność wkładu' ,   $param['value']['attributesxml']) ? '\''. pSQL($param['value']['attributesxml']['Wydajność wkładu']).'\'' : 'NULL'    ). ',`pojemnosc`= '.(array_key_exists('Pojemność' ,   $param['value']['attributesxml']) ?   '\''. pSQL( $param['value']['attributesxml']['Pojemność']).'\'' : 'NULL'    ).',`name`= '. '\''.  pSQL($param['value']['name'][$id_lang]) .'\'' . ' ,`description`= '.'\''.pSQL($param['value']['description'][$id_lang]).'\''.'  WHERE `kod_produktu`=\''.pSQL($product->kod_produktu).'\'';
				$db->Execute($sql);
				
				foreach($param['value']['featuresIds'] as $row){
					Product::addFeatureProductImport($product->id, $row['id_feature'], $row['id_feature_value'] );					
				}
				//addFeatureProductImport
					/*
					featuresIds
					a_color_id	
					{ id_feature="34", id_feature_value="47"}	
				*/
			/*	$sql = 'INSERT INTO `a_product`(`kod_produktu`, `id_sklep`) VALUES ( \''.pSQL($param['value']['kod_produktu']).'\','.$product->id.'   )';
				$db->Execute($sql);
			*/	
				//$sql = 'INSERT INTO `a_product_oem`(`kod_produktu`, `oem`)  VALUES ( \''.pSQL($param['value']['kod_produktu']).'\',  \''.pSQL($param['value']['kod_produktu']).'\' )';
				//$db->Execute($sql);
				
				
				//$sql = 'INSERT INTO `a_oem_synonym`(`oem`, `synonym`)  VALUES ( \''.pSQL($param['value']['kod_produktu']).'\',  \''.pSQL($param['value']['kod_produktu']).'\' )';
				//$db->Execute($sql);
				
				if(isSet($param['value']['printers']) and count($param['value']['printers'])){
					$printerIds = array();
					$printersStr = '';
					foreach($param['value']['printers'] as $printer){						
						$printerSql = pSQL($printer['model']); 
						if(!array_key_exists('producer_id' , $printer)){
							$pr = ' NULL ';
						}else{
							$pr  = $printer['producer_id'];
						}
						$sql = 'INSERT IGNORE INTO `a_printer` ( `model` , `producer_id`)  VALUES  (\''.$printerSql.'\' , '.$pr.')'; 
						$db->Execute($sql);						
						if(!array_key_exists('producer_id' , $printer)){						
							$pr = ' IS NULL ';
						}else{
							$pr  = '='.$printer['producer_id'];
						}												
						$sql = 'SELECT `id` FROM `a_printer` WHERE  `model` =\''.$printerSql.'\'  AND  `producer_id`  '. $pr ;
						$ret  = 	$db->ExecuteS($sql);
						if(!is_array($ret) || ! array_key_exists(0 , $ret) ){
							print_r($param['value']);														
							print_r($ret );
							print_r($sql);

						}
						
						
						$printerIds[] = $ret[0]['id'];
						
						//10N0016E
					}
					// printer_oem														
					$oem = 	pSQL($param['value']['kod_produktu']);					
					$sql = 'INSERT IGNORE INTO `a_oem_printer`(`oem`, `printer_id`)  VALUES  '; 					
					foreach($printerIds as $row){
						$sql .= '(\''.$oem.'\',  \''.  pSQL($row).'\'),';
					}
					$sql = rtrim($sql , ',');
					$db->Execute($sql);					
				}
				
				
				//$sql = 'INSERT INTO `a_product_synonym`(`kod_produktu`, `synonym`)  VALUES ( \''.pSQL($param['value']['kod_produktu']).'\',  \''.pSQL($param['value']['kod_produktu']).'\' )';
				//$db->Execute($sql);
								
				
				StockAvailable::setQuantity($product->id, null, $param['value']['quantity']);  
				
				//$product->deleteImages();
				//print_r(strip_tags(html_entity_decode($product->description[1],  ENT_QUOTES ,'UTF-8'  )));  die;
				
				if(!$product_id){
					$sql = 'INSERT INTO  '._DB_PREFIX_.'importerps_product_map  VALUES (\''. pSQL($this->classname). '\',\''.
					pSQL($id_xml) .'\',\''.
					$product->id .'\' )';
					
					
					if(! $db->Execute($sql)){					
						echo $db->getMsgError();
						$product->delete();
					    echo 'nie udało się updatowac Map' ; 
						return;
					}
				}	
				//$this->updateAccessories($product);
				/*
				if (!$this->updatePackItems($product))
					$this->_errors[] = Tools::displayError('An error occurred while adding products to the pack.');
				$this->updateDownloadProduct($product);
				*/
				if (!sizeof($this->_errors))
				{
					// tagi 
					
					Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'product_tag`
								WHERE `id_product` = '.(int)($product->id)); 						
					if(array_key_exists('tags', $param['value'] ))	

						if(! Tag::addTags($defLang, (int)($product->id),$param['value']['tags'])){
							//echo "Tags error "; die;
						}				
				
				
					if (!$product->updateCategories(array($param['value']['id_category'])))
						$this->_errors[] = Tools::displayError('An error occurred while linking object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
					elseif (false /*!$this->updateTags($languages, $product)*/)
						$this->_errors[] = Tools::displayError('An error occurred while adding tags.');
					elseif ( true /*$id_image = $this->addProductImage($product)*/)
					{
						//Hook::addProduct($product);
						Hook::exec('actionProductSave', array('id_product' => $product->id));
						Search::indexation(false, $product->id);
					}
				}
				else{
					echo "rrrrr";
					print_r($this->_errors);
					die;
					$product->delete();
				}	
			}else{
				echo "eeeeeeeeeerrr";
				die;
			}
			
			


			//images
			if(array_key_exists('imgs', $param['value']  )){ 
			
				$imgs = array(); 
				foreach($param['value']['imgs'] as $image){
					$imgs[$image] = 1; 	
				}
				
				$prestaImgs  = $product->getImages($defLang); 
				foreach( $prestaImgs as $row){
					$img  = new Image($row['id_image']); 
					if(empty($img->id)){
						echo 'Image load error'; die;
					}
					if(!empty($img->url)){
						//echo 'URL' .$img->url; 
						if(!array_key_exists($img->url , $imgs)){
							$img->delete(); 
						}else{
							unset( $imgs[$img->url]);
						}					
					}			
				}
				$position = Image::getHighestPosition($product->id)+1; 
				$allowedExtensions = array('jpeg', 'gif', 'png', 'jpg');
				foreach($imgs as $url => $one){
					$img  = new Image(); 
					$img->id_product = $product->id; 
					$img->url = $url; 
					$img->legend[$defLang] = "img";
					$img->position = $position++; 
					$img->validateFields();

					
					
					if(!$img->add()){
						echo '<br>Image Add Error ' ; die;								
					}else{
						$pathinfo = pathinfo($url);
						//$these = implode(', ', $this->allowedExtensions);
						if (!isset($pathinfo['extension']))
							return array('error' => Tools::displayError('File has an invalid extension, it should be one of').$these.'.');
						$ext = $pathinfo['extension'];
						if (!in_array(mb_strtolower($ext), $allowedExtensions))
							return array('error' => Tools::displayError('File has an invalid extension, it should be one of').$these.'.');
						else{
								
							
							
							if (!$new_path = $img->getPathForCreation())
								return array('error' => Tools::displayError('An error occurred during new folder creation'));
							$cont = file_get_contents($url);
							$tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS');
							file_put_contents($tmpName, $cont);
							ImageManager::resize($tmpName, $new_path.'.'.$img->image_format);
							$imagesTypes = ImageType::getImagesTypes('products');
							foreach ($imagesTypes as $imageType){
								ImageManager::resize($tmpName, $new_path.'-'.stripslashes($imageType['name']).'.'.$img->image_format, $imageType['width'], $imageType['height'], $img->image_format);
							}							
							// do here something
							unlink($tmpName);
							Hook::exec('actionWatermark', array('id_image' => $img->id, 'id_product' => $product->id ));
								
							$img->update();
						}
						
					}
				}

				$cover = Image::getCover($product->id); 
				//print_r($cover); die;
				if(empty($cover)){
					$imgs = Image::getImages($defLang, $product->id); 
					foreach($imgs as $row){				
						$img  = new Image($row['id_image']); 					
						if(empty($img->id)){
							echo 'Image load error1'; die;
						}
						if(!empty($img->url)){
							$img->cover = 1; 
							$img->legend[$defLang] = "img";
							if(!$img->save()){
								echo '<br>Image cover set Error ' ; die;														
							}
							break; 
						}								
					}				
				}
			}
/// attributes combination 

			$provision = Configuration::get('PS_IMPORTPS_GLOBAL_PROVISION');
			if(array_key_exists('attributesCombination' , $param['value']) and count($param['value']['attributesCombination'])){				
				$combinaisons = $product->getAttributeCombinaisons($defLang);			
				//print_r($combinaisons);
				$combinationList = array(); 				
				foreach( $combinaisons as $index => $combination ){
					$combinationList[$combination['id_product_attribute']  ] ['atributes_list'][] = $combination['id_attribute'] ; 
					$combinationList[$combination['id_product_attribute']  ] ['combinationIndex'][]  = $index; 
				}
				
				//print_r($combinationList); 
				// sprawdzanie czy znamy id combinacji atrybutów czyli czy attr_ids == jest w $combinationList
				$toUpdateCombination = array();
				$new  = $param['value']['attributesCombination'];
				
				foreach($new as $index=> $attrComb){
					//echo print_r($attrComb); die;
					$prod = $attrComb['attrs_ids']; 
					sort($prod ); 
					foreach($combinationList as $id_product_attribute => $shopComb){
						$shop = $shopComb['atributes_list']; 
						sort($shop);
						//echo print_r($shop, true);echo  print_r($prod, true) . '<br>'; 
						if(	$shop == $prod){						
							//update
							$toUpdateCombination[$id_product_attribute] = $attrComb; 
							unset ($combinationList[$id_product_attribute]);
							unset ($new[$index]);
							break 1;
						}			
					}
				}
				// w update są te do update  w $new nowe  , a w $combinationList te do usunicia // wartiości są w 
				//echo $product->id;
				//print_r($toUpdateCombination) ; 
				//print_r($new) ; 
				//print_r($combinationList) ;// die;

				foreach($combinationList as $id_product_attribute =>$attr){
					$product->deleteAttributeCombinaison($id_product_attribute);
					$product->checkDefaultAttributes();
					$product->updateQuantityProductWithAttributeQuantity();
					if (!$product->hasAttributes())
					{
						$product->cache_default_attribute = 0;
						$product->update();
					}else
						Product::updateDefaultAttribute($product->id);			
				} 

				
				
				
				if($product->price >0 and $product->wholesale_price >0)
					$factor = $product->price/$product->wholesale_price; 
				else	
					$factor = $provision;
				foreach($new as $attr){
					if ( $product->productAttributeExists($attr['attrs_ids']) ){
					//echo '<pre>'.print_r($toUpdateCombination, true). '</pre>'; 
					echo '<pre>'.print_r($new, true). '</pre>'; 
					//echo '<pre>'.print_r($combinationList, true). '</pre>'; 
						echo Tools::displayError('This combination already exists.' . '1' . print_r($attr['attrs_ids'] , true));
						die;
					}else{							
					//	public function addCombinationEntity                         ($wholesale_price, $price, $weight, $unit_impact, $ecotax, $quantity, $id_images, $reference, $supplier_reference, $ean13, $default, $location = NULL, $upc = NULL, $minimal_quantity = 1)	
					
						// array_key_exists('price', $attr) ? $attr['price']* $this->global_provision :0					
						/// ustalenie ceny 
						
						$id_product_attribute = $product->addCombinationEntity(
							( array_key_exists('price', $attr) ? $attr['price'] :0),
							// ( array_key_exists('price', $attr) ? $attr['price']* $this->global_provision :0),						
							( array_key_exists('price', $attr) ? $attr['price'] * $factor - $product->price:0),	
							( array_key_exists('weight', $attr) ? $attr['weight'] :0),
							0,
							0,
							( array_key_exists('quantity', $attr) ? $attr['quantity'] :null),
							false, 
							null,
							null,
							null,
							0
						);
						
						$product->addAttributeCombinaison($id_product_attribute, $attr['attrs_ids']);
						$product->checkDefaultAttributes();
						Product::updateDefaultAttribute($product->id);										
					}						
				}			
				
				
				foreach($toUpdateCombination as  $id_product_attribute =>$attr){
						
					if ( $product->productAttributeExists($attr['attrs_ids'], $id_product_attribute)){
						echo Tools::displayError('This combination already exists.');
						die;
					}else{		
						//print_r($attr) ; die;				
						// public function updateProductAttribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax, $quantity, $id_images, $reference, $supplier_reference, $ean13, $default, $location = NULL, $upc = NULL, $minimal_quantity)				
						$product->updateProductAttribute($id_product_attribute,
							( array_key_exists('price', $attr) ? $attr['price'] :0),
							//( array_key_exists('price', $attr) ? $attr['price']* $this->global_provision :0),						
							( array_key_exists('price', $attr) ? $attr['price'] * $factor - $product->price:0),	
							( array_key_exists('weight', $attr) ? $attr['weight'] :0),
							0,
							0,
							( array_key_exists('quantity', $attr) ? $attr['quantity'] :null),
							false, 
							null,
							null,
							null,
							0,
							null, 
							null,
							1
						);
						Hook::updateProductAttribute((int)$id_product_attribute);
						$product->addAttributeCombinaison($id_product_attribute, $attr['attrs_ids']);
						$product->checkDefaultAttributes();
						Product::updateDefaultAttribute($product->id);															
					}						
				}
			}
			echo json_encode(array('status' =>1  , 'data'=>' '. round( (microtime(true) - $this->startime) , 3 ) . ' s'));
			//echo json_encode(array('status' =>1  , 'data'=>' '. round( (microtime(true) - $this->startime) , 3 ) . ' s'));
			return;
			// echo json_encode(array('status' =>1  , 'id'=>$product->id));
				//$this->startime = microtime(true);
			
		
		
		/*     [name] => 4022-3 Kozaczki na obcasie z ozdobną kokardką - wino
            [desc] => Eleganckie kozaki / botki na wysokiej szpilce. Ozdobione kokardką. Od wewnętrznej strony zapinane na za

            [imgs] => http://yournewstyle.pl/files/clothes/big/2089479aab37d6524e1dec31ec97e69d.jpg
            [producer] => Sinly Shoes
            [url] => http://www.yournewstyle.pl/model/2768,4022-3-kozaczki-na-obcasie-z-ozdobna-kokardka-wino
            [attributesCombination] => Array
			*/
		}
	
	}
?>
