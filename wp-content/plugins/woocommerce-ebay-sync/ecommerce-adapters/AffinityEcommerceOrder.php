<?php
require_once(ABSPATH . 'wp-admin/includes/screen.php');

class AffinityEcommerceOrder {
	public $id;
	public $clientId;
	public $orderDate;
	public $modifiedDate;
	public $totalPrice;
	public $status;
	
	public static function get($id) {
		$objNativeOrder = new WC_Order($id);
		$objEcommerceOrder = self::transformNativeOrderIntoEcommerceOrder($objNativeOrder);
		return $objEcommerceOrder;
    }
	
	public static function createOrderReceivedFromEbay($objAffinityOrder) {
		require_once(__DIR__.'/../model/AffinityLog.php');
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Creating Order in Ecommerce", print_r($objAffinityOrder, true));
		
		$address = array(
            'first_name' => $objAffinityOrder->firstName,
            'last_name'  => $objAffinityOrder->lastName,
            'company'    => "eBay Buyer ID: " . $objAffinityOrder->ebayBuyerId,
            'email'      => $objAffinityOrder->email,
            'phone'      => $objAffinityOrder->phone,
            'address_1'  => $objAffinityOrder->addressLine1,
            'address_2'  => $objAffinityOrder->addressLine2, 
            'city'       => $objAffinityOrder->city,
            'state'      => $objAffinityOrder->state,
            'postcode'   => $objAffinityOrder->postCode,
            'country'    => $objAffinityOrder->country
        );

        $objNativeOrder = wc_create_order();
        $objNativeOrder->set_address($address, 'billing');
        $objNativeOrder->set_address($address, 'shipping');
        
        $orderTotal = 0;
        
        foreach ($objAffinityOrder->ebayItemId as $kk=>$v) {
        
			$options = array('eBay Item' => $objAffinityOrder->ebayItemId[$kk]);
	        
			if($objAffinityOrder->productId[$kk] > 0 && strpos($objAffinityOrder->productId[$kk], '-') === false) {
				if ($objAffinityOrder->variationId[$kk] > 0 && strpos($objAffinityOrder->variationId[$kk], '-') === false) {
					$prod = new WC_Product_Variation($objAffinityOrder->variationId[$kk]);
					$varrs = $prod->get_variation_attributes();
					if (!empty($objAffinityOrder->variationDetails)) {
						foreach ($objAffinityOrder->variationDetails as $v=>$k) {
							$options[$v] = $objAffinityOrder->variationDetails[$v];
						}
					} else {
						foreach ($varrs as $v=>$k) {
							$options[$v] = $k;
						}
					}
				} else {
					$prod = wc_get_product($objAffinityOrder->productId[$kk]);
				}
				
				$totals = array(
						'subtotal' => $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk],
						'total' => $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk],
						'subtotal_tax' => 0,
						'tax' => 0
				);
				
				$objNativeOrder->add_product($prod, $objAffinityOrder->qty[$kk], array('variation' => $options, 'totals' => $totals));
				
				$objNativeOrder->reduce_order_stock();
				
				$allOrderItems = $objNativeOrder->get_items();
				$productItem = array_pop($allOrderItems);
				$orderTotal += $objNativeOrder->get_line_total($productItem);
			}
			else {
				$objProductAsFee = new stdClass();
				$objProductAsFee->name = $objAffinityOrder->productDescription[$kk];
				$objProductAsFee->amount = $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk];
				$objProductAsFee->taxable = false; //price already includes taxes
				$objProductAsFee->tax_data = array(); 
				$objNativeOrder->add_fee($objProductAsFee);
				$orderTotal += $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk];
			}
        }
		
		$objShippingAsFee = new stdClass();
		$objShippingAsFee->name = $objAffinityOrder->shippingDescription;
		$objShippingAsFee->amount = $objAffinityOrder->shippingPrice;
		$objShippingAsFee->taxable = false; //price already includes taxes
		$objShippingAsFee->tax_data = array(); 
		$objNativeOrder->add_fee($objShippingAsFee);
		$orderTotal += $objAffinityOrder->shippingPrice;
		
		if($orderTotal !== $objAffinityOrder->totalPaid) {
			$objTaxAndExtras = new stdClass();
			$objTaxAndExtras->name = "Tax and Extras";
			$objTaxAndExtras->amount = floatval($objAffinityOrder->totalPaid) - $orderTotal;
			$objTaxAndExtras->taxable = false;
			$objTaxAndExtras->tax_data = array(); 
			$objNativeOrder->add_fee($objTaxAndExtras);
		}
		$objNativeOrder->set_total($objAffinityOrder->totalPaid);
		
		$objNativeOrder->update_status('processing', 'New Order Received from eBay!');
		$objEcommerceOrder = self::transformNativeOrderIntoEcommerceOrder($objNativeOrder);
		return $objEcommerceOrder;
	}
	
    public static function orderHasChanged($objWpPost) {
		$objEcommerceOrder = self::get($objWpPost->ID);
		
		require_once(__DIR__. '/../model/AffinityOrder.php');
		AffinityOrder::orderChanged($objEcommerceOrder);
	}
	
	public static function getEditOrderLink($orderId) {
		return get_edit_post_link($orderId);
	}
	
	private static function transformNativeOrderIntoEcommerceOrder($objNativeOrder) {
		$objEcommerceOrder = new AffinityEcommerceOrder();
		
		$objEcommerceOrder->id = $objNativeOrder->id;
		$objEcommerceOrder->clientId = $objNativeOrder->get_user_id();
		$objEcommerceOrder->orderDate = $objNativeOrder->order_date;
		$objEcommerceOrder->modifiedDate = $objNativeOrder->modified_date;
		$objEcommerceOrder->status = $objNativeOrder->get_status();
		$objEcommerceOrder->totalPrice = $objNativeOrder->get_total();
		
		return $objEcommerceOrder;
	}
	
	
}