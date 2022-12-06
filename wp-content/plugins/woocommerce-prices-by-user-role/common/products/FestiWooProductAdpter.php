<?php
if (!class_exists("AbstractFestiWooProduct")) {
	require_once dirname(__FILE__).'/AbstractFestiWooProduct.php';
}

class FestiWooProductAdpter
{
	private $_engine;
	private $_types = array(
		'simple',
		'variable',
		'grouped',
		'variation'
	);
	private $_instances = array();
	
	public function __construct($engine)
	{
		$this->_engine = $engine;
		$this->_prepareInstances();
		$this->onInit();
	} // end __construct
	
	private function _prepareInstances()
	{
		foreach ($this->_types as $type) {
			$className = ucfirst($type).'FestiWooProduct';
			
			$this->_onInitInstance($className);
			
			$this->_instances[$type] = new $className($this);
		}
	} // end _prepareInstances
	
	private function _onInitInstance($className)
	{
		$fileName = $className.'.php';
		$filePath = dirname(__FILE__).'/'.$fileName;
		
		if (!file_exists($filePath)) {
			throw new Exception("The ".$fileName." not found!");
		}
		
		require_once $filePath;
		
		if (!class_exists($className)) {
			$message = "The class ".$className." is not exists in ".$filePath;
			throw new Exception($message);
		}
	} // end _onInitInstance
	
	protected function onInit()
	{
		foreach ($this->_instances as $instance) {
			$instance->onInit();
		}
	} // end onInit
	
	public function getInstance($productType)
	{
		if (!array_key_exists($productType, $this->_instances)) {
			throw new Exception('Not found instance with type '.$productType);
		}
		
		return $this->_instances[$productType];
	} // end getInstance
	
    public function addActionListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        $this->_engine->addActionListener(
        	$hook,
        	$method,
        	$priority,
        	$acceptedArgs
		);
    } // end addActionListener
    
    public function addFilterListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        $this->_engine->addFilterListener(
        	$hook,
        	$method,
        	$priority,
        	$acceptedArgs
		);
    } // end addFilterListener
    
    public function removeAllLoopAddToCartLinks()
	{
        $this->addFilterListener(
            'woocommerce_loop_add_to_cart_link',
            'onRemoveAllAddToCartButtonFilter',
            10,
            2
        );
	} // end removeAllLoopAddToCartLinks
	
    public function removeLoopAddToCartLinksInSomeProducts()
	{
        $this->addFilterListener(
            'woocommerce_loop_add_to_cart_link',
            'onRemoveAddToCartButtonInSomeProductsFilter',
            10,
            2
        );
	} // end removeLoopAddToCartLinksInSomeProducts
	
	public function removeAddToCartButton($type = false)
	{
		if ($type) {
			$this->_instances[$type]->removeAddToCartButton();
			return true;
		}
		
		foreach ($this->_instances as $instance) {
			$instance->removeAddToCartButton();
		}
	} // end removeAddToCartButton
	
	public function replaceAllPriceToText()
	{
		$this->addFilterListener(
            'woocommerce_get_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            10,
            2
        );
		
		$this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            10,
            2
        );
	} // end replaceAllPriceToText
	
	public function replaceAllPriceToTextInSomeProduct()
	{
		$this->addFilterListener(
            'woocommerce_get_price_html',
            'onReplaceAllPriceToTextInSomeProductFilter',
            10,
            2
        );
		
		$this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onReplaceAllPriceToTextInSomeProductFilter',
            10,
            2
        );
	} // end replaceAllPriceToTextInSomeProduct
	
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
    
    public function onFilterPriceByRolePrice()
	{
        $this->addFilterListener(
            'woocommerce_get_price',
            'onDisplayPriceByRolePriceFilter',
            10,
            2
        );
	} // end onFilterPriceByRolePrice
	
    public function onFilterPriceByDiscountOrMarkup()
	{
        $this->addFilterListener(
            'woocommerce_get_price',
            'onDisplayPriceByDiscountOrMarkupFilter',
            10,
            2
        );
	} // end onFilterPriceByDiscountOrMarkup
	
	public function getUserPrice($product)
	{
		$type = $product->product_type;
		
		if (!$type){
			return false;
		}
		
		$productId = $this->_instances[$type]->getProductId($product);

		return $this->_engine->getRolePrice($productId);
	} // end getUserPrice
	
	public function onDisplayCustomerSavings()
	{
		$this->addFilterListener(
            'woocommerce_get_price_html',
            'onDisplayCustomerSavingsFilter',
            10,
            2
        );
		
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onDisplayCustomerSavingsFilter',
            10,
            2
        );
	} // end onDisplayCustomerSavings
	
	public function isAvaliableProductTypeToDispalySavings($product)
	{
		$type = $product->product_type;
		
		if (!$type){
			return false;
		}
		
		return $this->_instances[$type]->isAvaliableToDispalySavings($product);
	} // end isAvaliableProductTypeToDispalySavings
	
	public function isProductPage()
	{
		return $this->_engine->isProductPage();
	} // end isProductPage
	
	public function getMinOrMaxProductPice($product, $minOrMax)
	{
		$type = $product->product_type;
		
		if (!$type){
			return false;
		}
		
		$price = $this->_instances[$type]->getMinOrMaxProductPice(
			$product,
			$minOrMax
		);
		
		return $price;
	} // end getMinOrMaxProductPice
	
	public function getProductInstance($productId)
	{
		return $this->_engine->getProductInstance($productId);
	}
	
	public function getPriceRange($product)
	{
		$type = $product->product_type;
		
		if (!$type){
			return false;
		}
		
		return $this->_instances[$type]->getPriceRange($product);
	} // end getPriceRange
}
