<?php
/**
 * EbayCategoriesModel class
 *
 * responsible for managing ebay categories and store categories and talking to ebay
 * 
 */

// list of used EbatNs classes:

// require_once 'EbatNs_ServiceProxy.php';
// require_once 'GetCategoriesRequestType.php';
// require_once 'GetStoreRequestType.php';
// require_once 'CategoryType.php';	
// require_once 'EbatNs_Logger.php';
// require_once 'GetCategoryFeaturesRequestType.php';

class EbayCategoriesModel extends WPL_Model {
	const table = 'ebay_categories';

	var $_session;
	var $_cs;
	var $_categoryVersion;
	var $_siteid;

	public function __construct() {
		parent::__construct();
		
		global $wpdb;
		$this->tablename = $wpdb->prefix . self::table;
	}
	
	function initCategoriesUpdate( $session, $site_id )
	{
		$this->initServiceProxy($session);
		WPLE()->logger->info("initCategoriesUpdate( $site_id )");

		// set handler to receive CategoryType items from result
		$this->_cs->setHandler('CategoryType', array(& $this, 'storeCategory'));	
		
		// we will not know the version till the first call went through !
		$this->_categoryVersion = -1;
		$this->_siteid = $site_id;
		
		// truncate the db
		global $wpdb;
		// $wpdb->query('truncate '.$this->tablename);
		$wpdb->query( $wpdb->prepare("DELETE FROM {$this->tablename} WHERE site_id = %s ", $site_id ) );
		
		// download the data of level 1 only !
		$req = new GetCategoriesRequestType();
		$req->CategorySiteID = $site_id;
		$req->LevelLimit = 1;
		$req->DetailLevel = 'ReturnAll';
		
		$res = $this->_cs->GetCategories($req);
		$this->_categoryVersion = $res->CategoryVersion;
		
		// let's update the version information on the top-level entries
		$data['version'] = $this->_categoryVersion;
		$data['site_id'] = $this->_siteid;
		$wpdb->update( $this->tablename, $data, array( 'parent_cat_id' => '0', 'site_id' => $site_id ) );
        echo $wpdb->last_error;

        // include the account ID in the tasks
        $account_id = $session->wple_account_id;

		// include other site specific update tasks
		$tasks = array();
		/*$tasks[] = array(
			'task'        => 'loadShippingServices', 
			'displayName' => 'update shipping services', 
			'params'      => array(),
			'site_id'     => $site_id,
		);*/

        $tasks[] = array(
            'task'        => 'getCountryDetails',
            'displayName' => 'Downloading country details',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getShippingLocations',
            'displayName' => 'Downloading shipping locations',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getShippingDetails',
            'displayName' => 'Downloading shipping details',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getDispatchTimes',
            'displayName' => 'Downloading dispatch times',
            'params'      => array(),
            'site_id'     => $site_id,
        );

        $tasks[] = array(
            'task'        => 'getShippingPackages',
            'displayName' => 'Downloading shipping packages',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getExcludeShippingLocations',
            'displayName' => 'Downloading excluded shipping locations',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getDoesNotApplyText',
            'displayName' => 'Downloading "Does not apply" text',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

		/*$tasks[] = array(
			'task'        => 'loadPaymentOptions', 
			'displayName' => 'update payment options',
			'site_id'     => $site_id,
		);*/

        $tasks[] = array(
            'task'        => 'getPaymentDetails',
            'displayName' => 'Downloading payment details',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getMinimumStartPrices',
            'displayName' => 'Downloading minimum start prices',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );

        $tasks[] = array(
            'task'        => 'getReturnPolicyDetails',
            'displayName' => 'Downloading return policy details',
            'params'      => array(),
            'site_id'     => $site_id,
            'account_id'    => $account_id
        );


		// include eBay Motors for US site - automatically
		// if ( ( $site_id === 0 ) && ( get_option( 'wplister_enable_ebay_motors' ) == 1 ) ) {
		if ( $site_id === 0 || $site_id === '0' ) {

			// insert top level motors category manually
			$wpdb->query("DELETE FROM {$this->tablename} WHERE site_id = 100 ");
			$data['cat_id']        = 6000;
			$data['parent_cat_id'] = 0;
			$data['level']         = 1;
			$data['leaf']          = 0;
			$data['cat_name']      = 'eBay Motors';
			$data['site_id']       = 100;
			$wpdb->insert( $this->tablename, $data );

			$task = array( 
				'task'        => 'loadEbayCategoriesBranch', 
				'displayName' => 'eBay Motors', 
				'cat_id'      =>  6000,
				'site_id'     =>  100,
                'account_id'    => $account_id
			);
			$tasks[] = $task;

		}

		// fetch the data back from the db and add a task for each top-level id
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT cat_id, cat_name, site_id FROM {$this->tablename} WHERE parent_cat_id = 0 AND site_id = %s ", $site_id ), ARRAY_A );
        echo $wpdb->last_error;
		foreach ($rows as $row)
		{
			WPLE()->logger->info('adding task for category #'.$row['cat_id'] . ' - '.$row['cat_name']);

			$task = array( 
				'task'        => 'loadEbayCategoriesBranch', 
				'displayName' => $row['cat_name'], 
				'cat_id'      => $row['cat_id'],
				'site_id'     => $row['site_id'],
                'account_id'    => $account_id
			);
			$tasks[] = $task;
		}

		return $tasks;
	}
	
	function loadEbayCategoriesBranch( $cat_id, $session, $site_id )
	{
		$this->initServiceProxy($session);
		WPLE()->logger->info("loadEbayCategoriesBranch() - cat_id: $cat_id, site_id: $site_id" );

		// handle eBay Motors category (US only)
		if ( $cat_id == 6000 && $site_id == 0 ) $site_id = 100;

		// set handler to receive CategoryType items from result
		$this->_cs->setHandler('CategoryType', array(& $this, 'storeCategory'));	
		$this->_siteid = $site_id;

		// call GetCategories()
		$req = new GetCategoriesRequestType();
		$req->CategorySiteID = $site_id;
		$req->LevelLimit = 255;
		$req->DetailLevel = 'ReturnAll';
		$req->ViewAllNodes = true;
		$req->CategoryParent = $cat_id;
		$this->_cs->GetCategories($req);

	}	
	
	function storeCategory( $type, $Category )
	{
		global $wpdb;
		
		//#type $Category CategoryType
		$data['cat_id'] = $Category->CategoryID;
		if ( $Category->CategoryParentID[0] == $Category->CategoryID ) {

			// avoid duplicate main categories due to the structure of the response
			if ( $this->getItem( $Category->CategoryID, $this->_siteid ) ) return true;

			$data['parent_cat_id'] = '0';

		} else {
			$data['parent_cat_id'] = $Category->CategoryParentID[0];			
		}
		$data['cat_name'] = $Category->CategoryName;
		$data['level']    = $Category->CategoryLevel;
		$data['leaf']     = $Category->LeafCategory ? $Category->LeafCategory : 0;
		$data['version']  = $this->_categoryVersion ? $this->_categoryVersion : 0;
		$data['site_id']  = $this->_siteid;
		
		// remove unrecognizable chars from category name
		// $data['cat_name'] = trim(str_replace('?','', $data['cat_name'] ));

		$wpdb->insert( $this->tablename, $data );
		if ( $wpdb->last_error ) {
			WPLE()->logger->error('failed to insert category '.$data['cat_id'] . ' - ' . $data['cat_name'] );
			WPLE()->logger->error('mysql said: '.$wpdb->last_error );
			WPLE()->logger->error('data: '. print_r( $data, 1 ) );
		} else {
			WPLE()->logger->info('category inserted() '.$data['cat_id'] . ' - ' . $data['cat_name'] );
		}
					
		return true;
	}
	
	

	function downloadStoreCategories( $session, $account_id )
	{
		global $wpdb;
		$this->initServiceProxy($session);
		WPLE()->logger->info('downloadStoreCategories()');
		$this->account_id = $account_id;
		
		// download store categories
		$req = new GetStoreRequestType();
		$req->CategoryStructureOnly = true;
		
		$res = $this->_cs->GetStore($req);
		
		// empty table
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}ebay_store_categories WHERE account_id = %s ", $account_id ) );
		
		// insert each category
		foreach( $res->Store->CustomCategories as $Category ) {
		
			$this->handleStoreCategory( $Category, 1, 0 );

		}
	}
		
	
	function handleStoreCategory( $Category, $level, $parent_cat_id )
	{
		global $wpdb;
		if ( $level > 5 ) return false;		

		$data = array();
		$data['cat_id'] 		= $Category->CategoryID;
		$data['cat_name'] 		= $Category->Name;
		$data['order'] 			= $Category->Order;
		$data['leaf'] 			= is_array( $Category->ChildCategory ) ? '0' : '1';
		$data['level'] 			= $level;
		$data['parent_cat_id'] 	= $parent_cat_id;
		$data['account_id']     = $this->account_id;
		$data['site_id']        = WPLE()->accounts[ $this->account_id ]->site_id;

		// move "Other" category to the end of the list
        // Fix: Use cat_id==1 instead of order==0 to look for the "Other" category
		//if ( $data['order'] == 0 ) $data['order'] = 999;
		if ( $data['cat_id'] == 1 ) $data['order'] = 999;

		// insert row - and manually set field type to string. 
		// without parameter '%s' $wpdb would convert cat_id to int instead of bigint - on some servers!
		$wpdb->insert( $wpdb->prefix.'ebay_store_categories', $data, '%s' );

		// handle children recursively
		if ( is_array( $Category->ChildCategory ) ) {
			foreach ( $Category->ChildCategory as $ChildCategory ) {
				$this->handleStoreCategory( $ChildCategory, $level + 1, $Category->CategoryID );
			}
		}

	}
	

	
	function fetchCategoryConditions( $session, $category_id, $site_id )
	{

		// adjust Site if required - eBay Motors (beta)
		$test_site_id = $site_id == 0 ? 100 : $site_id;
		$primary_category = $this->getItem( $category_id, $test_site_id );
		WPLE()->logger->info("fetchCategoryConditions( $category_id, $test_site_id ) primary_category: ".print_r($primary_category,1));
		if ( $primary_category && $primary_category['site_id'] == 100 ) {
			$session->setSiteId( 100 );
			$site_id = 100;
		}

		$this->initServiceProxy($session);
		
		// download store categories
		$req = new GetCategoryFeaturesRequestType();
		$req->setCategoryID( $category_id );
		$req->setDetailLevel( 'ReturnAll' );
		
		$res = $this->_cs->GetCategoryFeatures($req);
		WPLE()->logger->info('fetchCategoryConditions() for category ID '.$category_id);
		// WPLE()->logger->info('fetchCategoryConditions: '.print_r($res,1));

		// build $conditions array
		$conditions = array();
		if ( @$res->Category[0]->ConditionValues && $res->Category[0]->ConditionValues->Condition && count($res->Category[0]->ConditionValues->Condition) > 0 )
		foreach ($res->Category[0]->ConditionValues->Condition as $Condition) {
			$conditions[$Condition->ID] = $Condition->DisplayName;
		}
		//WPLE()->logger->info('fetchCategoryConditions: '.print_r($conditions,1));

        ###
        # This does not work due to the version of the Ebat_NS library that WPLE is using - it does not recognize the SpecialFeatures element in the response #51239
        ###
		// Include SpecialFeatures in the conditions array
        if ( isset( $res->Category[0] ) && $res->Category[0]->SpecialFeatures && is_object( $res->Category[0]->SpecialFeatures) && $res->Category[0]->SpecialFeatures->Condition && count( $res->Category[0]->SpecialFeatures->Condition ) > 0 ) {
            foreach ($res->Category[0]->SpecialFeatures->Condition as $Condition) {
                $conditions[$Condition->ID] = $Condition->DisplayName;
            }
            WPLE()->logger->info('fetchCategoryConditions: '.print_r($conditions,1));
        }
		
		if (!is_array($conditions)) $conditions = 'none';

		// build features object
		$features = new stdClass();
		$features->conditions = $conditions;
		$features->ConditionEnabled          = !is_array($res->Category) ? null : $res->Category[0]->getConditionEnabled();
		$features->UPCEnabled                = !is_array($res->Category) ? null : $res->Category[0]->getUPCEnabled();
		$features->EANEnabled                = !is_array($res->Category) ? null : $res->Category[0]->getEANEnabled();
		$features->ISBNEnabled               = !is_array($res->Category) ? null : $res->Category[0]->getISBNEnabled();
		$features->BrandMPNIdentifierEnabled = !is_array($res->Category) ? null : $res->Category[0]->getBrandMPNIdentifierEnabled();
		$features->ItemCompatibilityEnabled  = !is_array($res->Category) ? null : $res->Category[0]->getItemCompatibilityEnabled();
		$features->VariationsEnabled         = !is_array($res->Category) ? null : $res->Category[0]->getVariationsEnabled();

		// store result in ebay_categories table
		global $wpdb;
		$data = array();
		$data['features']     = serialize( $features );
		// $data['last_updated'] = date('Y-m-d H:i:s'); // will be updated when storing item specifics
		$wpdb->update( $wpdb->prefix . self::table, $data, array( 'cat_id' => $category_id, 'site_id' => $session->getSiteId() ) );
		WPLE()->logger->info('category features / conditions were stored...'.$wpdb->last_error);
		
		// legacy return format
		return array( $category_id => $conditions );

	} // fetchCategoryConditions()

