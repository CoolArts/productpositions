<?php
if (!defined('_PS_VERSION_')) exit;

class ProductPositions extends Module {
	public function __construct() {
		$this->name = 'productpositions';
		$this->tab = 'migration_tools';
		$this->version = '1.0';
		$this->author = 'CoolArts';
		$this->need_instance = 0;
        $this->Db = Db::getInstance();
        
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Product Positions');
		$this->description = $this->l('Fix positions for products in parent categories.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}
	public function install() {
		return parent::install();
	}
	public function uninstall() {
		return parent::uninstall();
	}
	
	public function getContent() {
        if (Tools::isSubmit('OLD_POSITIONS')) {
            
        }
        		
		if ($this->context->controller->controller_type == 'admin')
			return $this->renderForm();
	}
    
	private function renderForm() {
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Actualiza las posiciones de los productos en las categorías padre'),
					'icon' => 'icon-cogs'
				),
                
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('¿Mantener posiciones existentes?'),
						'name' => 'OLD_POSITIONS',
						'desc' => $this->l('Atualiza sólo los productos que no estén incluidos en sus categorías padre'),
						'values' => array(
                            array(
                                'id' => 'active_off',
                                'value' => 1,
                            ),
                            array(
                                'id' => 'active_on',
                                'value' => 0,
                            )
                        ),
					),
				),
                
              'submit' => array(
					'title' => $this->l('Actualizar'),
				)
			),
		);
		
		$helper = new HelperForm();
		$helper->show_toolbar = true;
		
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitShoppingProducts';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->fieldsValues,
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}
}
