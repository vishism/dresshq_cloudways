<?php
if (!class_exists("FestiWooProductAdpter")) {
	require_once dirname(__FILE__).'/products/FestiWooProductAdpter.php';
}

class WooUserRolePricesFrontendFestiPlugin extends WooUserRolePricesFestiPlugin
{
	protected $settings;
	protected $userRole;
	protected $products;
	protected $eachProductId = 0;
	protected $removeLoopList = array();
	protected $textInsteadPrices;
	protected $mainProductOnPage = 0;
	
    protected function onInit()
    {
        if (!$this->_isSesionStarted()) {
            session_start();
        }

        $this->settings = $this->getOptions('settings');
		
        $this->addActionListener(
            'woocommerce_init',
            'onInitFiltersAction',
            10,
            2
        );
		
		$this->addActionListener('wp_print_styles', 'onInitCssAction');
		$this->addActionListener('wp_enqueue_scripts', 'onInitJsAction');
    } // end onInit
    
    protected function getProductsInstances()
	{
		return new FestiWooProductAdpter($this);
	} // end getProductsInstances
	
	    
    public function onInitFiltersAction()
    {
        $this->userRole = $this->getUserRole();
		
    	$this->products = $this->getProductsInstances();
		
		$this->addActionListener('wp', 'onInitMainProductIdAction');
		
        if ($this->_hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
			$this->onFilterPriceByDiscountOrMarkup();	
        } else {
			$this->onFilterPriceByRolePrice();
        }

		$this->onDisplayCustomerSavings();
		
		$this->onHideAddToCartButton();
		
		$this->onHidePrice();

		$this->onFilterPriceRanges();
	} // end onInitFiltersAction
	
	public function onInitMainProductIdAction()
	{
		$this->getMainProductId();
	} // end onInitMainProductIdAction
	
	protected function onFilterPriceRanges()
	{
		$this->addFilterListener(
            'woocommerce_get_variation_price',
            'onVariationPriceFilter',
            10,
            4
        );
		
		$this->addFilterListener(
            'woocommerce_get_variation_regular_price',
            'onVariationPriceFilter',
            10,
            4
        );
		
		$this->addFilterListener(
            'woocommerce_grouped_price_html',
            'onGroupedProductPriceRangeFilter',
            10,
            2
        );
	} // end onFilterPriceRanges
	
	public function onGroupedProductPriceRangeFilter($price, $product)
	{
		$from = $this->products->getMinOrMaxProductPice(
			$product,
			'min'
		);

		$from = $this->getFormattedPriceWithTax($product, $from);

		$to = $this->products->getMinOrMaxProductPice(
			$product,
			'max'
		);
		
		$to = $this->getFormattedPriceWithTax($product, $to);
		
		$display_price = $this->fetchGroupedProductPriceRange($from, $to);

		$price = $display_price.$product->get_price_suffix();
		
		return $price;
	} // end onGroupedProductPriceRangeFilter
	
	protected function fetchGroupedProductPriceRange($from, $to)
	{
		$template = '%1$s&ndash;%2$s';
		
		$content = _x($template, 'Price range: from-to', 'woocommerce');
		
		$content = sprintf($content, $from, $to);
		
		return $content;
	} // end fetchGroupedProductPriceRange
	
	protected function getMainProductId()
	{
		if ($this->mainProductOnPage) {
			return $this->mainProductOnPage;
		}
		
		if (!$this->isProductPage()) {
			return false;
		}
		
		$this->mainProductOnPage = get_the_ID();
		
		return $this->mainProductOnPage;
	} //end getMainProductId
	
	protected function onDisplayCustomerSavings()
	{
		if ($this->_isMarkupEnabledOrDiscountFromRolePrice()) {
			return false;
		}
		
		$this->products->onDisplayCustomerSavings();
		
        $this->addFilterListener(
            'woocommerce_cart_total',
            'onDisplayCustomerTotalSavingsFilter',
            10,
            2
        );
	} // end onDisplayCustomerSavings
	
