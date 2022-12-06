<?php

class SimpleFestiWooProduct extends AbstractFestiWooProduct
{
	public function removeAddToCartButton()
	{
		remove_all_actions('woocommerce_simple_add_to_cart');
	} // end removeAddToCartButton
	
	public function getProductId($product)
	{
		return $product->id;
	} // end getProductId
	
	public function isAvaliableToDispalySavings($product)
	{
		return true;
	} // end isAvaliableToDispalySavings
}
