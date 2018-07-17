<?php
abstract class AbstractParser {
	public $errorMsg;
	
	public $token; 
	public $autoExec = true; 
	
	public $onlyAvalible = true; 
	
	public $categories = array();
	public $categoriesTree = array();
	
	public $producerMap = array(); 
	
	public $shopManufacturers = array();  
	
	
	public $attributesGroup = array(); 
	
	
	
	public $products = array(); 
	public $manufacturers = array();
	public $manufactuerSynonym = array();
	
	public $languages;	
	
	public $defaultLang; 
	
	public function __construct($token){
		$this->errorMsg = '';
		$this->defaultLang = Configuration::get('PS_LANG_DEFAULT');
		$this->onlyAvalible = true; 
		$this->languages = Language::getLanguages(false);
		//print_r($this->languages); die;
		$this->token = $token; 	
		
		$sql = 'SELECT * from '._DB_PREFIX_.'importerps_manufacruer_synonym ' ;
		if ($results = Db::getInstance()->ExecuteS($sql))
		foreach ($results as $row)
			$this->manufactuerSynonym[strtolower($row['synonym'])] = $row['value'];
			//$this->manufactuerSynonym[$row['value']] = $row['synonym'];
	}
	
	public function getShopManufacturers(){
		$this->shopManufacturers = array();
		foreach(Manufacturer::getManufacturers(false, 0, false) as $man){
			$this->shopManufacturers[$man['name']] = $man;		
		}
		return $this->shopManufacturers;
	}	


	public function remapManufacturers( $manMap ){
		$this->manufacturers = array(); 	
		foreach($manMap as $key => $val){
			$this->manufacturers[$this->getManufacturerNameMap($key)]  = $val; 		
		}	
	}	
	
	public function getManufacturerNameMap($manufacturerName){
		$k  = strtolower($manufacturerName);
		if(array_key_exists($k, $this->manufactuerSynonym)){
			return  $this->manufactuerSynonym[$k];
		}
		return  $manufacturerName;
	}
	
	
	public function displayContent(){
		$this->displayManufacturers();
		echo '<hr style="width:100%; clear: both;" >';		
		$this->displayCategories();
		echo '<hr style="width:100%; clear: both;" >';		
		$this->displayProduct();
		echo '<hr style="width:100%; clear: both;" >';
		$this->displayForm();		
	}
	