    /**
     * @deprecated Use EbayTaxonomyModel::getItemAspectsForCategory() instead
     * @param $session
     * @param $category_id
     * @param bool $site_id
     * @return array[]|string[]
     */
	function fetchCategorySpecifics( $session, $category_id, $site_id = false )
	{
	    global $wpdb;
	    //_deprecated_function( 'EbayCategoriesModel::fetchCategorySpecifics', '3.2', 'EbayTaxonomyModel::getItemAspectsForCategory' );

		// adjust Site if required - eBay Motors (beta)
		$test_site_id = $site_id == 0 ? 100 : $site_id;
		$primary_category = $this->getItem( $category_id, $test_site_id );
		WPLE()->logger->info("fetchCategorySpecifics( $category_id, $test_site_id ) primary_category: ".print_r($primary_category,1));
		if ( $primary_category && $primary_category['site_id'] == 100 ) {
			$session->setSiteId( 100 );
			$site_id = 100;
		}

		$account = WPLE()->accounts[ $session->wple_account_id ];

		if ( $account && $account->oauth_token ) {
            // make sure we have a valid oauth token
            WPLE_eBayAccount::maybeMintToken( $session->wple_account_id );

            $taxonomy_mdl = new EbayTaxonomyModel( $session->wple_account_id );

            $wpl_site = WPLE_eBaySite::getSite( $site_id );
            $category_tree_id = $wpl_site->default_category_tree_id;
            $aspects = $taxonomy_mdl->getItemAspectsForCategory( $category_id, $category_tree_id );

            $specifics = [];

            if ( $aspects ) foreach ( $aspects as $aspect ) {
                $name = $aspect->getLocalizedAspectName();

                $min_value = '';
                if ( $aspect->getAspectConstraint()->getAspectRequired() ) {
                    $min_value = 1;
                }

                $new_specs = new stdClass();
                $new_specs->Name          = $name;
                $new_specs->ValueType     = $aspect->getAspectConstraint()->getAspectDataType();
                $new_specs->MinValues     = $min_value;
                $new_specs->MaxValues     = $aspect->getAspectConstraint()->getItemToAspectCardinality() == 'SINGLE' ? 1 : '';
                $new_specs->SelectionMode = $aspect->getAspectConstraint()->getAspectMode();
                // store the Usage to be able to display the recommended Item Specifics #54107
                $new_specs->Usage         = $aspect->getAspectConstraint()->getAspectUsage();
                $new_specs->recommendedValues = [];

                $aspect_values = $aspect->getAspectValues();
                if ( $aspect_values ) {
                    foreach ( $aspect_values as $value ) {
                        // WPLE()->logger->info('*** '.$Recommendation->Name.' recommendedValue: '.$recommendedValue->Value);
                        $value = $value->getLocalizedValue();

                        if ( strpos( $value, chr(239) ) ) continue; // skip values with 0xEF / BOM (these are broken on eBay and cause problems on some servers)
                        if ( strpos( $value, chr(226) ) ) continue; // skip values with 0xE2
                        if ( strpos( $value, chr(128) ) ) continue; // skip values with 0x80
                        if ( strpos( $value, chr(139) ) ) continue; // skip values with 0x8B
                        $value = preg_replace('/[[:cntrl:]]/i', '', $value); // remove control characters (not encountered yet)
                        $new_specs->recommendedValues[] = $value;
                    }
                }


                $specifics[] = $new_specs;
            }
            WPLE()->logger->debug( 'Got specifics: '. print_r( $specifics, 1 ) );

            // store result in ebay_categories table
            $data = array();
            $data['specifics']    = serialize( $specifics );
            $data['last_updated'] = date('Y-m-d H:i:s');
            $wpdb->update( $wpdb->prefix . self::table, $data, array( 'cat_id' => $category_id, 'site_id' => $site_id ) );
            WPLE()->logger->info('category specifics were stored...'.$wpdb->last_error);

            return $specifics;
        }

		$this->initServiceProxy($session);
		
		// download store categories
		$req = new GetCategorySpecificsRequestType();
		$req->setCategoryID( $category_id );
		$req->setDetailLevel( 'ReturnAll' );
		$req->setMaxNames( apply_filters( 'wple_category_specifics_max_names', get_option( 'wplister_item_specifics_limit', 100 ) ) ); 			// eBay default is 30
		$req->setMaxValuesPerName( apply_filters( 'wple_category_specifics_max_name_value', 1000 ) ); 	// eBay default is 25 - no maximum
		
		$res = $this->_cs->GetCategorySpecifics($req);
		WPLE()->logger->info('fetchCategorySpecifics() for category ID '.$category_id);

		// build $specifics array
		$specifics = array();
		if ( @$res->Recommendations[0]->NameRecommendation && count($res->Recommendations[0]->NameRecommendation) > 0 ) {
			foreach ($res->Recommendations[0]->NameRecommendation as $Recommendation) {

				// ignore invalid data - Name is required
				// if ( empty( $Recommendation->getName() ) ) continue; // does not work in PHP 5.4 and before (Fatal Error: Can't use method return value in write context)
                if ( ! $Recommendation->getName() ) continue;			// works in all PHP versions

				$new_specs                = new stdClass();
				$new_specs->Name          = $Recommendation->Name;
				$new_specs->ValueType     = $Recommendation->ValidationRules->ValueType;
				$new_specs->MinValues     = $Recommendation->ValidationRules->MinValues;
				$new_specs->MaxValues     = $Recommendation->ValidationRules->MaxValues;
				$new_specs->SelectionMode = $Recommendation->ValidationRules->SelectionMode;

				if ( is_array( $Recommendation->ValueRecommendation ) ) {
					foreach ($Recommendation->ValueRecommendation as $recommendedValue) {
						// WPLE()->logger->info('*** '.$Recommendation->Name.' recommendedValue: '.$recommendedValue->Value);
						if ( strpos( $recommendedValue->Value, chr(239) ) ) continue; // skip values with 0xEF / BOM (these are broken on eBay and cause problems on some servers)
						if ( strpos( $recommendedValue->Value, chr(226) ) ) continue; // skip values with 0xE2
						if ( strpos( $recommendedValue->Value, chr(128) ) ) continue; // skip values with 0x80
						if ( strpos( $recommendedValue->Value, chr(139) ) ) continue; // skip values with 0x8B
        				$value = preg_replace('/[[:cntrl:]]/i', '', $recommendedValue->Value); // remove control characters (not encountered yet)
						$new_specs->recommendedValues[] = $value;
					}
				}

				$specifics[] = $new_specs;
			}		
		}
		// WPLE()->logger->info('fetchCategorySpecifics: '.print_r($specifics,1));
		if (!is_array($specifics)) $specifics = 'none';

		// store result in ebay_categories table
		global $wpdb;
		$data = array();
		$data['specifics']    = serialize( $specifics );
		$data['last_updated'] = date('Y-m-d H:i:s');
		$wpdb->update( $wpdb->prefix . self::table, $data, array( 'cat_id' => $category_id, 'site_id' => $site_id ) );
		WPLE()->logger->info('category specifics were stored...'.$wpdb->last_error);
		
		// legacy return format
		return array( $category_id => $specifics );

	} // fetchCategorySpecifics()
		
	
	static function getItemSpecificsForCategory( $category_id, $site_id = false, $account_id = false ) {
		// if site_id is empty, get it from account_id or default account
		if ( ! $site_id && ! $account_id ) {
		    if ( empty( WPLE()->accounts ) ) {
		        // no accounts exist, return an empty array
                return array();
            }

			$account = WPLE()->accounts[ get_option('wplister_default_account_id') ];
			$site_id = $account->site_id;
		}
		if ( ! $site_id && $account_id ) {
			$account = WPLE()->accounts[ $account_id ];
			$site_id = $account->site_id;
		}

		// get category from db
		$category = self::getItem( $category_id, $site_id );
		if ( ! $category_id ) return array();
		if ( ! $category    ) return array();

		// if timestamp is recent, return item specifics
        $item_specifics = maybe_unserialize( $category['specifics'] );
        $cache_lifetime = apply_filters( 'wple_item_specifics_cache_lifetime', '1 month' );
		if ( !empty( $item_specifics ) && get_option( 'wplister_disable_item_specifics_cache', 0 ) == 0 && strtotime( $category['last_updated']  ) > strtotime("-{$cache_lifetime}") ) {
			// WPLE()->logger->info('found recent item specifics from '.$category['last_updated'] );
			return maybe_unserialize( $category['specifics'] );
		}
		WPLE()->logger->info('updating outdated item specifics - last update: '.$category['last_updated'] );

		// fetch info from eBay
		WPLE()->initEC( $account_id );
		$result = WPLE()->EC->getCategorySpecifics( $category_id );
		WPLE()->EC->closeEbay();

		// always return an array
		return $result;
		//return is_array($result) ? reset($result) : array();

	} // getItemSpecificsForCategory()

	
	static function getConditionsForCategory( $category_id, $site_id = false, $account_id = false ) {

		// if site_id is empty, get it from account_id or default account
		if ( ! $site_id && ! $account_id ) {
            if ( empty( WPLE()->accounts ) ) {
                // no accounts exist, return an empty array
                return apply_filters( 'wple_get_conditions_for_category', array(), $category_id, $site_id, $account_id );
            }

			$account = WPLE()->accounts[ get_option('wplister_default_account_id') ];
			$site_id = $account->site_id;
		}
		if ( ! $site_id && $account_id ) {
			$account = WPLE()->accounts[ $account_id ];
			$site_id =  ($account) ? $account->site_id : $site_id;
		}

		// get category from db
		$category = self::getItem( $category_id, $site_id );
        if ( ! $category_id ) return apply_filters( 'wple_get_conditions_for_category', array(), $category_id, $site_id, $account_id );
        if ( ! $category    ) return apply_filters( 'wple_get_conditions_for_category', array(), $category_id, $site_id, $account_id );

        // if timestamp is recent, return category conditions
		if ( strtotime( $category['last_updated']  ) > strtotime('-1 month') && get_option( 'wplister_log_level', 0 ) < 7 ) {
			// WPLE()->logger->info('found recent category conditions from '.$category['last_updated'] );
			$features = maybe_unserialize( $category['features'] );
			if ( is_object($features) )
				//return $features->conditions;
                return apply_filters( 'wple_get_conditions_for_category', $features->conditions, $category_id, $site_id, $account_id );
        }
		WPLE()->logger->info('updating outdated category conditions - last update: '.$category['last_updated'] );


        // fetch info from eBay
        WPLE()->initEC( $account_id );
        $result = WPLE()->EC->getCategoryConditions( $category_id );
        WPLE()->EC->closeEbay();

		// always return an array
		//return is_array($result) ? reset($result) : array();
        $conditions = is_array($result) ? reset($result) : array();
        return apply_filters( 'wple_get_conditions_for_category', $conditions, $category_id, $site_id, $account_id );

    } // getConditionsForCategory()

	
	static function getUPCEnabledForCategory( $category_id, $site_id = false, $account_id = false ) {

		// if site_id is empty, get it from account_id or default account
		if ( ! $site_id && ! $account_id ) {
			$account = WPLE()->accounts[ get_option('wplister_default_account_id') ];
			$site_id = $account->site_id;
		}
		if ( ! $site_id && $account_id ) {
			$account = WPLE()->accounts[ $account_id ];
			$site_id = $account->site_id;
		}

		// get category from db
		$category = self::getItem( $category_id, $site_id );
		if ( ! $category_id ) return array();
		if ( ! $category    ) return false;

		// if timestamp is recent, return category features
		if ( strtotime( $category['last_updated']  ) > strtotime('-1 month') ) {
			// WPLE()->logger->info('found recent category features from '.$category['last_updated'] );
			$features = maybe_unserialize( $category['features'] );
			if ( is_object($features) )
				return isset( $features->UPCEnabled ) ? $features->UPCEnabled : null;
		}
		WPLE()->logger->info('updating outdated category features (UPCEnabled) - last update: '.$category['last_updated'] );

		// fetch info from eBay
		WPLE()->initEC( $account_id );
		$result = WPLE()->EC->getCategoryConditions( $category_id );
		WPLE()->EC->closeEbay();

		// fetch updated category details from DB
		$category = self::getItem( $category_id, $site_id );
		$features = maybe_unserialize( $category['features'] );
		if ( is_object($features) ) {
			return isset( $features->UPCEnabled ) ? $features->UPCEnabled : null;
		}

		// nothing found
		return false;
	} // getUPCEnabledForCategory()

