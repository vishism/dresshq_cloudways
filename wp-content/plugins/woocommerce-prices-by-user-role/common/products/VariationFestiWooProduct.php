<?php

class VariationFestiWooProduct extends AbstractFestiWooProduct
{
	public function removeAddToCartButton()
	{
	} // end removeAddToCartButton
	
	public function getProductId($product)
	{
		return $product->variation_id;
	} // end getProductId
	
	public function isAvaliableToDispalySavings($product)
	{
		return true;
	} // end isAvaliableToDispalySavings
}
