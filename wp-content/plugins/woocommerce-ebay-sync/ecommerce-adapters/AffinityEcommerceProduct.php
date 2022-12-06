<?php

class AffinityEcommerceProduct {
	public $id;
	public $title;
	public $shortDescription;
	public $description;
	public $priceIncludingTax;
	public $isStockBeingManaged;
	public $qtyAvailable;
	public $status;
	
	public $objMainImage;
	public $arrObjAdditionalImages;
	
	public $arrEcommerceItemSpecifics = array();
	public $arrEcommerceCategories = array();
	public $arrEcommerceShipping = array();
	
	public $arrEcommerceProductVariations = array();
	public $arrAllVariationDifferentItemSpecifics = array();
	public $arrCurrentVariationItemSpecifics = array();
    
	private function getVariations() {
		$objWpQuery = new WP_Query(array(
            'post_type' => 'product_variation', 
            'post_parent' => $this->id
        ));
        
        if(!$objWpQuery->have_posts()) {
            return array();
        }
		
		require_once(__DIR__ . "/AffinityEcommerceItemSpecific.php");
        
        $arrEcommerceProductVariations = array();
		$arrVariationItemSpecifics = array();
		
		while($objWpQuery->have_posts()) {
			$objWpQuery->the_post();
			
			$objNativeProduct = new WC_Product ( get_the_ID() );
			$objVariationProduct = self::transformNativeProductIntoEcommerceProduct($objNativeProduct);
			
			$arrVariationItemSpecific = AffinityEcommerceItemSpecific::getVariationAttributesAsItemSpecifics( $objVariationProduct->id );
			$arrVariationItemSpecifics = array_merge($arrVariationItemSpecifics, $arrVariationItemSpecific);
			$objVariationProduct->arrCurrentVariationItemSpecifics = $arrVariationItemSpecific;
			
			$arrEcommerceProductVariations[] = $objVariationProduct;
		}
		
		$this->arrAllVariationDifferentItemSpecifics = AffinityEcommerceItemSpecific::getArrUniqueItemSpecificsIdsAndValues($arrVariationItemSpecifics);
		return $arrEcommerceProductVariations;
    }
	
    public static function get($id) {
		$objNativeProduct = new WC_Product($id);
		$objEcommerceProduct = self::transformNativeProductIntoEcommerceProduct($objNativeProduct);
		return $objEcommerceProduct;
    }
	
    /*
	 * @Todo
	 * Filter:	Virtual/Downloadable Products
	 *			Not Stock Managed
	 */
    public static function getAll($arrCustomFilters = null) {
		global $wp_query;
		
        if(!is_array($arrCustomFilters)) {
            $arrCustomFilters = array();
        }
        
        $arrFilters = array_merge(
			array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'tax_query' => array(
						'relation' => 'OR',
						array(
								'taxonomy' => 'product_type',
								'field' => 'slug',
								'terms' => array('simple', 'variable'),
								'operator' => 'IN'
						),
						array(
								'taxonomy' => 'product_type',
								'operator' => 'NOT EXISTS',
						)
				)
			), 
			$arrCustomFilters);
        $wp_query = new WP_Query($arrFilters);
        
        if(!$wp_query->have_posts()) {
            return false;
        }
        
		$arrEcommerceProducts = array();
		while($wp_query->have_posts()) {
			$wp_query->the_post();
			$objNativeProduct = new WC_Product(get_the_ID());
			
			$arrEcommerceProducts[] = self::transformNativeProductIntoEcommerceProduct($objNativeProduct);
		}
		