	public function displaySubTree($xmlSubCat){
		
		$ret = '<ul>';
			foreach($xmlSubCat as $xmlId => $xmlSub) {
				$ret.='<li>';
					$ret.='<span class="folder">'.$xmlId.' '.$this->categories[$xmlId]['name'].' --> </span>' ; 
					if(array_key_exists('id_category' ,$this->categories[$xmlId])){
						$cat = new Category($this->categories[$xmlId]['id_category']);  
						if(!empty($cat->id)){
							//print_r($cat); die;
							$ret.='<span style ="float: right">'.$cat->name[$this->defaultLang]. ' ' . $cat->id .'</span>'; 
						}else{
							$ret.='error kateogia powinna juz isnieć '; 
							die; 
						}						
					
					}else{
						$ret.='<span style ="color:Red;">!!!!!!!</span>';
					}
					
					
					if(!empty($xmlSub))
						$ret.=$this->displaySubTree($xmlSub); 
				$ret.='</li>';						
			}		
		$ret.='</ul>';
		return $ret;	
	}
	
	
	public function displayCategories(){
			//print_r($this->categories); die;
		echo '<div  style="width: 70%;" >';
		$this->displaySubTree($this->categoriesTree); 		
		echo '</div>';
		//die;
	}	
	
	
	public function synchroniseCategories(){
	
		$className = get_class($this);	
		
		$sql = 'SELECT * from '._DB_PREFIX_.'importerps_category_map  WHERE classname = \''.pSQL($className).'\'';
		$results = Db::getInstance()->ExecuteS($sql);						
		foreach($results  as $row){
			if(array_key_exists($row['id_xml'] ,  $this->categories)){
				// czy kategoria istnije w sklepie  
				$cat = new Category($row['id_category']);
				if(empty($cat->id)){					
					$sql = 'DELETE from '._DB_PREFIX_.'importerps_category_map  WHERE classname = \''.pSQL($className).
					'\'  AND id_xml = \''.pSQL($row['id_xml']).'\'';
					if(! Db::getInstance()->Execute($sql)){
						echo 'error Kategoria nie isnitje w sklepie, ale istnije w map - usunięto go ze sklepu ręccznie';
						echo 'i w doatku nie udao sie usunać z map';						
						die;
					}
					continue;
				}else{
					$this->categories[$row['id_xml']]['id_category']  = $cat->id; 
					$this->categories[$row['id_xml']]['id_parent']  = $cat->id_parent; 
					$this->categories[$row['id_xml']]['exist']  = 1; 
				}				
			}		
		}
		/// kategorie które nie mają  id_category   próbuje odtworzyć dane z kontekstu  
		/* foreach($this->categories as $id_xml => $category){
			if(empty($this->categories[$id_xml]['id_category'])){
				if($result  = Category::searchByName($this->defaultLang, $this->categories[$id_xml]['name'])){					
					$this->categories[$id_xml]['id_category']  = $result[0]['id_category']; 
					$this->categories[$id_xml]['id_parent']  = $result[0]['id_parent'];
					$this->categories[$id_xml]['exist']  = 0; 				
					if(count($result)) $this->categories[$id_xml]['duplicate'] = 1; 				
				}
			}		
		} */
		// zostały te które nie zostały zidentyfikowane 
		if($this->autoExec){  // generowanie drzewa kategorii 			
			foreach($this->categoriesTree as $id_xml => $subCat){
				if(!empty($this->categories[$id_xml]['id_category'])){
					if(array_key_exists('exist', $this->categories[$id_xml]) and  !$this->categories[$id_xml]['exist'] ){
						$sql = 'INSERT INTO  '._DB_PREFIX_.'importerps_category_map  VALUES (\''. pSQL($className). '\',\''.
							pSQL($id_xml) .'\',\''.
							$this->categories[$id_xml]['id_category'] .'\' )';
							$db  = Db::getInstance(); 					
							if(! $db->Execute($sql)){
								echo $db->getMsgError();
								echo 'error nie udało się updatować category map0'; 
								die;
							}
							$this->categories[$id_xml]['exist']	= 1; 						
					}									
				}else{
					
					$cat = new Category(); 
					$cat->name[$this->defaultLang] =	$this->categories[$id_xml]['name'];
					$cat->link_rewrite[$this->defaultLang] = Tools::str2url($cat->name[$this->defaultLang]);
					$cat->id_parent = 2 ; 
					if($cat->add()){
						$sql = 'INSERT INTO  '._DB_PREFIX_.'importerps_category_map  VALUES (\''. pSQL($className). '\',\''.
							pSQL($id_xml) .'\',\''.
							$cat->id.'\' )';
							$db  = Db::getInstance(); 					
							if(! $db->Execute($sql)){
								echo $db->getMsgError();
								echo 'error nie udało się updatować category map1'; 
								die;
							}										
						$this->categories[$id_xml]['id_category']  = $cat->id; 
						$this->categories[$id_xml]['id_parent']  = $cat->id_parent;
						$this->categories[$id_xml]['exist']  = 1; 																									
					}
					
				}
				if(!empty($subCat)) 
					$this->addSubCat($subCat, $id_xml);
			}
		}

	}		
	
	
	/**
	** jest gwarancja że kartoteka nadredna istnije 
	**/
	
	public function addSubCat($subCategory, $xmlParrent){
		$className = get_class($this);	
		foreach($subCategory as $id_xml => $subCat){
			if(!empty($this->categories[$id_xml]['id_category'])){
				if(array_key_exists('exist', $this->categories[$id_xml]) and  !$this->categories[$id_xml]['exist'] ){
					$sql = 'INSERT INTO  '._DB_PREFIX_.'importerps_category_map  VALUES (\''. pSQL($className). '\',\''.
						pSQL($id_xml) .'\',\''.
						$this->categories[$id_xml]['id_category'] .'\' )';
						$db  = Db::getInstance(); 					
						if(! $db->Execute($sql)){
							echo $db->getMsgError();
							echo 'error nie udało się updatować category map3'; 
							die;
						}
						$this->categories[$id_xml]['exist']	= 1; 						
				}									
			}else{
				$cat = new Category(); 
				$cat->name[$this->defaultLang] =	$this->categories[$id_xml]['name'];
				$cat->link_rewrite[$this->defaultLang] = Tools::str2url($cat->name[$this->defaultLang]);
				$cat->id_parent = $this->categories[$xmlParrent]['id_category']; 
				//print_R ($cat->id_parent); die;
				if($cat->add()){
					$sql = 'INSERT INTO  '._DB_PREFIX_.'importerps_category_map  VALUES (\''. pSQL($className). '\',\''.
						pSQL($id_xml) .'\',\''.
						$cat->id.'\' )';
						$db  = Db::getInstance(); 					
						if(! $db->Execute($sql)){
							echo $db->getMsgError();
							echo 'error nie udało się updatować category map4'; 
							die;
						}										
					$this->categories[$id_xml]['id_category']  = $cat->id; 
					$this->categories[$id_xml]['id_parent']  = $cat->id_parent;
					$this->categories[$id_xml]['exist']  = 1; 																									
				}
			}
			if(!empty($subCat)) 
				$this->addSubCat($subCat, $id_xml);
		}		
	}
	
