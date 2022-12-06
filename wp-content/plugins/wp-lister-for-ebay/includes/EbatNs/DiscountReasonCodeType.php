<?php
/* Generated on 14.02.18 14:28 by globalsync
 * $Id: $
 * $Log: $
 */

require_once 'EbatNs_FacetType.php';

class DiscountReasonCodeType extends EbatNs_FacetType
{
	const CodeType_SpecialOffer = 'SpecialOffer';
	const CodeType_Promotion = 'Promotion';
	const CodeType_CustomCode = 'CustomCode';

	/**
	 * @return 
	 **/
	function __construct()
	{
		parent::__construct('DiscountReasonCodeType', 'urn:ebay:apis:eBLBaseComponents');
	}
}
$Facet_DiscountReasonCodeType = new DiscountReasonCodeType();
?>