		return $arrEcommerceProducts;
    }
	
	private static function getItemSpecifics($objNativeProduct) {
		require_once(__DIR__. '/AffinityEcommerceItemSpecific.php');
		return AffinityEcommerceItemSpecific::getProductAttributes($objNativeProduct);
	}
	
	private static function getEcommerceCategories($objNativeProduct) {
		require_once(__DIR__. '/AffinityEcommerceCategory.php');
		return AffinityEcommerceCategory::getProductCategories($objNativeProduct);
	}
	
	private static function getEcommerceShipping($objNativeProduct) {
		require_once(__DIR__. '/../model/AffinityShippingRule.php');
		return AffinityShippingRule::generate($objNativeProduct);
	}
	
	private static function transformTemplate($obj) {
		$dir = wp_upload_dir();
		$penicilurl = str_replace('ecommerce-adapters/assets/search2.png', 'assets/search2.png', plugins_url('assets/search2.png', __FILE__));
		$ebayurl = get_option('ebayaffinity_ebaysite');
		$userid = get_option('ebayaffinity_ebayuserid');
		$searchurl = $ebayurl.'sch/'.rawurlencode($userid).'/m.html';
		$template = get_option('ebayaffinity_customtemplate');
		if (empty($template)) {
			$template = file_get_contents(__DIR__. '/../assets/product.html');
		}
		$logo = get_option('ebayaffinity_logo');
		$storelogo = '';

		if (!empty($logo)) {
			$surl = $ebayurl.'usr/'.rawurlencode($userid);
			$storelogo = '<a target="_top" href="'.esc_html($surl).'"><img src="'.esc_html($dir['baseurl'].'/'.$logo).'" alt=""></a>';
		}
		$template = str_replace('[[STORELOGO]]', $storelogo, $template);
		$template = str_replace('[[DESC]]', $obj->description, $template);
		
		$bin = '<script type="text/javascript">document.write(\'<a class="ebayaffinity_producttable_buynow" href="\'+'.json_encode(esc_html(get_option('ebayaffinity_bin'))).'+ebayItemID+\'">Buy now<\/a>\')</script>';
		
		$img = '';
		if (!empty($obj->objMainImage->fullUrl)) {
			$img = '<img src="'.esc_html($obj->objMainImage->fullUrl).'" alt="">';
		}
		$template = str_replace('[[IMG]]', $img, $template);
		$template = str_replace('[[TITLE]]', esc_html($obj->title), $template);
		$template = str_replace('[[PRICE]]', $obj->stylepriceIncludingTax, $template);
		$template = str_replace('[[BINCLICK]]', $bin, $template);
		$template = str_replace('[[SEARCHURL]]', $searchurl, $template);
		$template = str_replace('[[PENCILURL]]', $penicilurl, $template);
		
		// We should remove unwanted HTML tags. The only real way to do this is using DOMDocument, but it's not always available.
		// Some people try to do it with regular expressions, but such implementations tend to be somewhat broken.
		if (class_exists('DOMDocument')) {
			libxml_clear_errors();
				
			$template = htmlspecialchars_decode(htmlspecialchars($template, ENT_IGNORE, 'UTF-8'));
			$template = preg_replace('/[^\PC\s]/u', '', $template);
				
			$template2 = '<!DOCTYPE html><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>'.$template.'</body></html>';
			$dom = new DOMDocument();
			if ($dom->loadHTML($template2)) {
				$iframes = $dom->getElementsByTagName('iframe');
				foreach ($iframes as $iframe) {
					$iframe->parentNode->removeChild($iframe);
				}
				$embeds = $dom->getElementsByTagName('embed');
				foreach ($embeds as $embed) {
					$embed->parentNode->removeChild($embed);
				}
				$objects = $dom->getElementsByTagName('object');
				foreach ($objects as $object) {
					$object->parentNode->removeChild($object);
				}
				$applets = $dom->getElementsByTagName('applet');
				foreach ($applets as $applet) {
					$applet->parentNode->removeChild($applet);
				}
				$bases = $dom->getElementsByTagName('base');
				foreach ($bases as $base) {
					$base->parentNode->removeChild($base);
				}
				$meta = $dom->getElementsByTagName('meta');
				foreach ($bases as $base) {
					$meta->parentNode->removeChild($meta);
				}
				$template = $dom->saveHTML();
		
				$template = preg_replace('/.*<body>/s', '', $template);
				$template = preg_replace('/<\/body>.*/s', '', $template);
			}
		}
		
		return $template;
	}
	
	private static function smartTags($obj, $objn) {
		$template = $obj->listingDescription;
		
		$img = '';
		if (!empty($obj->objMainImage->fullUrl)) {
			$img = '<img src="'.esc_html($obj->objMainImage->fullUrl).'" alt="">';
		}
		
		$template = str_replace('[[TITLE]]', esc_html($obj->title), $template);
		$template = str_replace('[[PRICE]]', $obj->stylepriceIncludingTax, $template);
		$template = str_replace('[[DESC]]', apply_filters('the_content', $objn->post->post_content), $template);
		$template = str_replace('[[IMG]]', $img, $template);
		
		// We should remove unwanted HTML tags. The only real way to do this is using DOMDocument, but it's not always available.
		// Some people try to do it with regular expressions, but such implementations tend to be somewhat broken.
		if (class_exists('DOMDocument')) {
			libxml_clear_errors();
			
			$template = htmlspecialchars_decode(htmlspecialchars($template, ENT_IGNORE, 'UTF-8'));
			$template = preg_replace('/[^\PC\s]/u', '', $template);
			
			$template2 = '<!DOCTYPE html><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>'.$template.'</body></html>';
			$dom = new DOMDocument();
			if ($dom->loadHTML($template2)) {
				$iframes = $dom->getElementsByTagName('iframe');
				foreach ($iframes as $iframe) {
					$iframe->parentNode->removeChild($iframe);
				}
				$embeds = $dom->getElementsByTagName('embed');
				foreach ($embeds as $embed) {
					$embed->parentNode->removeChild($embed);
				}
				$objects = $dom->getElementsByTagName('object');
				foreach ($objects as $object) {
					$object->parentNode->removeChild($object);
				}
				$applets = $dom->getElementsByTagName('applet');
				foreach ($applets as $applet) {
					$applet->parentNode->removeChild($applet);
				}
				$bases = $dom->getElementsByTagName('base');
				foreach ($bases as $base) {
					$base->parentNode->removeChild($base);
				}
				$meta = $dom->getElementsByTagName('meta');
				foreach ($bases as $base) {
					$meta->parentNode->removeChild($meta);
				}
				$template = $dom->saveHTML();
				
				$template = preg_replace('/.*<body>/s', '', $template);
				$template = preg_replace('/<\/body>.*/s', '', $template);
			}
		}
		
		return $template;
	}
	
	private static function transformNativeProductIntoEcommerceProduct($objNativeProduct) {
		require_once(__DIR__ . "/AffinityEcommerceItemSpecific.php");
		require_once(__DIR__. '/../model/AffinityTitleRule.php');
		
		$objEcommerceProduct = new AffinityEcommerceProduct();
		$objEcommerceProduct->id = $objNativeProduct->post->ID;
		
		$condition = get_post_meta($objNativeProduct->post->ID, '_ebaycondition', true);
		if (empty($condition)) {
			$condition = 'NEW';
		}
		$objEcommerceProduct->condition = $condition;
		
		$title = AffinityTitleRule::generate($objNativeProduct);
		$objEcommerceProduct->title = $title[1];
		
		$objEcommerceProduct->shortDescription = $objNativeProduct->post->post_excerpt;
		
		$ebaydesc = get_post_meta($objNativeProduct->post->ID, '_ebaydesc', true);
		$useshort = get_option('ebayaffinity_useshort');
		if (empty($useshort)) {
			$useshort = get_post_meta($objNativeProduct->post->ID, '_ebayuseshort', true);
		}
		if (!empty($ebaydesc)) {
			$objEcommerceProduct->description = apply_filters('the_content', $ebaydesc);
		} else if (!empty($useshort)) {
			$objEcommerceProduct->description = apply_filters('the_content', $objNativeProduct->post->post_excerpt);
		} else {
			$objEcommerceProduct->description = apply_filters('the_content', $objNativeProduct->post->post_content);
		}
		
		$rrp_price = $objNativeProduct->get_price_including_tax(1, $objNativeProduct->get_regular_price());
		
		$price = $objNativeProduct->get_price_including_tax();
		$adjust = get_option('ebayaffinity_priceadjust');
		if (strpos($adjust, 'num') !== false) {
			$adjust = str_replace('num', '', $adjust);
			$adjust = floatval($adjust);
			$price += $adjust;
		} else {
			if (!empty($adjust)) {
				$price += $price * ($adjust / 100);
			}
		}
		
		$ebayprice = get_post_meta($objNativeProduct->post->ID, '_ebayprice', true);
		if (!empty($ebayprice)) {
			$price = $ebayprice;
		}
		
		$objEcommerceProduct->weight = $objNativeProduct->get_weight();
		$objEcommerceProduct->length = $objNativeProduct->get_length();
		$objEcommerceProduct->width = $objNativeProduct->get_width();
		$objEcommerceProduct->height = $objNativeProduct->get_height();
		
		if (empty($objEcommerceProduct->weight)) {
			$objEcommerceProduct->weight = 0;
		}
		
		if (empty($objEcommerceProduct->length)) {
			$objEcommerceProduct->length = 0;
		}
		
		if (empty($objEcommerceProduct->width)) {
			$objEcommerceProduct->width = 0;
		}
		
		if (empty($objEcommerceProduct->height)) {
			$objEcommerceProduct->height = 0;
		}
		
		$wc = wc_get_product($objNativeProduct->post->ID);
		
		if ($wc->is_type('variable')) {
			$variationloop = new WP_Query(array('post_type' => 'product_variation', 'post_parent' => $wc->post->ID));
			$price_arr = array();
		
			while ($variationloop->have_posts()) {
				$variationloop->the_post();
				$variation = new WC_Product(get_the_ID());
				$sprice = $variation->get_price_including_tax();
				
				$adjust = get_option('ebayaffinity_priceadjust');
				if (strpos($adjust, 'num') !== false) {
					$adjust = str_replace('num', '', $adjust);
					$adjust = floatval($adjust);
					$sprice += $adjust;
				} else {
					if (!empty($adjust)) {
						$sprice += $sprice * ($adjust / 100);
					}
				}
				
				$ebayprice = get_post_meta(get_the_ID(), '_ebayprice', true);
				if (!empty($ebayprice)) {
					$sprice = $ebayprice;
				}
				
				$price_arr[] = $sprice;
			}

			if (!empty($price_arr)) {
				$price_arr = array_unique($price_arr);
				if (count($price_arr) > 1) {
					sort($price_arr);
					$min = str_replace('.00', '', woocommerce_price($price_arr[0]));
					$max = str_replace('.00', '', woocommerce_price($price_arr[count($price_arr) - 1]));
					$stylePrice = $min . ' to ' . $max;
				} else {
					$stylePrice = woocommerce_price($price_arr[0]);
				}
			}
		}
		
		$objEcommerceProduct->retailPriceIncludingTax = number_format($rrp_price, 2, '.', '');
		$objEcommerceProduct->priceIncludingTax = number_format($price, 2, '.', '');
		
		if (empty($stylePrice)) {
			$objEcommerceProduct->stylepriceIncludingTax = woocommerce_price($price);
		} else {
			$objEcommerceProduct->stylepriceIncludingTax = $stylePrice;
		}
		
		$objEcommerceProduct->isStockBeingManaged = $objNativeProduct->managing_stock();
		$objEcommerceProduct->isInStock = $objNativeProduct->is_in_stock();
		$objEcommerceProduct->qtyAvailable = $objNativeProduct->get_stock_quantity();
		$objEcommerceProduct->status = $objNativeProduct->post->post_status;
		
		$mainImageId = $objNativeProduct->get_image_id();
		if($mainImageId) {
			$arrImageInfo = wp_get_attachment_image_src($mainImageId, 'full');
			
			if(!empty($arrImageInfo[0]) && !empty($arrImageInfo[1]) && !empty($arrImageInfo[2])) {
				require_once(__DIR__. '/AffinityEcommerceImage.php');
				$objMainImage = new AffinityEcommerceImage();
				$objMainImage->imageId = $mainImageId;
				$objMainImage->fullUrl = $arrImageInfo[0];
				$objMainImage->width = $arrImageInfo[1];
				$objMainImage->height = $arrImageInfo[2];
				$objMainImage->imageSize = filesize( get_attached_file( $mainImageId ) );
				$objEcommerceProduct->objMainImage = $objMainImage;
			}
		}
		
		$attachmentIds = $objNativeProduct->get_gallery_attachment_ids();
		$objEcommerceProduct->arrObjAdditionalImages = array();
		foreach($attachmentIds as $attachmentId) {
			$arrImageInfo = wp_get_attachment_image_src($attachmentId, 'full');
			
			if(!empty($arrImageInfo[0]) && !empty($arrImageInfo[1]) && !empty($arrImageInfo[2])) {
				require_once(__DIR__. '/AffinityEcommerceImage.php');
				$objImage = new AffinityEcommerceImage();
				$objImage->imageId = $mainImageId;
				$objImage->fullUrl = $arrImageInfo[0];
				$objImage->width = $arrImageInfo[1];
				$objImage->height = $arrImageInfo[2];
				$objImage->imageSize = filesize( get_attached_file( $attachmentId ) );
				$objEcommerceProduct->arrObjAdditionalImages[] = $objImage;
			}
		}
		
		$objEcommerceProduct->arrEcommerceItemSpecifics = self::getItemSpecifics($objNativeProduct);
		$objEcommerceProduct->arrEcommerceCategories = self::getEcommerceCategories($objNativeProduct);
		$objEcommerceProduct->arrEcommerceShipping = self::getEcommerceShipping($objNativeProduct);
		
		if ($wc->is_type('variable')) {
			$objEcommerceProduct->arrEcommerceProductVariations = $objEcommerceProduct->getVariations();
		} else {
			$objEcommerceProduct->arrEcommerceProductVariations = array();
		}
		
		$ebaytemplate = get_post_meta($objNativeProduct->post->ID, '_ebaytemplate', true);
		
		if (strlen($ebaytemplate) == 0 || $ebaytemplate == '1') {
			$objEcommerceProduct->listingDescription = self::transformTemplate($objEcommerceProduct);
		} else {
			$objEcommerceProduct->listingDescription = $objEcommerceProduct->description;
		}
		
		$objEcommerceProduct->listingDescription = self::smartTags($objEcommerceProduct, $objNativeProduct);
		
		return $objEcommerceProduct;
	}
	
	public static function productHasChanged($objWpPost, $toebay=false) {
		require_once(__DIR__ . "/AffinityEcommerceUtils.php");
		require_once(__DIR__. '/../model/AffinityGlobalOptions.php');
		require_once(__DIR__. '/../model/AffinityProduct.php');
		
		$objEcommerceProduct = self::get($objWpPost->ID);
		$affinityProduct = AffinityProduct::transformFromEcommerceProduct($objEcommerceProduct);
		
		if($objWpPost->post_status === "publish" && (!$affinityProduct->shouldNotBeSentToEbay)) {
			AffinityProduct::productWasPublished($objEcommerceProduct, $toebay);
		}
		else {
			AffinityProduct::productWasUnpublished($objEcommerceProduct, $toebay);
		}
	}
	
	
}