	public function displayManufacturers(){				
		$this->getShopManufacturers();
		$newM = $this->getNewManufacturers();

		echo '<div>';
			echo '<div style="display: inline; float: left;  width: 300px;">';
					echo '<span style="cursor: pointer;"   onclick ="{$(\'#xmlManufactuers\').toggle(); }"><h3> Producenci w xml </h3></span><br />';
					echo '<div  id="xmlManufactuers" ' .( count($newM)?  '': 'style="display: none;"' ).  '>';
						foreach($this->manufacturers as $man => $val){
							echo $man. '<br />';
						}
					echo '</div>';
			echo '</div>';
			
			echo '<div style="display: inline; float: left;  width: 300px;">';
					echo '<span style="cursor: pointer;" onclick ="{$(\'#shopManufactuers\').toggle(); }" ><h3> Producenci w sklepie </h3></span><br />';
					echo '<div  id="shopManufactuers"' . ( count($newM)?  '': 'style="display: none;"' )   .  ' >';
						foreach($this->shopManufacturers as $name => $val){
							echo $name . '<br />';										
						}				
					echo '</div>';
			echo '</div>';			
			echo '<div style="display: inline; float: left;  width: 300px;">';
				echo '<span style="cursor: pointer;"  onclick ="{$(\'#newManufactuers\').toggle(); }"><h3> Producenci do dodania</h3></span><br />';
				echo '<div  id="newManufactuers" >';
					foreach($newM as $name => $val){
						echo $name . '<br />';														
					}
				echo '</div>';
			echo '</div>';			
		echo '</div>';
		
	
	}
	
