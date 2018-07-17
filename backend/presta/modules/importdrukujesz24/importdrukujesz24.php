<?php
if (!defined('_PS_VERSION_'))
  exit;
 
class ImportDrukujesz24 extends Module
{
  public function __construct()
  {
    $this->name = 'importdrukujesz24';
    $this->tab = 'ImportDrukujesz24';
    
    $this->version = 1.0;
    $this->author = 'Paweł Skiba';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6.1.19');
 
    parent::__construct();
 
    $this->displayName = $this->l('ImportDrukujesz24');
    $this->description = $this->l('Import dla drukujesz24');
 
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');	
    if (!Configuration::get('IMPORTDRUKUJESZ24_PROVISION'))  
      $this->warning = $this->l('No provision provided');
  }
  
  
  public function getContent()
{
    $output = null;
 
    if (Tools::isSubmit('submit'.$this->name))
    {
        $my_module_name = strval(Tools::getValue('IMPORTDRUKUJESZ24_PROVISION'));
        if (!$my_module_name  || empty($my_module_name) || !Validate:: isFloat($my_module_name))
            $output .= $this->displayError( $this->l('Invalid Configuration value') );
        else
        {
            Configuration::updateValue('IMPORTDRUKUJESZ24_PROVISION', $my_module_name);
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
    }
    return $output.$this->displayForm();
}



public function displayForm()
{
    // Get default Language
    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
    
    

     
    // Init Fields form array
    $fields_form[0]['form'] = array(
        'legend' => array(
            'title' => $this->l('Settings'),
        ),
        'input' => array(
            array(
                'type' => 'text',
                'label' => $this->l('Provision'),
                'name' => 'IMPORTDRUKUJESZ24_PROVISION',
                'size' => 4,
                'required' => true
            )
        ),
        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'button'
        )
    );
     
    $helper = new HelperForm();
     
    // Module, t    oken and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
     
    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;
     
    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );
     
    // Load current value
    $helper->fields_value['IMPORTDRUKUJESZ24_PROVISION'] = Configuration::get('IMPORTDRUKUJESZ24_PROVISION');
     
