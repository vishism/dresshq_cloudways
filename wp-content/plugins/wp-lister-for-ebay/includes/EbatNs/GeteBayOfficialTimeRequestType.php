<?php
/* Generated on 14.02.18 14:28 by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'AbstractRequestType.php';

/**
  * Gets the official eBay system time in GMT.
  * 
 **/

class GeteBayOfficialTimeRequestType extends AbstractRequestType
{

	/**
	 * Class Constructor 
	 **/
	function __construct()
	{
		parent::__construct('GeteBayOfficialTimeRequestType', 'urn:ebay:apis:eBLBaseComponents');
		if (!isset(self::$_elements[__CLASS__]))
		{
			self::$_elements[__CLASS__] = array_merge(self::$_elements[get_parent_class()],
			array(
));
		}
		$this->_attributes = array_merge($this->_attributes,
		array(
));
	}

}
?>