	public function displayProduct(){			
		$sql =  'SELECT  pm.`id_xml`  ,   p.`price` as price
			FROM  `'._DB_PREFIX_.'product`  p , '._DB_PREFIX_.'importerps_product_map pm		
			WHERE  pm.`id_product` = p.`id_product` AND pm.`classname` = \''.pSQL(get_class($this)).'\''; 
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
			$ret.= 'Produktów: '.count( $this->products); 
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

			/*
			$irow = 0;
			foreach($this->products as $key => &$product){
				$ret.= '<tr'. ( $irow % 2 ? ' style="background-color:#D1EAEF"' : ' ' ). '>'; 
					$ret.= '<td style="text-align: right;"   >' . ($irow+1) . '</td>';
					$ret.= '<td>' . '<input  id="active_id_'.$key.'" type="checkbox" name="active" checked="checked">' . '</td>';
					$ret.= '<td>' . $key . '</td>';					
					$ret.= '<td>' . $product['name'][$this->defaultLang] . '</td>';
					$ret.= '<td style="text-align: right;" >' .sprintf("%.2F", $product['wholesale_price']). '</td>';	
					$bprice =  (ceil($product['wholesale_price'] * $provision * 100))/100; 
					$product['price'] = $bprice; 
					$ret.= '<td style="text-align: right;" >' .sprintf("%.2F",  $bprice) . '</td>';
					$ret.= '<td style="text-align: right;" >' . (array_key_exists($key , $prodPriceMap )? sprintf("%.2F",$prodPriceMap[$key]) : '') . '</td>';
					$ret.= '<td>' . '<input id="price_id_'.$key.'"  style="text-align: right;" type="text" size="7"  maxlength = "7" name="final_price"  value="'.  sprintf("%.2F", $product['price']).'">' . '</td>';
				$ret.= '</tr>';
				$ret.='<tr'. ( $irow % 2 ? ' style="background-color:#D1EAEF"' : ' ' ). '>'; 				
					$ret.='<td colspan = "2"></td>';
					$ret.='<td  colspan = "5">'  . '</td>';
					//$ret.='<td  colspan = "5">' . $product['description'][$this->defaultLang] . '</td>';
					$ret.='<td></td>';
				$ret.='</tr>';
				$ret.='<tr'. ( $irow % 2 ? ' style="background-color:#D1EAEF"' : ' ' ). '>'; 				
					$ret.='<td colspan = "2"></td>';
					$ret.='<td  colspan = "5">' .'Manufactuer: '.(array_key_exists('manufactuer' ,$product  ) ? $product['manufactuer'] : '') . '</td>';
					$ret.='<td></td>';
				$ret.='</tr>';				
				
				$str = ''; 
				foreach($product['attributesCombination'] as $comb){
					$a = array_keys($comb['attributes']); 
					sort( $a); 					
					foreach($a as $key){
						$str .= $key . ' ' .$comb['attributes'][$key] .',';					
					}
					$str .= '<span style ="float: right">' . $comb['quantity'] .'</span><br />'; 				
				}
				$ret.= '<tr'. ( $irow % 2 ? ' style="background-color:#D1EAEF"' : ' ' ). '>'; 				
					$ret.= '<td colspan = "2"></td>';
					$ret.= '<td  colspan = "5">' .$str. '</td>';
					$ret.= '<td></td>';
				$ret.='</tr>';				
			*/					
/*				echo '<tr'. ( $irow % 2 ? ' style="background-color:#D1EAEF"' : ' ' ). '>'; 				
					echo '<td colspan = "2"></td>';
					echo '<td  colspan = "4">' . 'Tagi '.(array_key_exists('tags' ,$product  ) ? $product['tags'] : '').'</td>';
					echo '<td></td>';
				echo '</tr>';				
*/				
				/*$irow ++;
				
			}		
			*/
			$ret.= '</table>';
		$ret.= '</div>';
		//echo '<pre>'.print_r($this->products, true).'</pre>';
		return $ret;
	}
	
	
	public function getNewManufacturers(){
		$ret = array();
		$ret = $this->manufacturers; 
		foreach($this->shopManufacturers as $name => $val){
			unset($ret[$name]); 
		}	
		return $ret;
	}	
	
	public abstract  function xmlCategoriesTree(array &$categories);
	public abstract 	function getSubCategories(array &$catNode , array &$categories, $catParrent );
	
	public function displayForm()
    {	
		global $currentIndex;
		$newM = $this->getNewManufacturers();
		$defaultLanguage = intval(Configuration::get('PS_LANG_DEFAULT'));
		$languages = Language::getLanguages();
		echo'		
		<input name="submitImportClass" type="submit" value="'.'Import'.'" class="button" onclick="importPs();" />			
		<hr style="width:100%;">';	
		echo '<div id="info_id">';
		echo '</div>';
		echo '<div id="errorinfo_id" style="color: red;">';
		echo '</div>';		
		
		echo '<hr style="width:100%;">';	
		//echo '<script type="text/javascript" src="http://jzaefferer.github.com/jquery-validation/jquery.validate.js"></script>';
		echo '<script type="text/javascript" src="../modules/importps/importps.js"></script>';
		echo '<script type="text/javascript">
			var globalAjaxToken = "'.sha1(_COOKIE_KEY_.'importps').'";
			var classname = "'. get_class($this) .'";'.								
		'var categories  = ' . json_encode($this->categories).';'.		 
		'var categoriesTree  = ' . json_encode($this->categoriesTree).';'.		 
		'var manufacturers  = ' . json_encode($newM).';'.
		' var products = '. json_encode($this->products).';'.
		' var attributesGroups = '.json_encode($this->attributesGroup).';'.
		'</script>';
				
		//echo 'grupy attrybutów <pre>'.print_r($this->products , true) . '</pre>';   
	}
	
	
	public function getNewProductManufacturers(){
		$ret = array();
		$ret = $this->manufacturers; 
		foreach($this->shopManufacturers as $name => $val){
			unset($ret[$name]); 
		}	
		return $ret;
	}		
	

}