	static function getEANEnabledForCategory( $category_id, $site_id = false, $account_id = false ) {

		// if site_id is empty, get it from account_id or default account
		if ( ! $site_id && ! $account_id ) {
			$account = WPLE()->accounts[ get_option('wplister_default_account_id') ];
			$site_id = $account->site_id;
		}
		if ( ! $site_id && $account_id ) {
			$account = WPLE()->accounts[ $account_id ];
			$site_id = $account->site_id;
		}

		// get category from db
		$category = self::getItem( $category_id, $site_id );
		if ( ! $category_id ) return array();
		if ( ! $category    ) return false;

		// if timestamp is recent, return category features
		if ( strtotime( $category['last_updated']  ) > strtotime('-1 month') ) {
			// WPLE()->logger->info('found recent category features from '.$category['last_updated'] );
			$features = maybe_unserialize( $category['features'] );
			if ( is_object($features) )
				return isset( $features->EANEnabled ) ? $features->EANEnabled : null;
		}
		WPLE()->logger->info('updating outdated category features (EANEnabled) - last update: '.$category['last_updated'] );

		// fetch info from eBay
		WPLE()->initEC( $account_id );
		$result = WPLE()->EC->getCategoryConditions( $category_id );
		WPLE()->EC->closeEbay();

		// fetch updated category details from DB
		$category = self::getItem( $category_id, $site_id );
		$features = maybe_unserialize( $category['features'] );
		if ( is_object($features) ) {
			return isset( $features->EANEnabled ) ? $features->EANEnabled : null;
		}

		// nothing found
		return false;
	} // getEANEnabledForCategory()

	

	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */
	
