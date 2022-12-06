/*
sourced from: woocom...ssets/js/admin/wc-enhanced-select.min.js?ver=3.2.5
global wc_enhanced_select_params 
*/
jQuery( function( $ ) {

	try {
		$( document.body )

			.on( 'vtprd-enhanced-select-init', function() {
				
        // Ajax product search box
				//.vtprd-product-search = select box class
        $( ':input.vtprd-product-search' ).each( function() {
					var select2_args = {
						allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
            multiple: true,
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
            language: { errorLoading:function(){ return "Not Found" } }, //sometimes not founds don't return standard msg
            escapeMarkup: function( m ) {
							return m;
						},          
						ajax: {
              url: ajaxurl, // AJAXURL is predefined in WordPress admin, don't need vtprdProductSelect.ajax_url with localize!! 
							dataType:    'json',
							delay:       250,
							data:        function( params ) {
								return {
									      term:     params.term,
												action: 'vtprd_product_search_ajax'                
								};
							},
							processResults: function( data ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( { id: id, text: text } );
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args );
    
				}); //end PRODUCT search
 
 				
        // NO Ajax category search box - all categories are pre-loaded
        //   vtprd-noajax-search is now used across many different selects!!!
				//.vtprd-category-search = select box class
        //$( ':input.vtprd-category-search' ).each( function() {
        $( ':input.vtprd-noajax-search' ).each( function() {
					var select2_args = {
						allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
            multiple: true,
						//minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
            language: { errorLoading:function(){ return "Not Found" } }, //sometimes not founds don't return standard msg
            escapeMarkup: function( m ) {
							return m;
						}
					};

					$( this ).selectWoo( select2_args );
    
				}); //end category search       
/*
	       //SUPERCEDED with above - but all parts still in the code....
        // Ajax category search box
				//.vtprd-category-search = select box class
        $( ':input.vtprd-category-search' ).each( function() {
					var select2_args = {
						allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
            multiple: true,
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
            language: { errorLoading:function(){ return "Not Found" } }, //sometimes not founds don't return standard msg
            escapeMarkup: function( m ) {
							return m;
						},          
						ajax: {
              url: ajaxurl, // AJAXURL is predefined in WordPress admin, don't need  vtprdcategorySelect.ajax_url with localize!! 
							dataType:    'json',
							delay:       350, //longer delay to not get a temp error...
							data:        function( params ) {
								return {
									      term:     params.term,
												action: 'vtprd_category_search_ajax',
                        catid:  $( this ).data( 'catid' ) //pass the taxonomy from the html back to PHP with ajax call               
								};
							},
							processResults: function( data ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( { id: id, text: text } );
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args );
    
				}); //end category search 
*/
            
 				
        // Ajax customer search box
				//.vtprd-customer-search = select box class
        $( ':input.vtprd-customer-search' ).each( function() {
					var select2_args = {
						allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
						placeholder: $( this ).data( 'placeholder' ),
            multiple: true,
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
            language: { errorLoading:function(){ return "Not Found" } }, //sometimes not founds don't return standard msg
            escapeMarkup: function( m ) {
							return m;
						},          
						ajax: {
              url: ajaxurl, // AJAXURL is predefined in WordPress admin, don't need  vtprdcustomerSelect.ajax_url with localize!! 
							dataType:    'json',
							delay:       250, 
							data:        function( params ) {
								return {
									      term:     params.term,
												action: 'vtprd_customer_search_ajax'        
								};
							},
							processResults: function( data ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( { id: id, text: text } );
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args );
    
				}); //end customer search       

 
          
			}) //end TRY

			//triggers the whole deal!!!
      .trigger( 'vtprd-enhanced-select-init' );
      

		$( 'html' ).on( 'click', function( event ) {
			if ( this === event.target ) {
				$( ':input.vtprd-product-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
        //	$( '.wc-enhanced-select, :input.wc-product-search, :input.wc-customer-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
			}
		} );
    
	} catch( err ) {
		// If select2 failed (conflict?) log the error but don't stop other scripts breaking.
		window.console.log( err );
	}
});
