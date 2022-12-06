<?php

class GroupedFestiWooProduct extends AbstractFestiWooProduct
{
	public function removeAddToCartButton()
	{
        $this->adapter->addActionListener(
            'woocommerce_after_add_to_cart_button',
            'removeGroupedAddToCartLinkAction'
        );
	} // end removeAddToCartButton
	
	public function getProductId($product)
	{
		return $product->id;
	} // end getProductId
	
	public function isAvaliableToDispalySavings($product)
	{
		return $this->adapter->isProductPage();
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
