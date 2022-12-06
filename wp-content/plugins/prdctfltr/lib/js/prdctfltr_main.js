(function($){
"use strict";

	var curr_data = {};

	$.expr[':'].Contains = function(a,i,m){
		return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
	};

	String.prototype.getValueByKey = function (k) {
		var p = new RegExp('\\b' + k + '\\b', 'gi');
		return this.search(p) != -1 ? decodeURIComponent(this.substr(this.search(p) + k.length + 1).substr(0, this.substr(this.search(p) + k.length + 1).search(/(&|;|$)/))) : "";
	};

	function prdctfltr_sort_classes() {
		if ( prdctfltr.ajax_class == '' ) {
			prdctfltr.ajax_class = '.products';
		}
		if ( prdctfltr.ajax_category_class == '' ) {
			prdctfltr.ajax_category_class = '.product-category';
		}
		if ( prdctfltr.ajax_products_class == '' ) {
			prdctfltr.ajax_products_class = '.type-product';
		}
		if ( prdctfltr.ajax_pagination_class == '' ) {
			prdctfltr.ajax_pagination_class = '.woocommerce-pagination';
		}
	}
	prdctfltr_sort_classes();

	function prdctfltr_filter_terms_init(curr) {
		curr = ( curr == null ? $('#prdctfltr_woocommerce').length > 0 ? $('#prdctfltr_woocommerce') : $('.prdctfltr_woocommerce') : curr );

		curr.each( function() {
			var curr_el = $(this);
			if ( curr_el.hasClass('prdctfltr_search_fields') ) {
				curr_el.find('.prdctfltr_filter:not(.prdctfltr_range, .prdctfltr_byprice, .prdctfltr_orderby, .prdctfltr_instock, .prdctfltr_per_page) .prdctfltr_checkboxes').each( function() {
					var curr_list = $(this);
					prdctfltr_filter_terms(curr_list)
				});
			}
		});

	}
	prdctfltr_filter_terms_init();

	function prdctfltr_init_tooltips(curr) {

		curr = ( curr == null ? $('#prdctfltr_woocommerce').length > 0 ? $('#prdctfltr_woocommerce') : $('.prdctfltr_woocommerce') : curr );

		curr.each( function() {
			var curr_el = $(this);

			var $pf_tooltips = curr_el.find('.prdctfltr_filter.pf_attr_img label, .prdctfltr_terms_customized:not(.prdctfltr_terms_customized_select) label');

			$pf_tooltips
			.on('mouseover', function()
			{
				var $this = $(this);

				if ($this.prop('hoverTimeout'))
				{
					$this.prop('hoverTimeout', clearTimeout($this.prop('hoverTimeout')));
				}

				$this.prop('hoverIntent', setTimeout(function()
				{
					$this.addClass('prdctfltr_hover');
				}, 250));
				})
			.on('mouseleave', function()
				{
				var $this = $(this);

				if ($this.prop('hoverIntent'))
				{
					$this.prop('hoverIntent', clearTimeout($this.prop('hoverIntent')));
				}

				$this.prop('hoverTimeout', setTimeout(function()
				{
					$this.removeClass('prdctfltr_hover');
				}, 250));
			});
		});

	}
	prdctfltr_init_tooltips();

	function prdctfltr_show_opened_widgets() {

		if ( $('.prdctfltr-widget').length > 0 && $('.prdctfltr-widget .prdctfltr_error').length !== 1 ) {
			$('.prdctfltr-widget .prdctfltr_filter').each( function() {

				var curr = $(this);

				if ( curr.find('input[type="checkbox"]:checked').length > 0 ) {

					curr.find('.prdctfltr_widget_title .prdctfltr-down').removeClass('prdctfltr-down').addClass('prdctfltr-up');
					curr.find('.prdctfltr_checkboxes').addClass('prdctfltr_down').css({'display':'block'});

				}
			});
		}

	}
	prdctfltr_show_opened_widgets();

	function prdctfltr_init_scroll(curr) {

		curr = ( curr == null ? $('#prdctfltr_woocommerce') : curr );

		if ( curr.hasClass('prdctfltr_scroll_active') ) {

			curr.find('.prdctfltr_filter:not(.prdctfltr_range) .prdctfltr_checkboxes').mCustomScrollbar({
				axis:'y',
				scrollInertia:550,
				autoExpandScrollbar:true,
				advanced:{
					updateOnBrowserResize:true,
					updateOnContentResize:true
				}
			});

			if ( curr.hasClass('pf_mod_row') && ( curr.find('.prdctfltr_checkboxes').length > $('.prdctfltr_filter_wrapper:first').attr('data-columns') ) ) {
				if ( $('.prdctfltr-widget').length == 0 || $('.prdctfltr-widget').length == 1 && $('.prdctfltr-widget .prdctfltr_error').length == 1 ) {

					if ( curr.hasClass('prdctfltr_slide') ) {
						curr.find('.prdctfltr_woocommerce_ordering').show();
					}

					var curr_scroll_column = curr.find('.prdctfltr_filter:first').width();
					var curr_columns = curr.find('.prdctfltr_filter').length;

					curr.find('.prdctfltr_filter_inner').css('width', curr_columns*curr_scroll_column);
					curr.find('.prdctfltr_filter').css('width', curr_scroll_column);
					
					curr.find('.prdctfltr_filter_wrapper').mCustomScrollbar({
						axis:'x',
						scrollInertia:550,
						scrollbarPosition:'outside',
						autoExpandScrollbar:true,
						advanced:{
							updateOnBrowserResize:true,
							updateOnContentResize:false
						}
					});

					if ( curr.hasClass('prdctfltr_slide') ) {
						curr.find('.prdctfltr_woocommerce_ordering').hide();
					}
				}
			}

			if ( $('.prdctfltr-widget').length == 0 || $('.prdctfltr-widget .prdctfltr_error').length == 1 ) {
				curr.find('.prdctfltr_slide .prdctfltr_woocommerce_ordering').hide();
			}

		}
	}

	function prdctfltr_show_opened_cats(curr) {

		curr = ( curr == null ? $('#prdctfltr_woocommerce') : curr );

		curr.find('label.prdctfltr_active').each( function() {
			$(this).next().show();
			$(this).parents('.prdctfltr_sub').each( function() {
				$(this).show();
				if ( !$(this).prev().hasClass('prdctfltr_clicked') ) {
					$(this).prev().addClass('prdctfltr_clicked');
				}
			});
		});

/*		curr.find('.prdctfltr_sub label.prdctfltr_active').each( function() {
			var curr = $(this).parent();
			if ( !curr.is(':visible') ) {
				curr.show();

			}
		});*/

	}

	function prdctfltr_all_cats(curr) {

		curr = ( curr == null ? $('#prdctfltr_woocommerce') : curr );

		curr.find('.prdctfltr_filter.prdctfltr_attributes.prdctfltr_expand_parents .prdctfltr_sub').each( function() {
			var curr = $(this);
			if ( !curr.is(':visible') ) {
				curr.show();
				if ( !curr.prev().hasClass('prdctfltr_clicked') ) {
					curr.prev().addClass('prdctfltr_clicked');
				}
			}
		});

	}

	function prdctfltr_check(curr, curr_chckbx, curr_var) {

		var curr_filter = curr.closest('.prdctfltr_woocommerce');

		if ( curr.hasClass('prdctfltr_multi') ) {

			if ( curr_chckbx.val() !== '' ) {

				if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {

					if ( curr.hasClass('prdctfltr_merge_terms') ) {
						var curr_settings = ( curr.children(':first').val().indexOf('+') > 0 ? curr.children(':first').val().replace('+' + curr_var, '').replace(curr_var + '+', '') : '' );
					}
					else {
						var curr_settings = ( curr.children(':first').val().indexOf(',') > 0 ? curr.children(':first').val().replace(',' + curr_var, '').replace(curr_var + ',', '') : '' );
					}

					var curr_name = curr.children(':first').attr('name');
					var curr_chckbxval = curr_chckbx.attr('value');

					curr_filter.find('input[name="'+curr_name+'"]').val(curr_settings);
					curr_filter.find('.prdctfltr_filter[data-filter="'+curr_name+'"] input[value="'+curr_chckbxval+'"]').prop('checked', false).parent().removeClass('prdctfltr_active');

				}
				else {

					if ( curr.hasClass('prdctfltr_merge_terms') ) {
						var curr_settings = ( curr.children(':first').val() == '' ? curr_var : curr.children(':first').val() + '+' + curr_var );
					}
					else {
						var curr_settings = ( curr.children(':first').val() == '' ? curr_var : curr.children(':first').val() + ',' + curr_var );
					}

					var curr_name = curr.children(':first').attr('name');
					var curr_chckbxval = curr_chckbx.attr('value');

					curr_filter.find('input[name="'+curr_name+'"]').val(curr_settings);
					curr_filter.find('.prdctfltr_filter[data-filter="'+curr_name+'"] input[value="'+curr_chckbxval+'"]').prop('checked', true).parent().addClass('prdctfltr_active');

				}
			}
			else {

				var curr_name = curr.children(':first').attr('name');

				curr_filter.find('.prdctfltr_filter[data-filter="'+curr_name+'"] input[name="'+curr_name+'"]').each( function() {
					var curr_field = $(this);

					curr_field.val('');
					curr_field.closest('.prdctfltr_filter').find('input:not([type="hidden"])').prop('checked', false).change().parent().removeClass('prdctfltr_active');
				});

			}


		}
		else {

			if ( curr_chckbx.val() == '' ) {

				var curr_name = curr.children(':first').attr('name');

				curr_filter.find('.prdctfltr_filter[data-filter="'+curr_name+'"] input[name="'+curr_name+'"]').each( function() {
					var curr_field = $(this);

					curr_field.val('');
					curr_field.closest('.prdctfltr_filter').find('input:not([type="hidden"]):checked').prop('checked', false).change().parent().removeClass('prdctfltr_active');
				});

			}
			else {

				if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {

					var curr_name = curr.children(':first').attr('name');
					var curr_chckbxval = curr_chckbx.attr('value');

					curr_filter.find('.prdctfltr_filter[data-filter="'+curr_name+'"] input[name="'+curr_name+'"]').each( function() {
						var curr_field = $(this);

						curr_field.val('');
						curr_field.closest('.prdctfltr_filter').find('input[value="'+curr_chckbxval+'"]').prop('checked', false).change().parent().removeClass('prdctfltr_active');

					});

				}
				else {

					var curr_name = curr.children(':first').attr('name');
					var curr_chckbxval = curr_chckbx.attr('value');

					curr_filter.find('.prdctfltr_filter[data-filter="'+curr_name+'"] input[name="'+curr_name+'"]').each( function() {
						var curr_field = $(this);

						curr_field.val(curr_var);
						curr_field.closest('.prdctfltr_filter').find('input:not([type="hidden"])').prop('checked', false).change().parent().removeClass('prdctfltr_active');
						curr_field.closest('.prdctfltr_filter').find('input[value="'+curr_chckbxval+'"]').prop('checked', true).change().parent().addClass('prdctfltr_active');

					});

				}

			}
		}

		prdctfltr_submit_form(curr_filter);

	}

	function prdctfltr_submit_form(curr_filter) {

		curr_filter = ( curr_filter == null ? $('#prdctfltr_woocommerce') : curr_filter );

		if ( curr_filter.hasClass('prdctfltr_click_filter') || curr_filter.find('input[name="reset_filter"]:checked').length > 0 ) {

			var curr = curr_filter.find('.prdctfltr_woocommerce_ordering');

			prdctfltr_respond(curr);

		}

	}

	$('.prdctfltr_woocommerce').each( function() {

		var curr = $(this);

		prdctfltr_init_scroll(curr);

		if ( curr.find('.prdctfltr_filter.prdctfltr_attributes.prdctfltr_expand_parents').length > 0 ) {
			prdctfltr_all_cats(curr);
		}
		else {
			prdctfltr_show_opened_cats(curr);
		}

		if ( curr.hasClass('pf_mod_masonry') ) {
			curr.find('.prdctfltr_filter_inner').isotope({
				resizable: false,
				masonry: { }
			});
			if ( !curr.hasClass('prdctfltr_always_visible') ) {
				curr.find('.prdctfltr_woocommerce_ordering').hide();
			}
		}

		if ( curr.attr('class').indexOf('pf_sidebar_css') > 0 ) {
			if ( curr.hasClass('pf_sidebar_css_right') ) {
				$('body').css('right', '0px');
			}
			else {
				$('body').css('left', '0px');
			}
			if ( !$('body').hasClass('wc-prdctfltr-active-overlay') ) {
				$('body').addClass('wc-prdctfltr-active-overlay');
			}
		}

	});

	$(document).on( 'change', 'input[name^="rng_"]', function() {
		var curr = $(this).closest('.prdctfltr_woocommerce');

		if ( curr.hasClass('prdctfltr_click_filter') ) {
			prdctfltr_respond(curr.find('.prdctfltr_woocommerce_ordering'));
		}
	});

	$(document).on('click', '.prdctfltr_woocommerce_filter_submit', function() {

		var curr = $(this).parent().parent();

		prdctfltr_respond(curr);

		return false;

	});

	$(document).on('click', '.prdctfltr_woocommerce_filter', function() {

		var curr_filter = $(this).closest('.prdctfltr_woocommerce');

		if (curr_filter.hasClass('pf_mod_masonry') ) {
			var chck_height = curr_filter.find('.prdctfltr_filter_inner').height();
			if ( chck_height == 0 ) {
				var curr_check = curr_filter.find('.prdctfltr_woocommerce_ordering')
				curr_check.show().find('.prdctfltr_filter_inner').isotope('layout');
				curr_check.hide();
			}
		}

		if ( !curr_filter.hasClass('prdctfltr_always_visible') ) {
			var curr = $(this).closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_ordering');

			if( $(this).hasClass('prdctfltr_active') ) {
				if ( curr_filter.attr('class').indexOf( 'pf_sidebar' ) == -1 ) {
					if ( curr_filter.hasClass( 'pf_fullscreen' ) ) {
						curr.stop(true,true).fadeOut(200, function() {
							curr.find('.prdctfltr_close_sidebar').remove();
						});
					}
					else {
						curr.stop(true,true).slideUp(200);
					}
				}
				else {
					curr.stop(true,true).fadeOut(200, function() {
						curr.find('.prdctfltr_close_sidebar').remove();
					});
					if ( curr_filter.attr('class').indexOf( 'pf_sidebar_css' ) > 0 ) {
						if ( curr_filter.hasClass('pf_sidebar_css_right') ) {
							$('body').css({'right':'0px','bottom':'auto','top':'auto','left':'auto'});
						}
						else {
							$('body').css({'right':'auto','bottom':'auto','top':'auto','left':'0px'});
						}
						$('.prdctfltr_overlay').remove();
					}
				}
				$(this).removeClass('prdctfltr_active');
				$('body').removeClass('wc-prdctfltr-active');
			}
			else {
				$(this).addClass('prdctfltr_active')
				if ( curr_filter.attr('class').indexOf( 'pf_sidebar' ) == -1 ) {
					$('body').addClass('wc-prdctfltr-active');
					if ( curr_filter.hasClass( 'pf_fullscreen' ) ) {
						curr.prepend('<div class="prdctfltr_close_sidebar"><i class="prdctfltr-delete"></i> '+prdctfltr.localization.close_filter+'</div>');
						curr.stop(true,true).fadeIn(200);

						var curr_height = $(window).height() - curr.find('.prdctfltr_filter_inner').outerHeight() - curr.find('.prdctfltr_close_sidebar').outerHeight() - curr.find('.prdctfltr_buttons').outerHeight();

						if ( curr_height > 128 ) {
							var curr_diff = curr_height/2;
							curr_height = curr.outerHeight();
							curr.css({'padding-top':curr_diff+'px'});
						}
						else {
							curr_height = $(window).height() - curr.find('.prdctfltr_close_sidebar').outerHeight() - curr.find('.prdctfltr_buttons').outerHeight() -128;
						}
						curr_filter.find('.prdctfltr_filter_wrapper').css({'max-height':curr_height});
					}
					else {
						curr.stop(true,true).slideDown(200);
					}
				}
				else {
					curr.prepend('<div class="prdctfltr_close_sidebar"><i class="prdctfltr-delete"></i> '+prdctfltr.localization.close_filter+'</div>');
					curr.stop(true,true).fadeIn(200);
					if ( curr_filter.attr('class').indexOf( 'pf_sidebar_css' ) > 0 ) {
						$('body').append('<div class="prdctfltr_overlay"></div>');
						if ( curr_filter.hasClass('pf_sidebar_css_right') ) {
							$('body').css({'right':'160px','bottom':'auto','top':'auto','left':'auto'});
						}
						else {
							$('body').css({'right':'auto','bottom':'auto','top':'auto','left':'160px'});
						}
					}
					$('body').addClass('wc-prdctfltr-active');
				}

			}
		}

		return false;
	});

	$(document).on('click', '.prdctfltr_overlay, .prdctfltr_close_sidebar', function() {

		if ( $(this).closest('.prdctfltr_woocommerce').length > 0 ) {
			$(this).closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter.prdctfltr_active').trigger('click');
		}
		else {
			$('.prdctfltr_woocommerce_filter.prdctfltr_active:first').trigger('click');
		}

	});

	$(document).on('click', '.pf_default_select .prdctfltr_widget_title', function() {

		var curr = $(this).closest('.prdctfltr_filter').find('.prdctfltr_checkboxes');

		if ( !curr.hasClass('prdctfltr_down') ) {
			curr.prev().find('.prdctfltr-down').attr('class', 'prdctfltr-up');
			curr.addClass('prdctfltr_down');
			curr.slideDown(100);
		}
		else {
			curr.slideUp(100);
			curr.removeClass('prdctfltr_down');
			curr.prev().find('.prdctfltr-up').attr('class', 'prdctfltr-down');
		}

	});

	var pf_select_opened = false;
	$(document).on('click', '.pf_select .prdctfltr_filter > span, .prdctfltr_terms_customized_select.prdctfltr_filter > span', function() {
		pf_select_opened = true;
		var curr = $(this).next();

		if ( !curr.hasClass('prdctfltr_down') ) {
			curr.prev().find('.prdctfltr-down').attr('class', 'prdctfltr-up');
			curr.addClass('prdctfltr_down');
			curr.slideDown(100, function() {
				pf_select_opened = false;
			});
			curr.closest('.prdctfltr_filter').css({ 'z-index' : 2 });
			if ( !$('body').hasClass('wc-prdctfltr-select') ) {
				$('body').addClass('wc-prdctfltr-select');
			}
		}
		else {
			curr.slideUp(100, function() {
				pf_select_opened = false;
				curr.closest('.prdctfltr_filter').css({ 'z-index' : 'initial' });
			});
			curr.removeClass('prdctfltr_down');
			curr.prev().find('.prdctfltr-up').attr('class', 'prdctfltr-down');
			if ( curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_down').length == 0 ) {
				$('body').removeClass('wc-prdctfltr-select');
			}
		}

	});

	$(document).on( 'click', 'body.wc-prdctfltr-select', function(e) {

		var curr_target = $(e.target);

		if ( $('.prdctfltr_woocommerce').find('.prdctfltr_down').length > 0 && pf_select_opened === false && !curr_target.is('span, input, i') ) {
			$('.prdctfltr_woocommerce').find('.prdctfltr_down').each( function() {
				var curr = $(this);
				if ( curr.is(':visible') ) {
					curr.slideUp(100);
					curr.removeClass('prdctfltr_down');
					curr.prev().find('.prdctfltr-up').attr('class', 'prdctfltr-down');
				}
			});
			$('body').removeClass('wc-prdctfltr-select');
		}
	});

	$(document).on('click', 'span.prdctfltr_sale input[type="checkbox"], span.prdctfltr_instock input[type="checkbox"], span.prdctfltr_reset input[type="checkbox"]', function() {

		var curr = $(this).parent();
		var curr_filter = $(this).closest('.prdctfltr_woocommerce');

		if ( !curr.hasClass('prdctfltr_active') ) {
			curr.addClass('prdctfltr_active');
		}
		else {
			curr.removeClass('prdctfltr_active');
		}

		prdctfltr_submit_form(curr_filter);

	});

	$(document).on('click', '.prdctfltr_instock:not(span) label, .prdctfltr_orderby:not(span) label, .prdctfltr_per_page:not(span) label', function() {

		var label = $(this);
		var curr_chckbx = label.find('input[type="checkbox"]');
		var curr = curr_chckbx.closest('.prdctfltr_filter');
		var curr_var = curr_chckbx.val();
		var curr_filter = curr_chckbx.closest('.prdctfltr_woocommerce');

		if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {

			curr.children(':first').val('');
			curr.find('input:not([type="hidden"])').prop('checked', false);
			curr.find('label').removeClass('prdctfltr_active');

		}
		else {

			curr.children(':first').val(curr_var);
			curr.find('input:not([type="hidden"])').prop('checked', false);
			curr.find('label').removeClass('prdctfltr_active');
			curr_chckbx.prop('checked', true);
			curr_chckbx.parent().addClass('prdctfltr_active')

			if ( curr_chckbx.closest('.prdctfltr_woocommerce').hasClass('pf_select') || curr.hasClass('prdctfltr_terms_customized_select') ) {
				curr_chckbx.closest('.prdctfltr_filter').closest('.prdctfltr_checkboxes').slideUp(250).removeClass('prdctfltr_down');
				curr_chckbx.closest('.prdctfltr_filter').find('.prdctfltr_regular_title i').removeClass('prdctfltr-up').addClass('prdctfltr-down');
			}

		}

		prdctfltr_submit_form(curr_filter);
		return false;
	});

	$(document).on('click', '.prdctfltr_byprice label', function() {

		var label = $(this);
		var curr_chckbx = label.find('input[type="checkbox"]');
		var curr = curr_chckbx.closest('.prdctfltr_filter');
		var curr_var = curr_chckbx.val().split('-');
		var curr_filter = curr_chckbx.closest('.prdctfltr_woocommerce');

		if ( curr_chckbx.parent().hasClass('prdctfltr_active') ) {

			curr.children(':first').val('');
			curr.children(':first').next().val('');
			curr.find('input:not([type="hidden"])').prop('checked', false);
			curr.find('label').removeClass('prdctfltr_active');

		}
		else {

			curr.children(':first').val(curr_var[0]);
			curr.children(':first').next().val(curr_var[1]);
			curr.find('input:not([type="hidden"])').prop('checked', false);
			curr.find('label').removeClass('prdctfltr_active');
			curr_chckbx.prop('checked', true);
			curr_chckbx.parent().addClass('prdctfltr_active');

		}

		prdctfltr_submit_form(curr_filter);
		return false;
	});

	$(document).on('click', '.prdctfltr_attributes input[type="checkbox"]', function() {

		var curr_chckbx = $(this);
		var curr = curr_chckbx.closest('.prdctfltr_filter');
		var curr_var = curr_chckbx.val();
		var curr_filter = curr.closest('.prdctfltr_woocommerce');

		if ( curr_filter.hasClass('pf_adptv_unclick') ) {
			if ( curr_chckbx.parent().hasClass( 'pf_adoptive_hide' ) ) {
				return false;
			}
		}

		prdctfltr_check(curr, curr_chckbx, curr_var);

	});


	$(document).on('click', '.prdctfltr_filter_title a.prdctfltr_title_remove, .prdctfltr_regular_title a, .prdctfltr_widget_title a', function() {

		var curr_deep = false;
		if ( !$(this).hasClass('prdctfltr_title_remove') ) {
			var curr_deep = true;
			var curr = $(this).closest('.prdctfltr_filter');
		}

		var curr_filter = $(this).closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_ordering');
		var curr_key = $(this).attr('data-key');

		if ( curr_key == 'byprice' ) {
			curr_filter.find('.prdctfltr_byprice input[type="hidden"], .prdctfltr_price input[type="hidden"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key == 'products_per_page' ) {
			curr_filter.find('.prdctfltr_per_page input[type="hidden"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key == 'instock_products' ) {
			curr_filter.find('.prdctfltr_filter.prdctfltr_instock input[type="hidden"], span.prdctfltr_instock input[type="checkbox"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key == 'sale_products' ) {
			curr_filter.find('span.prdctfltr_sale input[type="checkbox"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key.substr(0,4) == 'rng_' ) {
			curr_filter.find('.prdctfltr_range input[type="hidden"][name$="'+curr_key.substr(4, curr_key.length)+'"]').each(function() {
				$(this).remove();
			});
		}
		else if ( curr_key == 'product_cat' ) {

			var curr_els = curr_filter.find('.prdctfltr_attributes input[name="product_cat"], .prdctfltr_add_inputs input[name="product_cat"]');

			if ( curr_deep === true && curr_els.length > 1 ) {

				var cur_vals = curr.find('input[type="checkbox"]:checked');
				cur_vals.each( function() {

					var curr_value = $(this).val();

					curr_els.each( function() {

						var curr_chckd = $(this);
						var curr_chckdval = $(this).val();

						if ( curr_chckdval.indexOf( ',' ) > 0 ) {
							curr_chckd.val(curr_chckdval.replace(',' + curr_value, '').replace(curr_value + ',', ''));
						}
						else if ( curr_chckdval.indexOf( '+' ) > 0 ) {
							curr_chckd.val(curr_chckdval.replace('+' + curr_value, '').replace(curr_value + '+', ''));
						}
						else {
							curr_chckd.val(curr_chckdval.replace(curr_value, '').replace(curr_value, ''));
						}

					});

				});

			}
			else {

				curr_filter.find('.prdctfltr_attributes input[name="product_cat"], .prdctfltr_add_inputs input[name="product_cat"]').each(function() {
					$(this).remove();
				});

			}
		}
		else if ( curr_key == 'product_tag' ) {
			var curr_els = curr_filter.find('.prdctfltr_attributes input[name="product_tag"]');

			if ( curr_deep === true && curr_els.length > 1 ) {

				var cur_vals = curr.find('input[type="checkbox"]:checked');
				cur_vals.each( function() {

					var curr_value = $(this).val();

					curr_els.each( function() {

						var curr_chckd = $(this);
						var curr_chckdval = $(this).val();

						if ( curr_chckdval.indexOf( ',' ) > 0 ) {
							curr_chckd.val(curr_chckdval.replace(',' + curr_value, '').replace(curr_value + ',', ''));
						}
						else if ( curr_chckdval.indexOf( '+' ) > 0 ) {
							curr_chckd.val(curr_chckdval.replace('+' + curr_value, '').replace(curr_value + '+', ''));
						}
						else {
							curr_chckd.val(curr_chckdval.replace(curr_value, '').replace(curr_value, ''));
						}

					});

				});

			}
			else {
				curr_filter.find('.prdctfltr_attributes input[name="product_tag"]').each(function() {
					$(this).remove();
				});
			}
		}
		else {
			var curr_els = curr_filter.find('.prdctfltr_'+curr_key+' > input[type="hidden"]');

			if ( curr_deep === true && curr_els.length > 1 ) {

				var cur_vals = curr.find('input[type="checkbox"]:checked');
				cur_vals.each( function() {

					var curr_value = $(this).val();

					curr_els.each( function() {

						var curr_chckd = $(this);
						var curr_chckdval = $(this).val();

						if ( curr_chckdval.indexOf( ',' ) > 0 ) {
							curr_chckd.val(curr_chckdval.replace(',' + curr_value, '').replace(curr_value + ',', ''));
						}
						else if ( curr_chckdval.indexOf( '+' ) > 0 ) {
							curr_chckd.val(curr_chckdval.replace('+' + curr_value, '').replace(curr_value + '+', ''));
						}
						else {
							curr_chckd.val(curr_chckdval.replace(curr_value, '').replace(curr_value, ''));
						}

					});

				});

			}
			else {
				curr_filter.find('.prdctfltr_'+curr_key+' > input[type="hidden"]').each(function() {
					$(this).remove();
				});
			}
		}

		prdctfltr_respond(curr_filter);

		return false;
	});

	$(document).on('click', '.prdctfltr_checkboxes label > i', function() {

		var curr = $(this).parent().next();

		$(this).parent().toggleClass('prdctfltr_clicked');

		if ( curr.hasClass('prdctfltr_sub') ) {
			curr.slideToggle(100, function() {
				if ( curr.closest('.prdctfltr_woocommerce').hasClass('pf_mod_masonry') ) {
					curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_filter_inner').isotope('layout');
				}
			});

		}

		return false;

	});

	$(document).on('click', '.prdctfltr_sc_products '+prdctfltr.ajax_pagination_class+' a, .archive.woocommerce '+prdctfltr.ajax_pagination_class+' a', function() {

		var curr = $(this).closest(prdctfltr.ajax_pagination_class);

		var curr_sc = ( curr.closest('.prdctfltr_sc_products').length > 0 ? curr.closest('.prdctfltr_sc_products') : $('.prdctfltr_sc_products:first').length > 0 ? $('.prdctfltr_sc_products:first') : $('.prdctfltr_woocommerce:first').length > 0 ? $('.prdctfltr_woocommerce:first') : 'none' );

		if ( curr_sc == 'none' ) {
			return;
		}

		if ( curr_sc.hasClass('prdctfltr_ajax') ) {
			var curr_filter = ( curr_sc.find('.prdctfltr_woocommerce').length > 0 ? curr_sc.find('.prdctfltr_woocommerce') : $('.prdctfltr-widget').find('.prdctfltr_woocommerce') );
		}
		else if ( prdctfltr.use_ajax == 'yes' && $('.prdctfltr_sc_products').length == 0 ) {
			var curr_filter = curr_sc;
		}
		else {
			return;
		}

		var curr_loader = curr_sc.find('.prdctfltr_woocommerce').attr('data-loader');
		if ( curr_sc.find('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter i').length > 0 ) {
			curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter').addClass('pf_ajax_loading');
			curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter i').replaceWith('<img src="'+prdctfltr.url+'/lib/images/svg-loaders/'+curr_loader+'.svg" class="prdctfltr_reset_this prdctfltr_loader" />');
		}
		else {
			curr.closest('.prdctfltr_woocommerce').prepend('<img src="'+prdctfltr.url+'/lib/images/svg-loaders/'+curr_loader+'.svg" class="prdctfltr_reset_this prdctfltr_loader" />');
		}

		if ( curr_filter !== null ) {

			curr_filter.find('.prdctfltr_filter input[type="hidden"], .prdctfltr_add_inputs input[type="hidden"]:not([name="post_type"])').each(function() {

				var curr_val = $(this).val();
				var curr_name = $(this).attr('name');

				if ( curr_val == '' ) {
					$(this).remove();
				}
			});

			var curr_href = $(this).attr('href');
 
			if ( curr_href.indexOf('paged=') >= 0 ) {
				var pf_paged = curr_href.getValueByKey('paged');
			}
			else {
				return false;
			}

			var curr_fields = {};

			curr_filter.find('.prdctfltr_filter input[type="hidden"]').each( function() {
				if ( $(this).attr('value') !== '' ) {
					curr_fields[$(this).attr('name')] = $(this).attr('value');
				}
			});
			if ( curr_filter.find('input[name="sale_products"]:checked').length > 0 ) {
				curr_fields['sale_products'] = 'on';
			}
			if ( curr_filter.find('input[name="instock_products"]:checked').length > 0 ) {
				curr_fields['instock_products'] = 'in';
			}

			var curr_widget = 'no';
			if ( $('.prdctfltr-widget').length > 0 && $('.prdctfltr-widget .prdctfltr_error').length == 1 ) {
				curr_widget = 'yes';
			}

			var data = {
				action: 'prdctfltr_respond',
				pf_query: curr_sc.attr('data-query'),
				pf_shortcode: curr_sc.attr('data-shortcode'),
				pf_page: curr_sc.attr('data-page'),
				pf_action: curr_sc.attr('action'),
				pf_paged: pf_paged,
				pf_filters: curr_fields,
				pf_widget: curr_widget,
				pf_mode: ( curr.closest('.prdctfltr_woocommerce').attr('id') == 'prdctfltr_woocommerce' ? 'yes' : 'no' )
			}

			data.pf_query = data.pf_query.replace('paged='+data.pf_page, 'paged='+data.pf_paged);

			if ( curr.closest('.prdctfltr_woocommerce').attr('data-lang') !== undefined ) {
				data.lang = curr.closest('.prdctfltr_woocommerce').attr('data-lang');
			}

			$.post(prdctfltr.ajax, data, function(response) {
				if (response) {

					var curr_response = $(response);

					if ( $('.prdctfltr_sc_products:first').length > 0 ) {

						curr_sc.after(curr_response);
						var curr_next = curr_sc.next();

						curr_next.css({'position':'absolute', 'top':0, 'left':0});

						var curr_products = ( curr_next.find(prdctfltr.ajax_products_class).length > 0 ? curr_next.find(prdctfltr.ajax_products_class) : curr_next.find('.type-product') );

						curr_products.css('opacity', 0);

						curr_sc.css({'position':'absolute', 'top':0, 'left':0}).fadeOut(100).remove();
						curr_next.removeAttr('style');

						curr_next = curr_next.find('.prdctfltr_woocommerce');

					}
					else {

						if ( curr_response.find('.prdctfltr_woocommerce').length > 0 ) {
							$('.prdctfltr_woocommerce:first').replaceWith(curr_response.find('.prdctfltr_woocommerce'));
							var curr_next = $('.prdctfltr_woocommerce:first');
						}
						else {
							var curr_next = $(prdctfltr.ajax_class+':first');
						}

						if ( curr_response.find(prdctfltr.ajax_class+':first').length > 0 ) {
							$(prdctfltr.ajax_class+':first').replaceWith(curr_response.find(prdctfltr.ajax_class+':first'));
							var curr_products = ( $(prdctfltr.ajax_class+':first').find(prdctfltr.ajax_products_class).length > 0 ? $(prdctfltr.ajax_class+':first').find(prdctfltr.ajax_products_class) : $(prdctfltr.ajax_class+':first').find('.type-product') );

							curr_products.css('opacity', 0);
						}
						if ( curr_response.find(prdctfltr.ajax_pagination_class).length > 0 ) {
							if ( $(prdctfltr.ajax_pagination_class).length > 0 ) {
								$(prdctfltr.ajax_pagination_class).replaceWith(curr_response.find(prdctfltr.ajax_pagination_class));
							}
							else {
								$(prdctfltr.ajax_class+':first').after(curr_response.find(prdctfltr.ajax_pagination_class));
							}
						}
						else {
							$(prdctfltr.ajax_pagination_class).remove();
						}
					}

					if ( prdctfltr.js !== '' ) {
						eval(prdctfltr.js);
					}

					if ( curr_next !== undefined ) {

						if ( curr_next.find('.prdctfltr_filter.prdctfltr_attribute.prdctfltr_expand_parents').length > 0 ) {
							prdctfltr_all_cats(curr_next);
						}
						else {
							prdctfltr_show_opened_cats(curr_next);
						}
						prdctfltr_init_scroll(curr_next);
						prdctfltr_filter_terms_init(curr_next);
						prdctfltr_init_tooltips(curr_next);

						if ( curr_next !== undefined ) {
							if ( curr_next.hasClass('pf_mod_masonry') ) {

								curr_next.find('.prdctfltr_woocommerce_ordering').show();
								curr_next.find('.prdctfltr_filter_inner').isotope({
									resizable: false,
									masonry: { }
								});
								if ( !curr_next.hasClass('prdctfltr_always_visible') ) {
									curr_next.find('.prdctfltr_woocommerce_ordering').hide();
								}
							}

							curr_products.each(function(i) {
								$(this).delay((i++) * 100).fadeTo(100, 1);
							});
						}

					}

					curr_data['paginated'] == true;

				}
				else {
					alert(prdctfltr.localization.ajax_error);
				}
			});

			return false;

		}

	});

	function prdctfltr_respond(curr) {

		if ( curr.find('input[name="reset_filter"]:checked').length > 0 ) {
			curr.find('input[name="reset_filter"]').remove();
			curr.find('input[type="hidden"], input[name="sale_products"], input[name="instock_products"]:not([type="hidden"])').remove();
		}
		else {
			var curr_check = [];

			curr.find('.prdctfltr_filter input[type="hidden"], .prdctfltr_add_inputs input[type="hidden"]:not([name="post_type"])').each(function() {

				var curr_attr = $(this).attr('name');
				var curr_val = $(this).val();

				if ( curr_val == '' ) {
					$(this).remove();
				}
				else if ( $.inArray(curr_attr, curr_check) == -1 ) {
					curr_check.push(curr_attr);
				}
				else {
					$(this).remove();
				}
			});
		}

		curr.find('.prdctfltr_filter.prdctfltr_range').each( function() {
			var curr_rng = $(this);
			if ( curr_rng.find('[name^="rng_min_"]').val() == undefined || curr_rng.find('[name^="rng_max_"]').val() == undefined ) {
				curr_rng.find('input').remove();
			}
		});

		if ( ( curr.closest('.prdctfltr_sc_products').length > 0 && curr.closest('.prdctfltr_sc_products').hasClass('prdctfltr_ajax') ) || ( $('.prdctfltr_sc_products:first').length > 0 && $('.prdctfltr_sc_products:first').hasClass('prdctfltr_ajax') ) || ( $(prdctfltr.ajax_class+':first').length > 0 && prdctfltr.use_ajax == 'yes' && $('body').hasClass('archive') && $('body').hasClass('woocommerce') ) ) {

			var curr_loader = curr.closest('.prdctfltr_woocommerce').attr('data-loader');
			if ( curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter i').length > 0 ) {
				curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter').addClass('pf_ajax_loading');
				curr.closest('.prdctfltr_woocommerce').find('.prdctfltr_woocommerce_filter i').replaceWith('<img src="'+prdctfltr.url+'/lib/images/svg-loaders/'+curr_loader+'.svg" class="prdctfltr_reset_this prdctfltr_loader" />');
			}
			else {
				curr.closest('.prdctfltr_woocommerce').prepend('<img src="'+prdctfltr.url+'/lib/images/svg-loaders/'+curr_loader+'.svg" class="prdctfltr_reset_this prdctfltr_loader" />');
			}

			if ( $('body').hasClass('wc-prdctfltr-active') ) {

				var curr_filter = curr.closest('.prdctfltr_woocommerce');

				if ( curr_filter.attr('class').indexOf( 'pf_sidebar' ) == -1 ) {
					if ( curr_filter.hasClass( 'pf_fullscreen' ) ) {
						curr.stop(true,true).fadeOut(200, function() {
							curr.find('.prdctfltr_close_sidebar').remove();
						});
					}
					else {
						curr.stop(true,true).slideUp(200);
					}
				}
				else {
					curr.fadeOut(200);

					if ( curr_filter.attr('class').indexOf( 'pf_sidebar_css' ) > 0 ) {
						if ( curr_filter.hasClass('pf_sidebar_css_right') ) {
							$('body').css({'right':'0px','bottom':'auto','top':'auto','left':'auto'});
						}
						else {
							$('body').css({'right':'auto','bottom':'auto','top':'auto','left':'0px'});
						}
						$('.prdctfltr_overlay').remove();
					}
					curr_filter.find('.prdctfltr_woocommerce_filter').removeClass('prdctfltr_active');
					$('body').removeClass('wc-prdctfltr-active');

				}

			}

			var curr_sc = ( curr.closest('.prdctfltr_sc_products').length > 0 ? curr.closest('.prdctfltr_sc_products') : ( $('.prdctfltr_sc_products:first').length > 0 ? $('.prdctfltr_sc_products:first') : curr.closest('.prdctfltr_woocommerce') ) );

			var curr_fields = {};

			curr.find('.prdctfltr_filter input[type="hidden"]').each( function() {
				if ( $(this).attr('value') !== '' ) {
					curr_fields[$(this).attr('name')] = $(this).attr('value');
				}
			});
			if ( curr.find('input[name="sale_products"]:checked').length > 0 ) {
				curr_fields['sale_products'] = 'on';
			}
			if ( curr.find('input[name="instock_products"]:checked').length > 0 ) {
				curr_fields['instock_products'] = 'in';
			}

			var curr_widget = 'no';
			if ( $('.prdctfltr-widget').length > 0 && $('.prdctfltr-widget .prdctfltr_error').length !== 1 ) {
				curr_widget = 'yes';
			}

			if ( prdctfltr.analytics == 'yes' ) {

				var analyticsData = {
					action: 'prdctfltr_analytics',
					pf_filters: curr_fields,
					pf_nonce: $('.prdctfltr_wc:first').attr('data-nonce')
					
				}

				$.post(prdctfltr.ajax, analyticsData, function(response) {
					
				});

			}

			var data = {
				action: 'prdctfltr_respond',
				pf_query: curr_sc.attr('data-query'),
				pf_shortcode: curr_sc.attr('data-shortcode'),
				pf_page: ( curr_data['paginated'] !== undefined ? curr_sc.attr('data-page') : 1 ),
				pf_filters: curr_fields,
				pf_widget: curr_widget,
				pf_mode: ( curr.closest('.prdctfltr_woocommerce').attr('id') == 'prdctfltr_woocommerce' ? 'yes' : 'no' )
			}

			var curr_widget = curr.closest('.prdctfltr_woocommerce').parent();

			if ( curr.closest('.prdctfltr-widget').length > 0 && $('.prdctfltr-widget .prdctfltr_error').length !== 1 ) {


				var rpl = $('<div></div>').append(curr_widget.find('.prdctfltr_filter').children(':not(input):first').clone()).html().toString().replace(/\t/g, '');
				var rpl_off = $('<div></div>').append(curr_widget.find('.prdctfltr_filter').children(':not(input):first').find('.prdctfltr_widget_title').clone()).html().toString().replace(/\t/g, '');
				
				rpl = rpl.replace(rpl_off, '%%%');

				data.pf_preset = curr_widget.find('.prdctfltr_woocommerce').attr('data-preset');
				data.pf_template = curr_widget.find('.prdctfltr_woocommerce').attr('data-template');
				data.pf_widget_title = $.trim(rpl);

			}

			if ( curr.closest('.prdctfltr_woocommerce').attr('data-lang') !== undefined ) {
				data.lang = curr.closest('.prdctfltr_woocommerce').attr('data-lang');
			}

			$.post(prdctfltr.ajax, data, function(response) {

				if (response) {

					var curr_response = $(response);

					if ( $('.prdctfltr_sc_products:first').length > 0 ) {

						if ( curr_response.find('.prdctfltr-widget').length > 0 ) {
							curr_sc.after(curr_response);
						}
						else {
							curr_sc.after(curr_response).next().find('.prdctfltr-widget').remove();
						}

						var curr_next = curr_sc.next();

						curr_next.css({'position':'absolute', 'top':0, 'left':0});

						var curr_products = ( curr_next.find(prdctfltr.ajax_products_class).length > 0 ? curr_next.find(prdctfltr.ajax_products_class) : curr_next.find('.type-product') );

						curr_products.css('opacity', 0);

						curr_sc.css({'position':'absolute', 'top':0, 'left':0}).fadeOut(100).remove();
						curr_next.removeAttr('style');

						curr_next = curr_next.find('.prdctfltr_woocommerce');

					}
					else {

						if ( curr_response.find('.prdctfltr_woocommerce').length > 0 ) {
							$('.prdctfltr_woocommerce:first').replaceWith(curr_response.find('.prdctfltr_woocommerce'));
							var curr_next = $('.prdctfltr_woocommerce:first');
						}
						else {
							var curr_next = $(prdctfltr.ajax_class+':first');
						}

						if ( curr_response.find(prdctfltr.ajax_class+':first').length > 0 ) {
							$(prdctfltr.ajax_class+':first').replaceWith(curr_response.find(prdctfltr.ajax_class+':first'));
							var curr_products = ( $(prdctfltr.ajax_class+':first').find(prdctfltr.ajax_products_class).length > 0 ? $(prdctfltr.ajax_class+':first').find(prdctfltr.ajax_products_class) : $(prdctfltr.ajax_class+':first').find('.type-product') );

							if ( curr_products !== undefined ) {
								curr_products.css('opacity', 0);
							}

						}
						if ( curr_response.find(prdctfltr.ajax_pagination_class).length > 0 ) {
							if ( $(prdctfltr.ajax_pagination_class).length > 0 ) {
								$(prdctfltr.ajax_pagination_class).replaceWith(curr_response.find(prdctfltr.ajax_pagination_class));
							}
							else {
								$(prdctfltr.ajax_class+':first').after(curr_response.find(prdctfltr.ajax_pagination_class));
							}
						}
						else {
							$(prdctfltr.ajax_pagination_class).remove();
						}
					}

					var curr_widget = curr.closest('.prdctfltr_woocommerce').parent();

					if ( curr.closest('.prdctfltr-widget').length > 0 ) {

						if ( curr_response.find('.prdctfltr-widget').length > 0 ) {
							curr_widget.after( curr_response.find('.prdctfltr-widget') );
							var curr_next = curr_widget.next();

							curr_next.css({'position':'absolute', 'top':0, 'left':0});

							curr_widget.css({'position':'absolute', 'top':0, 'left':0}).fadeOut(100).remove();
							curr_next.removeAttr('style');
						}

					}

					if ( prdctfltr.js !== '' ) {
						eval(prdctfltr.js);
					}

					if ( curr_next !== undefined ) {

						if ( curr.find('.prdctfltr_filter.prdctfltr_attributes.prdctfltr_expand_parents').length > 0 ) {
							prdctfltr_all_cats(curr_next);
						}
						else {
							prdctfltr_show_opened_cats(curr_next);
						}
						prdctfltr_init_scroll(curr_next);
						prdctfltr_filter_terms_init(curr_next);
						prdctfltr_init_tooltips(curr_next);
						prdctfltr_show_opened_widgets();

						if ( curr_next !== undefined ) {
							if ( curr_next.hasClass('pf_mod_masonry') ) {

								curr_next.find('.prdctfltr_woocommerce_ordering').show();
								curr_next.find('.prdctfltr_filter_inner').isotope({
									resizable: false,
									masonry: { }
								});
								if ( !curr_next.hasClass('prdctfltr_always_visible') ) {
									curr_next.find('.prdctfltr_woocommerce_ordering').hide();
								}
							}

					}

						if ( curr_products !== undefined ) {
							curr_products.each(function(i) {
								$(this).delay((i++) * 100).fadeTo(100, 1);
							});
						}
					}

				}
				else {
					alert(prdctfltr.localization.ajax_error);
				}
			});

		}
		else {
			curr.find('.prdctfltr_filter input[type="hidden"]').each( function() {
				if ( curr.find('.prdctfltr_add_inputs input[name='+$(this).attr('name')+']').length > 0 ) {
					curr.find('.prdctfltr_add_inputs input[name='+$(this).attr('name')+']').remove();
				}
			});
			curr.submit();
		}

		return false;
	}

	if ( $('.prdctfltr-widget').length == 0 || $('.prdctfltr-widget .prdctfltr_error').length == 1 ) {

		$(window).on('resize', function() {

			$('.prdctfltr_woocommerce').each( function() {

				var curr = $(this);
		
				if ( curr.hasClass('pf_mod_row') ) {

					if ( window.matchMedia('(max-width: 768px)').matches ) {
						curr.find('.prdctfltr_filter_inner').css('width', 'auto');
					}
					else {
						var curr_columns = curr.find('.prdctfltr_filter_wrapper:first').attr('data-columns');

						var curr_scroll_column = curr.find('.prdctfltr_woocommerce_ordering').width();
						var curr_columns_length = curr.find('.prdctfltr_filter').length;

						curr.find('.prdctfltr_filter_inner').css('width', curr_columns_length*curr_scroll_column/curr_columns);
						curr.find('.prdctfltr_filter').css('width', curr_scroll_column/curr_columns);
					}
				}
			});
		});
	}

	if ((/Trident\/7\./).test(navigator.userAgent)) {
		$(document).on('click', '.prdctfltr_checkboxes label img', function() {
			$(this).parents('label').children('input:first').change().click();
		});
	}

	if ((/Trident\/4\./).test(navigator.userAgent)) {
		$(document).on('click', '.prdctfltr_checkboxes label > span > img, .prdctfltr_checkboxes label > span', function() {
			$(this).parents('label').children('input:first').change().click();
		});
	}

	function prdctfltr_filter_terms(list) {

		var curr_filter = list.closest('.prdctfltr_wc');
		var form = $("<div>").attr({"class":"prdctfltr_search_terms","action":"#"}),
		input = $("<input>").attr({"class":"prdctfltr_search_terms_input prdctfltr_reset_this","type":"text","placeholder":prdctfltr.localization.filter_terms});
		

		if ( curr_filter.hasClass('pf_select') || curr_filter.hasClass('pf_default_select') || list.closest('.prdctfltr_filter').hasClass('prdctfltr_terms_customized_select') ) {
			$(form).append("<i class='prdctfltr-search'></i>").append(input).prependTo(list);
		}
		else{
			$(form).append("<i class='prdctfltr-search'></i>").append(input).insertBefore(list);
		}

		$(input)
		.change( function () {
			var filter = $(this).val();
			if(filter) {
				var curr = $(this).closest('.prdctfltr_filter');
				if ( curr.find('div.prdctfltr_sub').length > 0 ) {
					$(list).find(".prdctfltr_sub:not(:visible)").css({'margin-left':0}).show().prev().addClass('prdctfltr_clicked');
					if ( curr.hasClass('prdctfltr_searching') === false ) {
						curr.addClass('prdctfltr_searching');
					}
				}
				$(list).find("label > span:not(:Contains(" + filter + "))").closest('label').hide();
				$(list).find("label > span:Contains(" + filter + ")").closest('label').show();
			} else {
				var curr = $(this).closest('.prdctfltr_filter');
				if ( curr.find('div.prdctfltr_sub').length > 0 ) {
					$(list).find(".prdctfltr_sub:visible").css({'margin-left':'22px'}).hide().prev().removeClass('prdctfltr_clicked');
				}
				curr.removeClass('prdctfltr_searching');
				$(list).find("label > span").closest('label').show();
			}
			return false;
		})
		.keyup( function () {
			$(this).change();
		});

	}

	$(window).load( function() {
		$('.pf_mod_masonry .prdctfltr_filter_inner').each( function() {
			$(this).isotope('layout');
		});
	});

	$(document).on('click', '.prdctfltr_sc_products '+prdctfltr.ajax_class+' '+prdctfltr.ajax_category_class+' a, .archive.woocommerce '+prdctfltr.ajax_class+' '+prdctfltr.ajax_category_class+' a', function() {

		var curr = $(this).closest(prdctfltr.ajax_category_class);

		var curr_sc = ( curr.closest('.prdctfltr_sc_products').length > 0 ? curr.closest('.prdctfltr_sc_products') : $('.prdctfltr_sc_products:first').length > 0 ? $('.prdctfltr_sc_products:first') : $('.prdctfltr_woocommerce:first').length > 0 ? $('.prdctfltr_woocommerce:first') : 'none' );

		if ( curr_sc == 'none' ) {
			return;
		}

		if ( curr_sc.hasClass('prdctfltr_sc_products') ) {
			var curr_filter = ( curr_sc.find('.prdctfltr_woocommerce').length > 0 ? curr_sc.find('.prdctfltr_woocommerce') : $('.prdctfltr-widget').find('.prdctfltr_woocommerce') );
		}
		else if ( $('.prdctfltr_sc_products').length == 0 ) {
			var curr_filter = curr_sc;
		}
		else {
			return;
		}

		var cat = curr.find('.prdctfltr_cat_support').data('slug');

		var hasFilter = curr_filter.find('.prdctfltr_filter[data-filter="product_cat"] input[value="'+cat+'"]:first');

		if ( hasFilter.length > 0 ) {
			hasFilter.trigger('click');
			if ( !curr_filter.hasClass('prdctfltr_click_filter') ) {
				curr_filter.find('a.prdctfltr_woocommerce_filter_submit').trigger('click');
			}
		}
		else {
			var hasField = curr_filter.find('.prdctfltr_filter[data-filter="product_cat"]');

			if ( hasField.length > 0 ) {
				hasField.find('input[name="product_cat"]').val(cat);
			}
			else {
				var append = $('<input name="product_cat" type="hidden" value="'+cat+'" />');
				curr_filter.find('.prdctfltr_add_inputs').append(append);
			}

			if ( !curr_filter.hasClass('prdctfltr_click_filter') ) {
				curr_filter.find('a.prdctfltr_woocommerce_filter_submit').trigger('click');
			}
			else {
				prdctfltr_respond(curr_filter);
			}
		}

		return false;

	});

})(jQuery);