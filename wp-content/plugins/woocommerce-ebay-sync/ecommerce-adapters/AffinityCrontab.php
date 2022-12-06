<?php

class AffinityCrontab {
	public function createCrontabHooks() {
		add_action('wp_affinity_cron', array($this, 'cron'));
		add_action('wp_affinity_cron_inv', array($this, 'cron_inv'));
		add_action('wp_affinity_cron_sync_all', array($this, 'cron_sync_all'));
		add_action('wp_affinity_cron_orders', array($this, 'cron_orders'));
		add_action('wp_affinity_cron_clearlog', array($this, 'cron_clearlog'));
	}
	
	public static function createInitialCrontab() {
		wp_schedule_event(time() + 600, get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron');
		wp_schedule_event(time() + 1200, get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron_inv');
		wp_schedule_event(time() + 600, 'hourly', 'wp_affinity_cron_orders');
		wp_schedule_event(time() + 600 + 900, 'hourly', 'wp_affinity_cron_orders');
		wp_schedule_event(time() + 600 + 1800, 'hourly', 'wp_affinity_cron_orders');
		wp_schedule_event(time() + 600 + 2700, 'hourly', 'wp_affinity_cron_orders');
		
		$logtime = get_option('ebayaffinity_clearlogtime');
		if (!empty($logtime)) {
			wp_schedule_event(time() + 900, $logtime, 'wp_affinity_cron_clearlog');
		}
	}
	
	public static function clearCrontab() {
		wp_clear_scheduled_hook('wp_affinity_cron');
		wp_clear_scheduled_hook('wp_affinity_cron_inv');
		wp_clear_scheduled_hook('wp_affinity_cron_sync_all');
		wp_clear_scheduled_hook('wp_affinity_cron_orders');
		wp_clear_scheduled_hook('wp_affinity_cron_clearlog');
	}
	
	public static function cron_clearlog() {
		global $wpdb;
		$wpdb->query("DELETE FROM `".$wpdb->prefix."ebayaffinity_log`");
		update_option('ebayaffinity_logran', time());
	}
	
	public static function cron_orders() {
		$setup1 = get_option('ebayaffinity_setup1');
		$setup2 = get_option('ebayaffinity_setup2');
		$setup3 = get_option('ebayaffinity_setup3');
		$setup4 = get_option('ebayaffinity_setup4');
		if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4))) {
			$orders_expire = get_option('affinity_orders_expire');
			if (empty($orders_expire) || $orders_expire < time()) {
				update_option('affinity_orders_expire', time() + 600);
				require_once(__DIR__.'/../service/AffinityLocalUpdateService.php');
				$arrResult = AffinityLocalUpdateService::orderNotificationReceived();
			}
		}
	}
	
	public static function cron_suggestions() {
		global $wpdb;
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		require_once(__DIR__ ."/../service/AffinityBackendService.php");
		require_once(__DIR__ ."/../service/AffinityEnc.php");
		$prods = AffinityEbayInventory::getBySearchCategory('', 0, 1, '', '', 'prodtwosets', 3, 100, false);
		$backend = get_option('ebayaffinity_backend');
		$token = AffinityEnc::getToken();

		if ((!empty($backend)) && (!empty($token))) {
			foreach ($prods[0] as $prod) {
				$product = new WC_Product($prod['id']);
				$attrs = $product->get_attributes();
				update_post_meta($prod['id'], '_affinity_suggestedCatId', '0');
				$json = AffinityBackendService::getSuggestionForTitle($prod['title']);
				
				$a = array();
				
				if (!empty($json['data'])) {
					$c = array();
					foreach ($json['data'] as $b) {
						$c[] = intval($b);
					}
					
					$categoryIds = $wpdb->get_results("SELECT categoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId IN (" . implode(',', $c) . ") ");
					
					if (!empty($categoryIds)) {
						foreach ($categoryIds as $b) {
							$a[] = $b->categoryId;
						}
					}
					
					if (!empty($a)) {
						update_post_meta($prod['id'], '_affinity_suggestedCatId', implode(',', $a));
					}
				}
			}
		}
	}

	public static function cron_cat_suggestions() {
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		require_once(__DIR__.'/../model/AffinityEbayCategory.php');
		$cats = AffinityEbayInventory::categoryset(true, true);
		$ecats = AffinityEbayCategory::getAlleBay();
		foreach ($cats as $k=>$cat) {
			if (empty($cat[1]) && empty($cat[2])) {
				$sname = AffinityEbayCategory::showWooCatName($cats, $k);
				
				$sname = preg_replace('/[^A-Za-z0-9>]/',  ' ', $sname);
				$sname = str_replace('  ', ' ', $sname);
				$sname = str_replace('  ', ' ', $sname);
				$sname = trim($sname);
				
				$asname = explode(' > ', $sname);
				
				$suggecatid = array();
				
				foreach ($ecats as $ecat) {
					$ecat->catname = ' '.str_replace("'", '', $ecat->catname).' ';
					$ecat->catname = preg_replace('/[^A-Za-z0-9>]/',  ' ', $ecat->catname);
					$ecat->catname = str_replace('  ', ' ', $ecat->catname);
					$ecat->catname = str_replace('  ', ' ', $ecat->catname);
					
					$match = true;
					foreach ($asname as $asnam) {
						$asnam = ' '.str_replace("'", '', $asnam).' ';
						if (stripos($ecat->catname, $asnam) === false) {
							$match = false;
						}
					}
					if ($match) {
						$suggecatid[] = $ecat->categoryId; 
						if (count($suggecatid) >= 5) {
							break;
						}
					}
				}
				
				if (!empty($suggecatid)) {
					update_term_meta($k, '_affinity_suggestedCatId', implode(',', $suggecatid));
				}
			}
		}
	}

	public static function cron() {
		$setup_ebayuserid = get_option('ebayaffinity_ebayuserid');
		$setup_token = get_option('affinityPushAccessToken');
		
		self::cron_cat_suggestions();
		
		$setup0 = (!empty($setup_ebayuserid)) && (!empty($setup_token));
		if (!empty($setup0)) {
			self::cron_suggestions();
		}
	}

	public static function cron_inv() {
		$setup1 = get_option('ebayaffinity_setup1');
		$setup2 = get_option('ebayaffinity_setup2');
		$setup3 = get_option('ebayaffinity_setup3');
		$setup4 = get_option('ebayaffinity_setup4');
		$setup5 = get_option('ebayaffinity_setup5');
		if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4)) && (!empty($setup5))) {
			require_once(__DIR__ . "/AffinityEcommerceProduct.php");
			require_once(__DIR__.'/../model/AffinityEbayInventory.php');

			AffinityEbayInventory::publishUpdates();
		}
	}

	public static function cron_sched_sync_all() {
		$t = wp_next_scheduled('wp_affinity_cron_sync_all');
		if ($t === false || ($t - time() > 300) || $t - time() < 0) {
			wp_clear_scheduled_hook('wp_affinity_cron_sync_all');
			wp_schedule_single_event(time() + 300, 'wp_affinity_cron_sync_all');
		}
	}

	public static function cron_sync_all() {
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		AffinityEbayInventory::syncAll();
	}
}