	static function getAll() {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;
		$profiles = $wpdb->get_results("
			SELECT * 
			FROM $table
			ORDER BY cat_name
		", ARRAY_A);		

		return $profiles;		
	}

	static function getItem( $id, $site_id = false ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;
		
		// when site is US (0), find eBay Motors categories (100) as well
		$where_site_sql = $site_id === false ? '' : "AND site_id ='".esc_sql($site_id)."'";
		if ( $site_id === 0 || $site_id === '0' ) $where_site_sql = "AND ( site_id = 0 OR site_id = 100 )";

        $item = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE cat_id = %s
			$where_site_sql
		", $id
		), ARRAY_A);

		return $item;		
	}

	static function getCategoryName( $id, $site_id = null ) {
		global $wpdb;

		$table = $wpdb->prefix . self::table;
		$args = array( $id );
		$site_id_sql = '';

		if ( !is_null($site_id) ) {
		    $site_id_sql = " AND site_id = %d";
		    $args[] = $site_id;
        }

		$value = $wpdb->get_var( $wpdb->prepare("
			SELECT cat_name 
			FROM $table
			WHERE cat_id = %s
			$site_id_sql
		", $args ) );

		return $value;		
	}

	static function getCategoryType( $id, $site_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;
		$ebay_motors_sql = $site_id == 0 ? 'OR site_id = 100' : '';
		$value = $wpdb->get_var( $wpdb->prepare("
			SELECT leaf 
			FROM $table
			WHERE cat_id    = %s
			  AND ( site_id = %s
			  $ebay_motors_sql )
		", $id, $site_id ) );		

		$value = apply_filters_deprecated('wplister_get_ebay_category_type', array($value, $id), '2.8.4', 'wple_get_ebay_category_type' );
		$value = apply_filters('wple_get_ebay_category_type', $value, $id );
		return $value ? 'leaf' : 'parent';
	}

	static function getChildrenOf( $id, $site_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;
		$ebay_motors_sql = $site_id == 0 ? 'OR site_id = 100' : '';
		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT DISTINCT * 
			FROM $table
			WHERE parent_cat_id = %s
			  AND ( site_id     = %s
			  $ebay_motors_sql )
		", $id, $site_id 
		), ARRAY_A);		

		return $items;		
	}

	static function getStoreCategoryName( $id ) {
		global $wpdb;	
		$table = $wpdb->prefix . 'ebay_store_categories';
		$value = $wpdb->get_var( $wpdb->prepare("
			SELECT cat_name 
			FROM $table
			WHERE cat_id = %s
		", $id ) );		

		return $value;		
	}
	static function getStoreCategoryType( $id, $account_id ) {
		global $wpdb;	
		// $this->tablename = $wpdb->prefix . self::table;
		$table = $wpdb->prefix . 'ebay_store_categories';
		$value = $wpdb->get_var( $wpdb->prepare("
			SELECT leaf 
			FROM $table
			WHERE cat_id     = %s
			  AND account_id = %s
		", $id, $account_id ) );		

		return $value ? 'leaf' : 'parent';		
	}
	static function getChildrenOfStoreCategory( $id, $account_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . 'ebay_store_categories';
		$sortby = ( get_option( 'wplister_store_categories_sorting', 'default' ) == 'default' ) ? 'order' : 'cat_name';
		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT DISTINCT * 
			FROM $table
			WHERE parent_cat_id = %s
			  AND account_id    = %s
			ORDER BY `$sortby` ASC
		", $id, $account_id 
		), ARRAY_A);		

		return $items;		
	}

	// recursive method to get entire store category tree
	static function getEntireStoreCategoryTree( $id, $account_id ) {		

		// get account
		$accounts = WPLE()->accounts;
		$account  = isset( $accounts[ $account_id ] ) ? $accounts[ $account_id ] : false;
		if ( ! $account ) die('Invalid account!');

		// get StoreURL for account
		$user_details = maybe_unserialize( $account->user_details );
		$StoreURL     = $user_details->StoreURL;

		$items = self::getChildrenOfStoreCategory( $id, $account_id );
		foreach ( $items as &$item ) {

			// add store url
			$item['url'] = $StoreURL . '/?_fsub=' . $item['cat_id'];

			// these should be left out when returning JSON
			unset( $item['parent_cat_id'] );
			unset( $item['wp_term_id'] );
			unset( $item['version'] );
			unset( $item['site_id'] );
			unset( $item['account_id'] );

			if ( $item['leaf']      ) continue;
			if ( $item['level'] > 5 ) continue;
			$item['children'] = self::getEntireStoreCategoryTree( $item['cat_id'], $account_id );
		}

		return $items;		
	}


		
	/* recursively get full ebay category name */	
	static function getFullEbayCategoryName( $cat_id, $site_id = false ) {
		global $wpdb;
		$table = $wpdb->prefix . self::table;

		if ( intval($cat_id) == 0 ) return null;
		if ( $site_id === false ) $site_id = get_option('wplister_ebay_site_id');
		$ebay_motors_sql = $site_id == 0 ? 'OR site_id = 100' : '';

		$result = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE cat_id    = %s
			  AND ( site_id = %s
			  $ebay_motors_sql )
		", $cat_id, $site_id ) );

		if ( $result ) { 
			if ( $result->parent_cat_id != 0 ) {
				$parentname = self::getFullEbayCategoryName( $result->parent_cat_id, $site_id ) . ' &raquo; ';
			} else {
				$parentname = '';
			}
			return $parentname . $result->cat_name;
		}

		// if there is a category ID, but no category found, return warning
        return '<span style="color:darkred;">' . __( 'Unknown category ID', 'wp-lister-for-ebay' ).': '.$cat_id . '</span>';
	}

	/* recursively get full store category name */	
	static function getFullStoreCategoryName( $cat_id, $account_id = false ) {
		global $wpdb;
		if ( intval($cat_id) == 0 ) return null;
		if ( ! $account_id ) $account_id = get_option('wplister_default_account_id');

		$result = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM {$wpdb->prefix}ebay_store_categories
			WHERE cat_id     = %s
			  AND account_id = %s
		", $cat_id, $account_id ) );
		// $result = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'ebay_store_categories WHERE cat_id = '.$cat_id.' AND account_id = '.$account_id );

		if ( $result ) { 
			if ( $result->parent_cat_id != 0 ) {
				$parentname = self::getFullStoreCategoryName( $result->parent_cat_id, $account_id ) . ' &raquo; ';
			} else {
				$parentname = '';
			}
			return $parentname . $result->cat_name;
		}

		// if there is a category ID, but no category found, return warning
        return '<span style="color:darkred;">' . __( 'Unknown category ID', 'wp-lister-for-ebay' ).': '.$cat_id . '</span>';
	}
	
	
} // class EbayCategoriesModel