    return $helper->generateForm($fields_form);
}
  
	public function install()
	{
	// hook
	//actionObjectMailAlertAddBefore
	
			
	$defaultLang = intval(Configuration::get('PS_LANG_DEFAULT'));
		
	
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'importerps_manufacruer_synonym` (
	`value` varchar(128) COLLATE utf8_bin NOT NULL,
	`synonym` varchar(128) CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY (`value`,`synonym`),
	UNIQUE KEY `synonym` (`synonym`)
	) ENGINE=InnoDB ;';
		if (!Db::getInstance()->Execute($sql)){
			return false;
		}
		
		
		$sql = ' CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'importerps_category_map` (
	  `classname` varchar(128) CHARACTER SET utf8 NOT NULL,
	  `id_xml` varchar(128) CHARACTER SET utf8 NOT NULL,
	  `id_category` int(11) NOT NULL  DEFAULT \'1\',
	  PRIMARY KEY (`classname`,`id_xml`),
	  UNIQUE KEY `id_category` (`id_category`)
	) ENGINE=InnoDB ;';
		if (!Db::getInstance()->Execute($sql)){
			return false;
		}
		
		$sql = ' CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'importerps_product_map` (
	  `classname` varchar(128) CHARACTER SET utf8 NOT NULL,
	  `id_xml` varchar(128) CHARACTER SET utf8 NOT NULL,
	  `id_product` int(10) NOT NULL,
	  PRIMARY KEY (`classname`,`id_xml`),
	  UNIQUE KEY `id_product` (`id_product`)
	) ENGINE=InnoDB ;';
	
	
	$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'importerps_parsers` (
  `classname` varchar(32) NOT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `provision` decimal(3,2) unsigned NOT NULL,
  PRIMARY KEY (`classname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

	if (!Db::getInstance()->Execute($sql)){
			return false;
		}	
	
	
	$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'importerps_parser_offer` (
		   `classname` varchar(32) NOT NULL,
			  `id_xml` varchar(64) NOT NULL,
			  `cena` decimal(8,2) NOT NULL,
			  `quantity` int(11) NOT NULL,
			  `json` blob NOT NULL,
			  PRIMARY KEY (`classname`,`id_xml`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	if (!Db::getInstance()->Execute($sql)){
			return false;
		}
	
		if (!Db::getInstance()->Execute($sql)){
			return false;
		}
		if(!Configuration::get('IMPORTDRUKUJESZ24_PROVISION'))
			Configuration::updateValue('IMPORTDRUKUJESZ24_PROVISION', 1.05);
		
		$catC = count(Category::searchByName($defaultLang , 'drukujesz24_nowe')) ;
		if($catC >1 ){
			$this->errors[] = $this->l('kategorii drukujesz24_nowe  >1');
			return false;			
		}
		if($catC == 0  ){
			$cat = new Category();
			$cat->active = 0;
			$cat->name[$defaultLang] =	'drukujesz24_nowe';
			$cat->link_rewrite[$defaultLang] = Tools::str2url('drukujesz24_nowe');
			$cat->id_parent = (int)Configuration::get('PS_HOME_CATEGORY') ;
			if(!$cat->add()){
				return false;
			}						
		}		
		
		
		
		$parent_tab = new Tab();
		
		foreach (Language::getLanguages(true) as $lang)
			$parent_tab->name[$lang['id_lang']] = 'import Drukujesz24';
		
        //$parent_tab->name = 'New Tab';
        $parent_tab->class_name = 'ImportpsBackOffice';
        $parent_tab->id_parent = 0;
        $parent_tab->module = $this->name;
        $parent_tab->add();
        if (!parent::install()
            // || !$this->installModuleTab1('AdminSubmenu1', array((int)(Configuration::get('PS_LANG_DEFAULT'))=>'import zerowy'), $parent_tab->id)
             //|| !$this->installModuleTab1('AdminCsvimport', array((int)(Configuration::get('PS_LANG_DEFAULT'))=>'import csv'), $parent_tab->id)             
             //|| !$this->installModuleTab1('AdminExcelimport', array((int)(Configuration::get('PS_LANG_DEFAULT'))=>'import excel'), $parent_tab->id)
             || !$this->installModuleTab1('AdminImportmerge', array((int)(Configuration::get('PS_LANG_DEFAULT'))=>'Import'), $parent_tab->id)
             || !$this->installModuleTab1('AdminImporttools', array((int)(Configuration::get('PS_LANG_DEFAULT'))=>'Narzędzia'), $parent_tab->id)
             || !$this->registerHook('actionObjectMailAlertAddBefore')
          )
            return false;
        return true;		
		
	}
	
	public function uninstall()
	{
		if (!parent::uninstall() ||!$this->uninstallModuleTab1('AdminImporttools') || !$this->uninstallModuleTab1('AdminImportmerge')  || !$this->uninstallModuleTab1('AdminCsvimport') || !$this->uninstallModuleTab1('AdminExcelimport')   /*|| !$this->uninstallModuleTab1('AdminSubmenu1') */  || !$this->uninstallModuleTab1('ImportpsBackOffice')){
				return false;
		}
		return true;
	 // return parent::uninstall() && Configuration::deleteByName('mymodule') && $this->uninstallModuleTab('ImportpsBackOffice');
	  
	  
	}	
	
	public function _____hookActionObjectMailAlertAddBefore($params){
		$mailAlertObject  = $params['object'];
		
		$id_shop = 1;
		$id_lang = (int)$mailAlertObject->id_lang;
		/*
		id_customer
		id_product	42304
		id_product_attribute	0
		customer_email	pawehs1831@gmail.com
		
		
		$id_shop = (int)$customer['id_shop'];
		$id_lang = (int)$customer['id_lang'];
		$context->shop->id = $id_shop;
		$context->language->id = $id_lang;
		*/	
		$link = new Link();
		$product = new Product((int)$mailAlertObject->id_product, false, $id_lang, $id_shop);
		$product_link = $link->getProductLink($product, $product->link_rewrite, null, null, $id_lang, $id_shop);		
		
		
		$iso = Language::getIsoById($id_lang);
		
		$templateVars = array(
				'{product}' => (is_array($product->name) ? $product->name[$id_lang] : $product->name),
				'{product_link}' => $product_link,
				'{clientEmail}' => $mailAlertObject->customer_email				
			);
		
		$ret = 100;
		$f= __FILE__;
		$d = dirname(__FILE__);
		if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/customer_query.html')){
			$ret = Mail::Send(
				$mailAlertObject->id_lang, 
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
		}
		else{
			;
		// jakis log?					
		//echo '<pre>'.print_r($params , true).'</pre>';
		}
		//echo 'ret'.(int)$ret;
	}
	
	
private function installModuleTab1($tabClass, $tabName, $idTabParent)
    {
       // $idTab = Tab::getIdFromClassName($idTabParent);       
        $idTab = $idTabParent;
        $pass = true ;
        @copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');
        $tab = new Tab();
        $tab->name = $tabName;
        $tab->class_name = $tabClass;
        $tab->module = $this->name;
        $tab->id_parent = $idTab;
        $pass = $tab->save();
        return($pass);
    }
    
    private function uninstallModuleTab1($tabClass)
    {
        $pass = true ;
        @unlink(_PS_IMG_DIR_.'t/'.$tabClass.'.gif');
        $idTab = Tab::getIdFromClassName($tabClass);
        if($idTab != 0)
        {
            $tab = new Tab($idTab);
            $pass = $tab->delete();
        }
        return($pass);
    }	
	
	
	
private function installModuleTab($tabClass, $tabName, $idTabParent)

	{

		@copy(_PS_MODULE_DIR_.$this->name.'/logo.png', _PS_IMG_DIR_.'t/'.$tabClass.'.png');

		$tab = new Tab();

		$tab->name = $tabName;

		$tab->class_name = $tabClass;

		$tab->module = $this->name;

		$tab->id_parent = $idTabParent;

		if(!$tab->save())

			return false;

		return true;

	}

	private function uninstallModuleTab($tabClass)

	{

		$idTab = Tab::getIdFromClassName($tabClass);

		if($idTab != 0)

		{

			$tab = new Tab($idTab);

			$tab->delete();

			@unlink( _PS_IMG_DIR."t/".$tabClass.".png");

			return true;

		}

		return false;

	}
}
?>