	private function _isMarkupEnabledOrDiscountFromRolePrice()
	{
		return !$this->_isDiscountTypeEnabled()
			   && $this->_isRolePriceDiscountTypeEnabled();
	} // end _isMarkupEnabledOrDiscountFromRolePrice
	
    public function onDisplayCustomerTotalSavingsFilter($total)
    {
        if (!$this->_hasOptionInSettings('showCustomerSavings')
            || !$this->_isEnabledPageInCustomerSavingsOption('cartTotal')
            || !$this->_isRegisteredUser()) {
            return $total;
        }
        
        $woocommerce = $this->getWoocommerceInstance();
        
        $total = $woocommerce->cart->total;

        $totalDiff = $total - $woocommerce->cart->subtotal;
        
        $userTotal = $total;

        
        $retailTotal = $this->getRetailTotal($woocommerce) + $totalDiff;
        
        if (!$this->_isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)) {
            return $total;
        }

        $totalSavings = $this->getTotalSavings($retailTotal, $userTotal);

		$userTotal = $this->getFormattedPrice($userTotal);
		$retailTotal = $this->getFormattedPrice($retailTotal);

        $vars = array(
            'regularPrice' => $this->fetchPrice($retailTotal),
            'userPrice' => $this->fetchPrice($userTotal),
            'userDiscount' => $this->fetchTotalSavings($totalSavings)
        );
        
