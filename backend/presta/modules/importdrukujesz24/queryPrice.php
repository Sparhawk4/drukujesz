<?php class queryPrice extends Module
{
	public $id_customer;

	public $customer_email;

	public $id_product;

	public $id_product_attribute;

	public $id_shop;

	public $id_lang;
	
	
	public function getContext(){		
		return $this->context;
	}	
	
	
	public function sendOueryDialog($params){
		/*
		[name]
		[email] => 
		[phone] => 
		[link] =>  // w tym jest id 
		)*/
		//print_r($params); die;
		if(!array_key_exists('email' , $params) OR !array_key_exists('phone' , $params) OR !array_key_exists('link' , $params) ){
			die ( json_encode(array('status'=>0 , 'error' => 'Błąd formularza'  )));			
		}
		
		if(empty($params['email']) AND empty($params['phone']) ){
			die ( json_encode(array('status'=>0 , 'error' => 'Telefon lub Email są wymagane'  )));
		}		
		$ret = array();
		$ret['status'] = 1;
		if(!empty($params['email']) AND   !Validate::isEmail($params['email'])){
			$ret['status'] = 0;
			$ret['emailError'] = 'Błąd Email';
		}		
		if(!empty($params['phone']) and !Validate::isPhoneNumber($params['phone'])){
			$ret['status'] = 0;
			$ret['phoneError'] = 'Błąd Telefonu';
		}				
		if($ret['status'] == 0 ){
			die ( json_encode($ret));	
		}
 				
		$c = $this->context->cart;
		//print_r($this->context);
		
		
		$id_shop = $c->id_shop;
		$id_lang = (int)$c->id_lang;

		$link = new Link();
		$product = new Product((int)$params['link'], false, $id_lang, $id_shop);
		$product_link = $link->getProductLink($product, $product->link_rewrite, null, null, $id_lang, $id_shop);		
		
		
		$iso = Language::getIsoById($id_lang);
		
		$templateVars = array(
				'{product}' => (is_array($product->name) ? $product->name[$id_lang] : $product->name),
				'{product_link}' => $product_link,
				'{clientEmail}' => $params['email'],				
				'{clientPhone}' => $params['phone'],
				'{clientName}' => $params['name'],
			);
		
		//$ret = 100;
		$f= __FILE__;
		$d = dirname(__FILE__);
		if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/customer_query.html')){
			$r = Mail::Send(
				$id_lang, 
				'customer_query', 
				Mail::l('Zapytanie o cenę produktu', $id_lang),				
				$templateVars, 
				strval(Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop)),
				strval(Configuration::get('PS_SHOP_NAME', null, null, $id_shop)), 
				strval(Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop)), 
				strval(Configuration::get('PS_SHOP_NAME', null, null, $id_shop)), 
				NULL, 
				NULL, 
				dirname(__FILE__).'/mails/',
				false,
				$id_shop
			);
			$ret['status']  = $r;
			if($r == 0 ){				
				$ret['error'] = 'Nie udało się wysłac maila.';
			}			
		}
		else{
			;
		// jakis log?					
		//echo '<pre>'.print_r($params , true).'</pre>';
		}
		
		die (json_encode($ret));
		
		
	}	
	
	
}
