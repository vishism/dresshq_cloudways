<?php

class VariableFestiWooProduct extends AbstractFestiWooProduct
{
	public function removeAddToCartButton()
	{
        $this->adapter->addActionListener(
            'woocommerce_after_single_variation',
            'removeVariableAddToCartLinkAction'
        );
	} // end removeAddToCartButton
	
	public function getProductId($product)
	{
		$variationId = get_post_meta(
            $product->id,
            '_min_price_variation_id',
            true
        );
		
		return $variationId;
	} // end getProductId
	
	public function isAvaliableToDispalySavings($product)
	{
		return false;
	} // end isAvaliableToDispalySavings
	
	public function getMinOrMaxProductPice($product, $minOrMax)
	{
		$children = $this->getChildren($product);

		if (!$children) {
			return false;
		}
		
		$priceList = array();


		foreach ($children as $childrenId) {
			$product = $this->adapter->getProductInstance($childrenId);
			$price = $product->get_price();

			$priceList[] = $price;
		}

		return $minOrMax($priceList);
	}
}
