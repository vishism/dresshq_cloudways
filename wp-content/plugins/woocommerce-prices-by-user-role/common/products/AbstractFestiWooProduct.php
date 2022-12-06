<?php

class AbstractFestiWooProduct
{
	protected $_adapter;
	
	public function __construct($adapter)
	{
		$this->adapter = $adapter;
	} // end __construct
	
	public function onInit()
	{
	} //end onIni
	
	public function getMinOrMaxProductPice($product, $minOrMax)
	{
		return false;
	}
	
	public function getPriceRange($product)
	{
		return false;
	} // end getPriceRange

	public function getChildren($product)
	{
		return $product->get_children();
	} // end getChildren
}
