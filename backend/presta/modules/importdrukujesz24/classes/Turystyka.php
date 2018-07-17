<?php
include_once('abstractParser.php');
include_once(_PS_MODULE_DIR_.'/Db.php');
class Turystyka extends AbstractParser{

		
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
		
		//print_r(Configuration::get('PS_LANG_DEFAULT'));
		//print_r(Language::getLanguages());
		
		//echo '<pre>'.print_r(Category::getCategories(), true).'</pre>';
		//echo '<pre>'.print_r(Category::getCategoriesWithoutParent(), true).'</pre>';
		/*$cc = Category::getCategories();
		foreach($cc as $key=>$subArray){
			if($key<10) continue;
			foreach($subArray as $id => $cat){
				$toDel = new Category($id);
				$toDel->delete();
				echo $id.'<br>';
				
			}
			
		
		}
		85553 
		*/
		//$c = new AdminProductsController();
		//$c->getList($this->defaultLang);
		//print_r($c->_list);
		
		//$product
		//exit();
		$this->dbExt-> exec("set names utf8");
		
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
