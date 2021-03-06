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
            $this->flushProducts();
            $this->updatePositions();
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
        $helper->currentIndex =
            $this->context->link->getAdminLink('AdminModules', false).
            '&configure='.$this->name.
            '&tab_module='.$this->tab.
            '&module_name='.$this->name;
        
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->fieldsValues,
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array($fields_form));
    }
    
    private function flushProducts () {
        $query = 'SELECT `id_product`, `id_category_default` FROM `'._DB_PREFIX_.'product`';
        
        if (!$data = $this->Db->ExecuteS($query)) return;
        if (count($data) < 1) return;
        
        $root = $this->getRootCategories();
        
        //Add position switch functionality
        $max = (int)$this->getMaxPosition()+10;
        
        foreach ($data as $product) {
            if (in_array($product['id_category_default'], $root)) continue;
            
            // The method Category::getParentsCategories is overriden to accept and id
            $parents = Category::getParentsCategories($this->context->language->id, $product['id_category_default']);
            
            if (count($parents) > 0) {
                foreach ($parents as $parent) {
                    $this->Db->insert(
                        'category_product',
                        array(
                            'id_product' => $product['id_product'],
                            'id_category' => $parent['id_category'],
                            //Add position switch functionality
                            'position' => $max,
                        )
                    );
                }
            }
        }
        
        $this->updatePositions();
    }
    private function getMaxPosition () {
        $max = 'SELECT MAX(`position`) AS `max` FROM `'._DB_PREFIX_.'category_product`';
        $maxPos = $this->Db->ExecuteS($max);
        return $maxPos[0]['max'];
    }
    private function updatePositions () {
        $categories = $this->getRootCategories(0);
        
        //Add position switch functionality
        foreach ($categories as $category) {
            $query = '
                SELECT cp.* FROM `'._DB_PREFIX_.'category_product` cp
                INNER JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = cp.`id_product`
                WHERE cp.`id_category` = '.$category.'
                ORDER BY p.`active` DESC, cp.`position` ASC
            ';
            
            $positions = $this->Db->ExecuteS($query);
            $counter = 0;
            foreach ($positions as $pos) {
                extract($pos);
                
                $queryb = 'UPDATE `category_product` SET `position` = '.$counter.' WHERE `id_category` = '.$id_category.' AND `id_product` = '.$id_product;
                
                $this->Db->ExecuteS($queryb);
                $counter++;
            }
        }
    }
    private function getRootCategories ($root = 1) {
        $roots = 'SELECT `id_category` FROM category WHERE is_root_category = '.$root;
        $root = $this->Db->ExecuteS($roots);
        
        foreach ($root as $k => $v) $root[$k] = $v['id_category'];
        return $root;
    }
}
