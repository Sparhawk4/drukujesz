<?php
class ImportpsBackOfficeController extends ModuleAdminController {

	public function __construct()

	{

		$this->lang = (!isset($this->context->cookie) || !is_object($this->context->cookie)) ? intval(Configuration::get('PS_LANG_DEFAULT')) : intval($this->context->cookie->id_lang);

		parent::__construct();

	}

	public function display(){

		parent::display();

	}

	public function renderList() {

		//$supplierArray = $this->getSuppliers();

		return $this->context->smarty->fetch(dirname(__FILE__).'/../../views/templates/admin/initial.tpl');

	}

	private function getSuppliers() {

		return Supplier::getSuppliers();

	}

}
?>