        return $this->fetch('customer_total_savings_price.phtml', $vars);
    } // end onDisplayCustomerTotalSavingsFilter
    
    public function onVariationPriceFilter($price, $product, $minOrMax, $display)
	{
		$userPrice = $this->products->getMinOrMaxProductPice(
			$product,
			$minOrMax
		);

		return ($userPrice || $userPrice == 0) ? $userPrice : $price;
	} // end onVariationPriceFilter
    
    public function getRetailTotal($woocommerce)
    {
        $products = $woocommerce->cart->cart_contents;

        $total = 0;

        foreach ($products as $key => $product) {
            if ($this->_isVariableProduct($product)) {
                $productId = $product['variation_id'];
            } else {
                $productId = $product['product_id'];
            }
			
			$productInstance = $this->getProductInstance($productId);
            $price = $this->getRegularPrice($productInstance);
            $total += $price * $product['quantity'];
        }
        
        return $total;
    } // end getRetailTotal
    
    private function _isVariableProduct($product)
    {
        return array_key_exists('variation_id', $product)
               && !empty($product['variation_id']);
    } // end _isVariableProduct
    
    public function &getWoocommerceInstance()
    {
        return $GLOBALS['woocommerce'];
    } // end getWoocommerceInstance
    
    public function fetchTotalSavings($totalSavings)
    {
        $vars = array(
            'discount' => $totalSavings
        );

        return $this->fetch('discount.phtml', $vars);
    } // end fetchTotalSavings
    
    public function fetchPrice($price)
    {
        $vars = array(
            'price' => $price
        );
        
        return $this->fetch('price.phtml', $vars);
    } // end fetchRegularPrice
    
    protected function getTotalSavings($retailTotal, $userTotal)
    {        
        $savings = round(100 - ($userTotal/$retailTotal * 100), 2);
        
        return $savings;
    } // end getTotalSavings
    
    private function _isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)
    {
        return $retailTotal > $userTotal;
    } // end _isRetailTotalMoreThanUserTotal
	
	public function onDisplayCustomerSavingsFilter(
		$price, $product
	)
	{
		$result = $this->_hasConditionsForDisplayCustomerSavingsInProduct(
            $product
        );

        if (!$result) {
            return $price;
        }

		$regularPrice = $this->getRegularPrice($product);
		
		$rgularPriceWithTax = $this->getPriceWithTax(
			$product,
			$regularPrice
		);
		
		$userPrice = $product->get_price();
		
		$userPriceWithTax = $this->getPriceWithTax(
			$product,
			$userPrice
		);
		
		$result = $this->_isAvaliablePricesToDisplayCustomerSavings(
			$rgularPriceWithTax,
			$userPriceWithTax	
		);
		
		if (!$result) {
			return $price;
		}
		
		$userDiscount = $this->fetchUserDiscount(
			$rgularPriceWithTax,
			$userPriceWithTax,
			$product
		);
		
		$regularPrice = $this->getFormattedPrice($rgularPriceWithTax);
		$userPrice = $this->getFormattedPrice($userPriceWithTax);

        $vars = array(
            'regularPrice' => $this->fetchPrice($regularPrice),
            'userPrice'    => $this->fetchPrice($userPrice),
            'userDiscount' => $userDiscount,
            'priceSuffix'  => $product->get_price_suffix()
        );
        
        return $this->fetch('customer_product_savings_price.phtml', $vars);
	} // end onDisplayPriceContentForSingleProductFilter
	
    private function _isAvaliablePricesToDisplayCustomerSavings(
    	$regularPrice, $userPrice
	)
    {
        return $userPrice !== false && $userPrice < $regularPrice;
	} // end _isAvaliablePricesToDisplayCustomerSavings
	
    public function fetchUserDiscount($regularPrice, $userPrice, $product)
    {
        $discount = round(100 - ($userPrice/$regularPrice * 100), 2);
        $vars = array(
            'discount' => $discount
        );

        return $this->fetch('discount.phtml', $vars);
    } // end fetchRegularPrice
	
	protected function getFormattedPrice($price)
	{
		return wc_price($price);
	} // end getFormattedPrice
	
	protected function getFormattedPriceWithTax($product, $price)
	{
		$price = $this->getPriceWithTax($product, $price);
		
		return $this->getFormattedPrice($price);
	} // end getFormattedPriceWithTax
	
	protected function getPriceWithTax($product, $price)
	{
		return $product->get_price_including_tax(1, $price);
	} // end getPriceWithTax
	
    private function _hasConditionsForDisplayCustomerSavingsInProduct(
        $product
    )
    {
        return $this->_hasOptionInSettings('showCustomerSavings')
               && $this->_isRegisteredUser()
               && $this->_isAllowedPageToDisplayCustomerSavings($product)
               && $this->_isAvaliableProductTypeToDispalySavings($product);
    } // end _hasConditionsForDisplayCustomerSavingsInProduct
    
    private function _isAvaliableProductTypeToDispalySavings($product)
    {
    	$result =  $this->products->isAvaliableProductTypeToDispalySavings(
    		$product
		);
		
		return $result;
    } // end _isAvaliableProductTypeToDispalySavings
    
    private function _isAllowedPageToDisplayCustomerSavings($product)
    {
        $isEnabledProductPage = $this->_isEnabledPageInCustomerSavingsOption(
            'product'
        );
        
        $isEnabledArchivePage = $this->_isEnabledPageInCustomerSavingsOption(
            'archive'
        );
        
        $mainProduct = $this->_isMainProductInSimpleProductPage($product);
        
        $isProductPage = $this->isProductPage();
		


        if ($isProductPage && $isEnabledProductPage && $mainProduct) {
            return true;
        }

        if (!$isProductPage && $isEnabledArchivePage) {
            return true;
        }
		
		if ($this->_isProductParentMainproduct($product, $mainProduct)) {
			return true;
		}

        return false;
    } // end _isAllowedPageToDisplayCustomerSavings
    
    private function _isProductParentMainproduct($product)
	{
		if (!$product->post->post_parent) {
            return false;
        }

        return $product->post->post_parent == $this->mainProductOnPage;
	} // end _isProductParentMainproduct
    
    private function _isMainProductInSimpleProductPage($product)
    {
        return $product->id == $this->mainProductOnPage;
    } // end _isMainProductInSimpleProductPage
    
    private function _isEnabledPageInCustomerSavingsOption($page)
    {
        return in_array($page, $this->settings['showCustomerSavings']);
    } // end _isEnabledPageInCustomerSavingsOption
	
	protected function onHidePrice()
	{
		if (!$this->_hasAvailableRoleToViewPricesInAllProducts()) {
			$this->products->replaceAllPriceToText();
			$this->removeFilter(
				'woocommerce_get_price_html',
				'onDisplayCustomerSavingsFilter'
			);
		} else {
			$this->products->replaceAllPriceToTextInSomeProduct();
		}
	} // end onHidePrice
	
	protected function removeFilter($hook, $methodName, $priority = 10)
	{
		remove_filter($hook, array($this, $methodName), $priority);
	} // end removeFilter
	
	protected function onHideAddToCartButton()
	{
		if ($this->_isEnabledHideAddToCartButtonOptionInAllProducts()) {
            $this->removeAllAddToCartButtons();
        } else {
        	$this->removeAddToCartButtonsInSomeProduct();
        }
	} // end onHideAddToCartButton
	
	protected function onFilterPriceByRolePrice()
	{
		$this->products->onFilterPriceByRolePrice();
	} // end onFilterPriceByRolePrice
	
	public function onDisplayPriceByRolePriceFilter($price, $product)
	{
        if (!$this->_isRegisteredUser()) {
            return $price;
        }
		
		$this->userPrice = $price;

        if (!$this->_hasUserRoleInActivePLuginRoles()) {
            return $this->_getPriceWithFixedFloat($this->userPrice);
        }
		
		$newPrice = $this->getPrice($product);

        if ($newPrice) {
            $this->userPrice = $newPrice;
            return $this->_getPriceWithFixedFloat($this->userPrice);
        }
        
        return $this->userPrice;
	} // end onDisplayPriceByRolePriceFilter
	
	protected function onFilterPriceByDiscountOrMarkup()
	{
		$this->products->onFilterPriceByDiscountOrMarkup();
	} // end onFilterPriceByDiscountOrMarkup
	
	public function onDisplayPriceByDiscountOrMarkupFilter($price, $product)
	{
        if (!$this->_isRegisteredUser()) {
            return $price;
        }

		$this->userPrice = $price;

		$this->userPrice = $this->getPriceWithDiscountOrMarkUp($product);
		
        return $this->_getPriceWithFixedFloat($this->userPrice);
	} // end onDisplayPriceByDiscountOrMarkupFilter
	
    private function _hasUserRoleInActivePLuginRoles()
    {
    	$roles = $this->getAllUserRoles();
		
        if (!$roles) {
            return false;
        }
        
        $activeRoles = $this->getActiveRoles();

        if (!$activeRoles) {
            return false;
        }
        
		
		$result =  $this->_hasOneOfUserRolesInActivePLuginRoles(
			$activeRoles,
			$roles
		);
		
		return $result;
    } // end _hasUserRoleInActivePLuginRoles
    
    private function _hasOneOfUserRolesInActivePLuginRoles($activeRoles, $roles)
    {
		$result = false;

        foreach ($roles as $key => $role) {
        	$result = array_key_exists($role, $activeRoles);
			
        	if ($result) {
        		return $result;
        	}
        }
    } // end _hasOneOfUserRolesInActivePLuginRoles
	
    private function _getPriceWithFixedFloat($price)
    {
        $price = str_replace(',', '.', $price);
		return floatval($price);
    } // end _getPriceWithFixedFloat
	
    public function getPriceWithDiscountOrMarkUp($product)
    {
        $amount = $this->getAmountOfDiscountOrMarkUp();
        $isNotRoleDiscountType = false;
        $price = 0;
        
        if ($this->_isRolePriceDiscountTypeEnabled()) {
            $price = $this->getPrice($product);
            
            if (!$price) {
                $isNotRoleDiscountType = true;
            }
        }

        if (!$price) {
            $price = $this->getRegularPrice($product);
        }
        
        if ($isNotRoleDiscountType) {
            return $price;
        }
        
        if ($this->_isPercentDiscountType()) {
            $amount = $this->getAmountOfDiscountOrMarkUpInPercentage(
                $price,
                $amount
            );
        }

        if ($this->_isDiscountTypeEnabled()) {
            $newPrice = ($amount > $price) ? 0 : $price - $amount;
        } else {
            $newPrice = $price + $amount;
        }
                
        return $newPrice;
    } // end getPriceWithDiscountOrMarkUp
    
    public function getRegularPrice($product)
    {
        return $product->get_regular_price();
    } // end getRegularPrice
    
    public function getAmountOfDiscountOrMarkUpInPercentage($price, $discount)
    {
        $discount = $price / 100 * $discount;
        
        return $discount;
    } // end getAmountOfDiscountOrMarkUpInPercentage
    
    private function _isDiscountTypeEnabled()
    {
        return $this->settings['discountOrMakeUp'] == 'discount';
    } // end _isDiscountTypeEnabled
    
    private function _isPercentDiscountType()
    {
        $options = $this->settings;
        
        return $options['discountByRoles'][$this->userRole]['type'] == 0;
    } // end _isPercentDiscountType
    
    public function getPrice($product)
    {
    	return $this->products->getUserPrice($product);
    } // end getPrices
    
    public function getRolePrice($id)
    {
        $roles = $this->getAllUserRoles();

		if (!$roles) {
			return false;
		}

        $priceList = $this->getMetaOptions($id, 'festiUserRolePrices');
        
        if (!$priceList) {
            return false;
        }
        
        $prices = $this->getAllRolesPrices($priceList, $roles);
        
        if (!$prices) {
            return false;
        }

        return min($prices);
    } // end getRolePrice
    
    protected function getAllRolesPrices($priceList, $roles)
    {
        $prices = array();

        foreach ($roles as $key => $role) {
            if (!$this->_hasRolePriceInProductOptions($priceList, $role)) {
                continue;
            }
            
            $prices[]= $this->_getPriceWithFixedFloat($priceList[$role]);
        }

        return $prices;
    } // end getAllRolesPrices
    
    private function _hasRolePriceInProductOptions($priceList, $role)
    {        
        return array_key_exists($role, $priceList) && $priceList[$role];
    } // end _hasRolePriceInProductOptions
    
    private function _isRolePriceDiscountTypeEnabled()
    {
        $options = $this->settings;
        $priceType = $options['discountByRoles'][$this->userRole]['priceType'];
        
        return $priceType == 'role';
    } // end _isRolePriceDiscountTypeEnabled
    
    public function getAmountOfDiscountOrMarkUp()
    {
        return $this->settings['discountByRoles'][$this->userRole]['value'];
    } // end getAmountOfDiscountOrMarkUp
	
    private function _hasDiscountOrMarkUpForUserRoleInGeneralOptions()
    {
        if (!$this->userRole) {
            return false;
        }
		
		$role = $this->userRole;
        $options = $this->settings;

        return array_key_exists('discountByRoles', $options)
               && array_key_exists($role, $options['discountByRoles'])
               && $options['discountByRoles'][$role]['value'] != 0;
    } // end _hasDiscountOrMarkUpForUserRoleInGeneralOptions
	
	public function onReplaceAllPriceToTextInSomeProductFilter($price, $product)
	{
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
        	return $this->fetchContentInsteadOfPrices();
        }

        return $price;
	} // end onReplaceAllPriceToTextInSomeProductFilter
	
	protected function removeAddToCartButtonsInSomeProduct()
	{
		$this->products->removeLoopAddToCartLinksInSomeProducts();
		$this->removeAddToCartButtonInProductPage();
	} // end removeAddToCartButtonsInSomeProduct
	
	protected function removeAddToCartButtonInProductPage()
	{
		if (!$this->isProductPage()) {
			return false;
		}
		
		$productId = get_the_ID();
		$product = $this->getProductInstance($productId);
		
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
        	$type = $product->product_type;		
			$this->products->removeAddToCartButton($type);
        }
	} // end removeAddToCartButtonInProductPage
	
	public function getProductInstance($productId)
	{
		$wooFactory = new WC_Product_Factory();
		$product = $wooFactory->get_product($productId);
		return $product;
	} // end getProductInstance
	
	public function isProductPage()
	{
		return is_product();
	} // end isProductPage
	
	public function onRemoveAddToCartButtonInSomeProductsFilter(
		$button, $product
	)
    {
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
        	return '';
        }

        return $button;
    } // end onRemoveAddToCartButtonInSomeProductsFilter
    
    private function _hasAvailableRoleToViewPricesInProduct($product)
	{
		if ($this->_isChildProduct($product)) {
			$parentID = $product->post->post_parent;
			$product = $this->getProductInstance($parentID);
		}

    	if (!$this->_isAvailablePriceInProductForUnregisteredUsers($product)) {
            $this->setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }

		if (!$this->_isAvailablePriceInProductForRegisteredUsers($product)) {
            $this->setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
		}
		
		return true;
	} // end _hasAvailableRoleToViewPricesInProduct
	
	private function _isChildProduct($product)
	{
		return isset($product->post->post_parent) 
			   && $product->post->post_parent != false;
	} // end _isChildProduct
	
	private function _isAvailablePriceInProductForUnregisteredUsers($product)
	{
		return $this->_isRegisteredUser() || (!$this->_isRegisteredUser()
               && !$this->_hasOnlyRegisteredUsersInProductSettings($product));
	} // end _isAvailablePriceInProductForUnregisteredUsers
	
	private function _hasOnlyRegisteredUsersInProductSettings($product)
	{
		$produtcId = $product->id;
		
		if (!$produtcId) {
			return false;
		}

        $options = $this->getMetaOptions(
            $produtcId,
            'festiUserRoleHidenPrices'
        );
        
        if (!$options) {
            return false;
        }

        return array_key_exists(
            'onlyRegisteredUsers',
            $options
        );
	} // end _hasOnlyRegisteredUsersInProductSettings
	
	private function _isAvailablePriceInProductForRegisteredUsers($product)
	{
        return !$this->_isRegisteredUser() || ($this->_isRegisteredUser()
           && !$this->_hasHidePriceOptionForRoleInProductSettings($product));
	} // end _isAvailablePriceInProductForRegisteredUsers
	
	protected function replaceAllPriceToTextInSomeProduct()
	{
		
	} // end replaceAllPriceToTextInSomeProduct
	
    public function onReplaceAllPriceToTextInAllProductFilter()
    {
        return $this->fetchContentInsteadOfPrices();
    } //end onReplaceAllPriceToTextInAllProductFilter
    
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
	
    private function _hasAvailableRoleToViewPricesInAllProducts()
    {
        if (!$this->_isAvailablePriceInAllProductsForUnregisteredUsers()) {
            $this->setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }

		if (!$this->_isAvailablePriceInAllProductsForRegisteredUsers()) {
            $this->setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
		}

        return true;
    } // end _hasAvailableRoleToViewPricesInAllProducts
    
    private function _isAvailablePriceInAllProductsForRegisteredUsers()
    {
        return !$this->_isRegisteredUser() || ($this->_isRegisteredUser()
               && !$this->_hasHidePriceOptionForRoleInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForRegisteredUsers
    
    public function setValueForContentInsteadOfPrices($optionName)
    {
        $this->textInsteadPrices = $this->settings[$optionName];
    } // end getContentInsteadOfPrices
    
    private function _isAvailablePriceInAllProductsForUnregisteredUsers()
    {
        return $this->_isRegisteredUser() || (!$this->_isRegisteredUser()
               && !$this->_hasOnlyRegisteredUsersInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInGeneralSettings()
    {
        return array_key_exists('onlyRegisteredUsers', $this->settings);
    } // end _hasOnlyRegisteredUsersInGeneralSettings
	
    public function removeAllAddToCartButtons()
    {
        $this->products->removeAllLoopAddToCartLinks();
		$this->products->removeAddToCartButton();
    } //end removeAllAddToCartButtons
    
    public function removeGroupedAddToCartLinkAction()
    {
        echo $this->fetch('hide_grouped_add_to_cart_buttons.phtml');
    } // end removeGroupedAddToCartLinkAction
    
    public function removeVariableAddToCartLinkAction()
    {
        echo $this->fetch('hide_variable_add_to_cart_buttons.phtml');
    } // end removeVariableAddToCartLinkAction
    
    public function onRemoveAllAddToCartButtonFilter($button, $product)
    {
        return '';
    } // end onRemoveAddToCartButtonFilter
	
    private function _isEnabledHideAddToCartButtonOptionInAllProducts()
    {
        return (!$this->_isRegisteredUser() 
                  && $this->_hasHideAddToCartButtonOptionInSettings())
               || ($this->_isRegisteredUser() 
                  && ($this->_hasHideAddToCartButtonOptionForUserRole()
                     || $this->_hasHidePriceOptionForRoleInGeneralSettings()));
    } // end _isEnabledHideAddToCartButtonOptionInAllProducts
    
    private function _hasHidePriceOptionForRoleInProductSettings($product)
    {
    	$produtcId = $product->id;
		
		if (!$produtcId) {
			return false;
		}
        
        $options = $this->getMetaOptions(
            $produtcId,
            'festiUserRoleHidenPrices'
        );
        
        if (!$options) {
            return false;
        }
        
        if (!array_key_exists('hidePriceForUserRoles', $options)) {
            return false;
        }

        return $options && array_key_exists(
            $this->userRole,
            $options['hidePriceForUserRoles']
        );
    } // end _hasHidePriceOptionForRoleInProductSettings
    
    private function _hasHidePriceOptionForRoleInGeneralSettings()
    {   
        return array_key_exists('hidePriceForUserRoles', $this->settings)
               && array_key_exists(
                    $this->userRole,
                    $this->settings['hidePriceForUserRoles']
               );
    } // end _hasHidePriceOptionForRoleInGeneralSettings
    
    private function _hasHideAddToCartButtonOptionForUserRole()
    {
        $key = 'hideAddToCartButtonForUserRoles';
        
        return array_key_exists($key, $this->settings)
               && array_key_exists($this->userRole, $this->settings[$key]);
    } //end _hasHideAddToCartButtonOptionForUserRole
    
    private function _hasHideAddToCartButtonOptionInSettings()
    {
        return array_key_exists('hideAddToCartButton', $this->settings);
    } //end _hasHideAddToCartButtonOptionInSettings
    
    private function _isRegisteredUser()
    {
        return $this->userRole;
    } // end _isRegisteredUser
	
    public function getUserRole()
    {
        $roles = $this->getAllUserRoles();

        return $roles[0];
    } // end getUserRole
    
    public function getAllUserRoles()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            return false;    
        }
        
        $userData = get_userdata($userId);

        return $userData->roles;
    } // end getAllUserRoles
    
    public function getUserId()
    {
        if (defined('DOING_AJAX') && $this->_hasUserIdInSessionArray()) {
            return $_SESSION['userIdForAjax'];
        }

        $userId = get_current_user_id();
        
        return $userId;
    } // end getUserId
    
    private function _hasUserIdInSessionArray()
    {
        return isset($_SESSION['userIdForAjax']);
    } // end _hasUserIdInSessionArray
    
    private function _isSesionStarted()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE;
            } else {
                return session_id() === '';
            }
        }
        return false;
    } // end _isSesionStarted

    public function getPluginTemplatePath($fileName)
    {
        return $this->_pluginTemplatePath.'frontend/'.$fileName;
    } // end getPluginTemplatePath
    
    public function getPluginJsUrl($fileName)
    {
        return $this->_pluginJsUrl.'frontend/'.$fileName;
    } // end getPluginJsUrl
    
    public function getPluginCssUrl($path) 
    {
        return $this->_pluginUrl.$path;
    } // end getPluginCssUrl
    
    public function onInitJsAction()
    {
    	$this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-general',
            'general.js',
            'jquery',
            $this->_version
        );
    } // end onInitJsAction
    
    public function onInitCssAction()
    {
        $this->addActionListener(
            'wp_head',
            'appendCssToHeaderForCustomerSavingsCustomize'
        );

        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-styles',
            'static/styles/frontend/style.css',
            array(),
            $this->_version
        );
    } // end onInitCssAction
    
    public function appendCssToHeaderForCustomerSavingsCustomize()
    {
        if (!$this->_hasOptionInSettings('showCustomerSavings')) {
            return false;
        }
        
        $vars = array(
            'settings' => $this->settings,
        );

        echo $this->fetch('customer_savings_customize_style.phtml', $vars);
    } // end appendCssToHeaderForPriceCustomize
    
    private function _hasOptionInSettings($option)
    {
        return array_key_exists($option, $this->settings);
    } // end _hasOptionInSettings
}