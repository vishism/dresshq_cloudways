<?php

class AffinityEcommerceItemSpecific {
    const TYPE_LOCAL_ATTRIBUTE = 1;
    const TYPE_GLOBAL_ATTRIBUTE = 2;
	
	private static $_cachedArrAttributeTaxonomyObjects = null;
	
	public $id;
	public $name;
	public $label;
	public $type;
    public $arrValues = array();
	
	public static function getVariationAttributesAsItemSpecifics($productVariationId) {
		$return = array();
		$arrAllAttributes = self::getItemSpecifics();
		
		$objNativeProductVariation = new WC_Product_Variation($productVariationId);
		
		$attr = get_post_meta($objNativeProductVariation->parent->id, '_product_attributes');
		
		foreach($objNativeProductVariation->get_variation_attributes() as $attributeName => $arrValues) {
			$attributeName = preg_replace(array("/^attribute_/"), "", $attributeName);
			
			if(!isset($arrAllAttributes[$attributeName])) {
				continue;
			}
			
			$objItemSpecific = $arrAllAttributes[$attributeName];
			$objItemSpecific->arrValues = is_array($arrValues) ? $arrValues : array($arrValues);
			
			if (affinity_empty($objItemSpecific->arrValues[0])) {
				if (substr($objItemSpecific->name, 0, 3) === 'pa_') {
					$objItemSpecific->arrValues = wp_get_post_terms($objNativeProductVariation->parent->id, $objItemSpecific->name, array('fields' => 'names'));
				} else {
					foreach ($attr[0] as $att) {
						if ($att['name'] === $objItemSpecific->name) {
							$objItemSpecific->arrValues = explode('|', $att['value']);
							foreach ($objItemSpecific->arrValues as $k=>$v) {
								$objItemSpecific->arrValues[$k] = trim($v);
							}
						}
					}
				}
			} else {
				if (substr($objItemSpecific->name, 0, 3) === 'pa_') {
					$term = get_term_by('slug', $objItemSpecific->arrValues[0], $objItemSpecific->name);
					if ($term !== false) {
						$objItemSpecific->arrValues[0] = $term->name;
					}
				}
			}
			
			$return[] = $objItemSpecific;
		}
		return $return;
	}
	
	public static function getArrUniqueItemSpecificsIdsAndValues($arrRepeatedItemSpecifics) {
		if(!is_array($arrRepeatedItemSpecifics)) {
			return array();
		}
		
		$return = array();
		foreach($arrRepeatedItemSpecifics as $objItemSpecific) {
			if(!isset($return[$objItemSpecific->id])) {
				$return[$objItemSpecific->id] = clone $objItemSpecific;
			}
			
			$existingItemSpecific = $return[$objItemSpecific->id];
			
			foreach($objItemSpecific->arrValues as $value) {
				$existingItemSpecific->addValue($value);
			}
		}
		
		return $return;
	}
	
	private function addValue($value) {
		if(!in_array($value, $this->arrValues, true)) {
			$this->arrValues[] = $value;
		}
	}
	
	public static function getProductAttributes($objNativeProduct) {
		return self::getItemSpecifics($objNativeProduct->id);
	}
	
	public static function getAllUsedAttributes() {
		return self::getItemSpecifics();
	}
	
	public static function getAttributeTaxonomyLabel($attributeTaxonomyName) {
		if(self::$_cachedArrAttributeTaxonomyObjects === null) {
			self::$_cachedArrAttributeTaxonomyObjects = wc_get_attribute_taxonomies();
		}
		
		foreach(self::$_cachedArrAttributeTaxonomyObjects as $objAttributeTaxonomy) {
			$attributeName = "pa_" . $objAttributeTaxonomy->attribute_name;
			
			if($attributeName === $attributeTaxonomyName) {
				return $objAttributeTaxonomy->attribute_label;
			}
		}
		
		return $attributeTaxonomyName; //if it hasn't found, returns own name
	}
	
	private static function getItemSpecifics($productId = null) {
		global $wpdb;
		
		$productIdWhereQuery = "";
		if($productId !== null) {
			$productIdWhereQuery = "pm.post_id = $productId AND ";
		}
		
		$arrWpResults = $wpdb->get_results("SELECT meta_value FROM " . $wpdb->prefix . "postmeta pm WHERE "
				. $productIdWhereQuery
				. "pm.meta_key = '_product_attributes' AND "
				. "pm.meta_value != 'a:0:{}'");
		
		$arrDifferentAttributes = array();
		foreach($arrWpResults as $objWpResult) {
			$arrAttributes = unserialize($objWpResult->meta_value);
			
			foreach($arrAttributes as $attributeName => $arrAttribute) {
				$objItemSpecific = new AffinityEcommerceItemSpecific();
				$objItemSpecific->id = $attributeName;
				$objItemSpecific->type = $arrAttribute['is_taxonomy'] ? self::TYPE_GLOBAL_ATTRIBUTE : self::TYPE_LOCAL_ATTRIBUTE;
				$objItemSpecific->label = ($objItemSpecific->type === self::TYPE_LOCAL_ATTRIBUTE) ? $arrAttribute['name'] : self::getAttributeTaxonomyLabel($attributeName);
				$objItemSpecific->name = $arrAttribute['name'];
				
				if($productId !== null) {
					if($objItemSpecific->type === self::TYPE_GLOBAL_ATTRIBUTE) {
						$objItemSpecific->arrValues = wp_get_post_terms($productId, $attributeName, array('fields' => 'names'));
					}
					else {
						$objItemSpecific->arrValues = wc_get_text_attributes($arrAttribute['value']);
					}
				}
				
				$arrDifferentAttributes[$objItemSpecific->id] = $objItemSpecific;
			}
		}
		
		return $arrDifferentAttributes;
	}
	
}
	
	
