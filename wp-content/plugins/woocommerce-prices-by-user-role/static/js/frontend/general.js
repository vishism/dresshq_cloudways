jQuery(document).ready(function() 
{
	function hideVariableAddToCartButton()
	{
		if (!jQuery('#variableHideAddToCartButton').length > 0) {
			return false;
		}
		
		selector = jQuery('#variableHideAddToCartButton').parent();
	    selector = jQuery(selector).find('.variations_button');
	    selector.remove();
	    
	    return true;
	} // end hideVariableAddToCartButton

	function hideGroupedAddToCartButton()
	{
		if (!jQuery('#groupedHideAddToCartButton').length > 0) {
			return false;
		}
		
		selector = jQuery('#groupedHideAddToCartButton').parent();
        selector = jQuery(selector).find('.single_add_to_cart_button');
        selector.remove();
        selector = jQuery(selector).find('button');
        selector.remove();
        
        return true;
	} // end hideGroupedAddToCartButton
	
	var result = hideVariableAddToCartButton();
	
	if (result) {
		return true;
	}

	hideGroupedAddToCartButton();
	
}); 