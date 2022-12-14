(function($){
"use strict";

	var indexBefore = -1;

	function getIndex(itm, list) {
		var i;
		for (i = 0; i < list.length; i++) {
			if (itm[0] === list[i]) break;
		}
		return i >= list.length ? -1 : i;
	}

	function _trigger_color_picker(quickview) {
		quickview.find('.prdctfltr_st_color').each(function(){
			$(this).wpColorPicker({
				defaultColor: true,
				hide: true
			});
		});
	}

	function prdctfltr_init_settings() {
		if ( $('.wcpf_mode_presets').length > 0 ) {
			var curr = $('.wcpf_mode_presets').closest('form');

			curr.find('p span[class^=wcpfs_]').each( function() {
				var curr_el = $(this).parent();
				var curr_label = $(this).attr('class');

				var curr_label_stripped = curr_label.replace('wcpfs_', '');
				if ( $('.prdctfltr_customizer_fields').find('a.pf_active[data-filter="'+curr_label_stripped+'"]').length > 0 ) {

					$('.prdctfltr_customizer div[data-filter="'+curr_label_stripped+'"] .pf_options_holder').append(curr_el.prev().clone().wrap('<div>').parent().html());
						curr_el.prev().addClass(curr_label+'_init').hide();

					$('.prdctfltr_customizer div[data-filter="'+curr_label_stripped+'"] .pf_options_holder').append(curr_el.clone().wrap('<div>').parent().html());
						curr_el.addClass(curr_label+'_init').hide();

					$('.prdctfltr_customizer div[data-filter="'+curr_label_stripped+'"] .pf_options_holder').append(curr_el.next().clone().wrap('<div>').parent().html());
						curr_el.next().addClass(curr_label+'_init').hide();
						curr_el.next().find('select, input, textarea').prop('disabled', true);

				}
				else {
					curr_el.prev().addClass(curr_label+'_init').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
					curr_el.addClass(curr_label+'_init').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
					curr_el.next().addClass(curr_label+'_init').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
				}
			});

			curr.find('p span[class^=wcpff_]').each( function() {
				var curr_el = $(this).parent();
				var curr_label = $(this).attr('class');

				var curr_label_stripped = curr_label.replace('wcpff_', '');

				$('.prdctfltr_customizer_static div[data-filter="'+curr_label_stripped+'"] .pf_options_holder').append(curr_el.prev().clone().wrap('<div>').parent().html());
					curr_el.prev().addClass(curr_label+'_init').hide();

				$('.prdctfltr_customizer_static div[data-filter="'+curr_label_stripped+'"] .pf_options_holder').append(curr_el.clone().wrap('<div>').parent().html());
					curr_el.addClass(curr_label+'_init').hide();

				$('.prdctfltr_customizer_static div[data-filter="'+curr_label_stripped+'"] .pf_options_holder').append(curr_el.next().clone().wrap('<div>').parent().html());
					curr_el.next().addClass(curr_label+'_init').hide();
					curr_el.next().find('select, input, textarea').prop('disabled', true);

			});

		}
	}
	prdctfltr_init_settings();

	$('.prdctfltr_customizer').sortable({
		cursor:'move',
		handle: '.prdctfltr_c_move',
		start: function(event, ui) {
			indexBefore = getIndex(ui.item, $('.prdctfltr_customizer > div'));
		},
		stop: function(event, ui) {

			var count = $('.prdctfltr_customizer div.adv').length;

			if ( count > 0 ) {
				var i = 0;
				$('.prdctfltr_customizer div.adv').each( function() {
					var curr_el = $(this);
					var curr = curr_el.attr('data-id');

					curr_el.find('[name]').each( function() {
						var attr = $(this).attr('name');
						var id = $(this).attr('id').replace('_'+curr, '_'+i);
						$(this).attr('name', attr.replace('['+curr+']', '['+i+']'));
						$(this).attr('id', id);
						$(this).closest('tr').find('label').attr('for', id);
					});

					curr_el.attr('data-id', i);

					i++;
				});

			}

			var count = $('.prdctfltr_customizer div.rng').length;

			if ( count > 0 ) {
				var i = 0;
				$('.prdctfltr_customizer div.rng').each( function() {
					var curr_el = $(this);
					var curr = curr_el.attr('data-id');

					curr_el.find('[name]').each( function() {
						var attr = $(this).attr('name');
						var id = $(this).attr('id').replace('_'+curr, '_'+i);
						$(this).attr('name', attr.replace('['+curr+']', '['+i+']'));
						$(this).attr('id', id);
						$(this).closest('tr').find('label').attr('for', id);
					});

					curr_el.attr('data-id', i);

					i++;
				});

			}


			var indexAfter = getIndex(ui.item,$('.prdctfltr_customizer > div'));

			if (indexBefore==indexAfter) {
				return;
			} else {
				if (indexBefore<indexAfter) {
					$($('#wc_settings_prdctfltr_active_filters option')[indexBefore]).insertAfter($($('#wc_settings_prdctfltr_active_filters option')[indexAfter]));
				}
				else {
					$($('#wc_settings_prdctfltr_active_filters option')[indexBefore]).insertBefore($($('#wc_settings_prdctfltr_active_filters option')[indexAfter]));
				}
			}


		}
	});

	$(document).on('click', '.prdctfltr_c_visible', function() {
		var curr_el = $(this).parent();

		var curr_index = getIndex(curr_el, $('.prdctfltr_customizer > div'));

		if ( curr_el.find('.prdctfltr-eye').length > 0 ) {
			curr_el.find('.prdctfltr-eye').removeClass('prdctfltr-eye').addClass('prdctfltr-eye-disabled');
			$('#wc_settings_prdctfltr_active_filters option').eq(curr_index).prop("selected", false);
		}
		else {
			curr_el.find('.prdctfltr-eye-disabled').removeClass('prdctfltr-eye-disabled').addClass('prdctfltr-eye');
			$('#wc_settings_prdctfltr_active_filters option').eq(curr_index).prop("selected", true);
		}
		return false;
	});

	$(document).on('click', '.prdctfltr_c_delete', function() {

		var curr_el = $(this).parent();
		var curr_val =curr_el.attr('data-filter');

		if ( curr_el.attr('data-filter') == 'advanced' ) {

			if ( confirm(prdctfltr.localization.deactivate) === false ) {
				return;
			}

			var curr_index = getIndex(curr_el, $('.prdctfltr_customizer > div'));
			$('#wc_settings_prdctfltr_active_filters option').eq(curr_index).remove();
			curr_el.remove();

			var count = $('.prdctfltr_customizer div.adv').length;

			if ( count > 0 ) {
				var i = 0;
				$('.prdctfltr_customizer div.adv').each( function() {
					var curr_el = $(this);
					var curr = curr_el.attr('data-id');

					curr_el.find('[name]').each( function() {
						var attr = $(this).attr('name');
						var id = $(this).attr('id').replace('_'+curr, '_'+i);
						$(this).attr('name', attr.replace('['+curr+']', '['+i+']'));
						$(this).attr('id', id);
						$(this).closest('tr').find('label').attr('for', id);
					});

					curr_el.attr('data-id', i);

					i++;
				});

			}

		}
		else if ( curr_el.attr('data-filter') == 'range' ) {

			if ( confirm(prdctfltr.localization.deactivate) === false ) {
				return;
			}

			var curr_index = getIndex(curr_el, $('.prdctfltr_customizer > div'));
			$('#wc_settings_prdctfltr_active_filters option').eq(curr_index).remove();
			curr_el.remove();

			var count = $('.prdctfltr_customizer div.rng').length;

			if ( count > 0 ) {
				var i = 0;
				$('.prdctfltr_customizer div.rng').each( function() {
					var curr_el = $(this);
					var curr = curr_el.attr('data-id');

					curr_el.find('[name]').each( function() {
						var attr = $(this).attr('name');
						var id = $(this).attr('id').replace('_'+curr, '_'+i);
						$(this).attr('name', attr.replace('['+curr+']', '['+i+']'));
						$(this).attr('id', id);
						$(this).closest('tr').find('label').attr('for', id);
					});

					curr_el.attr('data-id', i);

					i++;
				});

			}

		}
		else {
			$('.prdctfltr_c_add_filter[data-filter="'+curr_val+'"]').trigger('click');
		}

		return false;
	});

	$(document).on('change', '.prdctfltr_adv_select', function() {

		var curr_el = $(this).closest('tr').next();
		var curr = curr_el.closest('.adv').attr('data-id');

		var curr_data = {
			action: 'prdctfltr_c_terms',
			taxonomy: $(this).find('option:selected').attr('value')
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				response = response.replace(/\%%/g, curr);
				curr_el.find('select').replaceWith(response);
			} else { 
				alert(prdctfltr.localization.ajax_error);
			}
		});

	});

	$(document).on('click', '.prdctfltr_c_add_filter', function() {
		var curr_el = $(this);
		var curr_val = curr_el.attr('data-filter');
		
		if ( curr_el.hasClass('pf_active') ) {

			if ( confirm(prdctfltr.localization.deactivate) === false ) {
				return;
			}

			$('#wc_settings_prdctfltr_active_filters option[value='+curr_val+']').remove();
			$('.prdctfltr_customizer div[data-filter='+curr_val+']').remove();

			var curr_form = $('.wcpf_mode_presets').closest('form');

			var curr_set = curr_form.find('p span[class=wcpfs_'+curr_val+']').parent();

			curr_set.prev().removeAttr('style').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
			curr_set.removeAttr('style').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
			curr_set.next().removeAttr('style').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
			curr_set.next().find('select, input, textarea').prop('disabled', false);

			curr_el.removeClass('pf_active').find('i').removeClass('prdctfltr-eye').addClass('prdctfltr-eye-disabled');

		}
		else {

			if ( confirm(prdctfltr.localization.activate) === false ) {
				return;
			}

			var add_filters = '';
			if ( curr_val == 'price' || curr_val == 'per_page' ) {
				add_filters = '<a href="#" class="prdctfltr_set_filters"><i class="prdctfltr-terms"></i></a>';
			}

			$('#wc_settings_prdctfltr_active_filters').append('<option value="'+curr_val+'" selected="selected">'+curr_el.find('span').text()+'</option>');
			$('.prdctfltr_customizer').append('<div class="pf_element" data-filter="'+curr_val+'"><span>'+curr_el.find('span').text()+'</span><a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a><a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a><a href="#" class="prdctfltr_c_toggle"><i class="prdctfltr-down"></i></a>'+add_filters+'<a href="#" class="prdctfltr_set_terms"><i class="prdctfltr-style"></i></a><div class="pf_options_holder"></div></div>');

			var curr_form = $('.wcpf_mode_presets').closest('form');

			var curr_set = curr_form.find('p span[class=wcpfs_'+curr_val+']').parent();

			curr_form.find('.wcpfs_'+curr_val+'_init').removeAttr('style');

			$('.prdctfltr_customizer div[data-filter="'+curr_val+'"] .pf_options_holder').append(curr_set.prev().clone().wrap('<div>').parent().html());
			curr_set.prev().hide();

			$('.prdctfltr_customizer div[data-filter="'+curr_val+'"] .pf_options_holder').append(curr_set.clone().wrap('<div>').parent().html());
			curr_set.hide();

			$('.prdctfltr_customizer div[data-filter="'+curr_val+'"] .pf_options_holder').append(curr_set.next().clone().wrap('<div>').parent().html());
			curr_set.next().hide();
			curr_set.next().find('select, input, textarea').prop('disabled', true);

			curr_el.addClass('pf_active').find('i').removeClass('prdctfltr-eye-disabled').addClass('prdctfltr-eye');

		}
		
		return false;
	});

	$(document).on('click', '.prdctfltr_c_add.pf_advanced', function() {

		var curr_el = $(this).parent().next();
		var curr = curr_el.find('.pf_element.adv').length;

		var curr_data = {
			action: 'prdctfltr_c_fields',
			pf_id: curr
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				response = response.split('%SPLIT%');

				var adv_ui = '<div class="pf_element adv" data-filter="advanced" data-id="'+response[0]+'"><span>'+prdctfltr.localization.adv_filter+'</span><a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a><a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a><a href="#" class="prdctfltr_c_toggle"><i class="prdctfltr-down"></i></a><a href="#" class="prdctfltr_set_terms"><i class="prdctfltr-style"></i></a><div class="pf_options_holder">'+response[1]+'</div></div>';
				$('#wc_settings_prdctfltr_active_filters').append('<option value="advanced" selected="selected">'+prdctfltr.localization.adv_filter+'</option>');

				var curr_append = curr_el.append(adv_ui);

			} else { 
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});


	function makeVals(formControl, controlType, value) {

		switch (controlType) {
			case 'text':
				$(formControl).val(value);
			break;
			case 'number':
				$(formControl).val(value);
			break;
			case 'textarea':
				$(formControl).val(value);
			break;
			case 'radio':
				$(formControl).val(value);
			break;
			case 'checkbox':
				$(formControl).prop('checked', ( value == 'yes' ? true : false ));
				break;
			case 'select':
				$(formControl).val(value);
				break;
			case 'multiselect':
				if ( value !== null ) {
					$(formControl).val(value);
				}
				break;
		}
		console.log($(formControl).val());
		return '';
	}


	function getVals(formControl, controlType) {

		switch (controlType) {
			case 'text':
				var value = $(formControl).val();
			break;
			case 'number':
				var value = $(formControl).val();
			break;
			case 'textarea':
				var value = $(formControl).val();
			break;
			case 'radio':
				var value = $(formControl).val();
			break;
			case 'checkbox':
				if ($(formControl).is(":checked")) {
					value = 'yes';
				}
				else {
					value = 'no';
				}
				break;
			case 'select':
				var value = $(formControl).val();
				break;
			case 'multiselect':
				var value = $(formControl).val() || [];
				break;
		}
		return value;
	}


	$(document).on('click', '.prdctfltr_or_add', function() {

		if ( confirm(prdctfltr.localization.add_override) === false ) {
			return false;
		};

		var curr = $(this).closest('p');

		var curr_data = {
			action: 'prdctfltr_or_add',
			curr_tax: curr.attr('class'),
			curr_term: curr.find('.prdctfltr_or_select').val(),
			curr_override: curr.find('.prdctfltr_filter_presets').val()
		};

		if ( curr_data.curr_term == undefined || curr_data.curr_override == 'default' ) {
			alert(prdctfltr.localization.override_notice);
			return;
		}

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				curr.prepend('<span class="prdctfltr_override"><input type="checkbox" class="pf_override_checkbox" /> '+prdctfltr.localization.term_slug+' : <span class="slug">'+curr.find('.prdctfltr_or_select').val()+'</span> '+prdctfltr.localization.filter_preset+' : <span class="preset">'+curr.find('.prdctfltr_filter_presets').val()+'</span> <a href="#" class="button prdctfltr_or_remove">'+prdctfltr.localization.remove_override_single+'</a><span class="clearfix"></span></span>')
				alert(prdctfltr.localization.added);
			} else { 
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});

	$(document).on('click', '.prdctfltr_or_remove', function() {

		if ( confirm(prdctfltr.localization.remove_override) === false ) {
			return false;
		};

		var curr = $(this).closest('p');
		var curr_remove = $(this).parent();

		var curr_data = {
			action: 'prdctfltr_or_remove',
			curr_tax: curr.attr('class'),
			curr_term: curr_remove.find('.slug').text()
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				curr_remove.remove();
				alert(prdctfltr.localization.removed);
			} else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});

	$(document).on('click', '#prdctfltr_save', function() {

		var curr_saving = {};

		var inputs = $('table:visible input[name^=wc_settings_prdctfltr], table:visible select[name^=wc_settings_prdctfltr], table:visible textarea[name^=wc_settings_prdctfltr]'), tmp;
		$.each(inputs, function(i, obj) {
			var tag = ( $(obj).prop('tagName') == 'INPUT' ? $(obj).attr('type') : $(obj).prop('tagName').toLowerCase() );
			curr_saving[$(obj).attr('name').replace('[]', '')] = getVals($(obj), tag);
		});

		if ( $('.pf_element.adv').length > 0 ) {
			var i = 0;

			curr_saving['wc_settings_prdctfltr_advanced_filters'] = {};
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_title'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_taxonomy'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_include'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_order'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_orderby'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_multiselect'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_relation'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_none'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_limit'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy_mode'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_mode'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_style'] = [];
			curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'] = [];

			$('.pf_element.adv').each( function() {
				var curr_el = $(this);
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_title'][i] = curr_el.find('input[name^="pfa_title"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_taxonomy'][i] = curr_el.find('select[name^="pfa_taxonomy"] option:selected').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_include'][i] = curr_el.find('select[name^="pfa_include"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_order'][i] = curr_el.find('select[name^="pfa _order"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_orderby'][i] = curr_el.find('select[name^="pfa _orderby"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_multiselect'][i] = ( curr_el.find('input[name^="pfa_multiselect"]:checked').length > 0 ? 'yes' : 'no' );
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_relation'][i] = curr_el.find('input[name^="pfa_relation"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'][i] = ( curr_el.find('input[name^="pfa_adoptive"]:checked').length > 0 ? 'yes' : 'no' );
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_none'][i] = ( curr_el.find('input[name^="pfa_none"]:checked').length > 0 ? 'yes' : 'no' );
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_limit'][i] = curr_el.find('input[name^="pfa_limit"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy'][i] = ( curr_el.find('input[name^="pfa_hierarchy"]:checked').length > 0 ? 'yes' : 'no' );
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy_mode'][i] = ( curr_el.find('input[name^="pfa_hierarchy_mode"]:checked').length > 0 ? 'yes' : 'no' );
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_mode'][i] = curr_el.find('input[name^="pfa_mode"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_style'][i] = curr_el.find('input[name^="pfa_style"]').val();
				curr_saving['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'][i] = curr_el.find('.pf_term_customization').val();
				i++;
			});

		}

		if ( $('.pf_element.rng').length > 0 ) {
			var m = 0;

			curr_saving['wc_settings_prdctfltr_range_filters'] = {};
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_title'] = [];
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'] = [];
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_include'] = [];
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_order'] = [];
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_orderby'] = [];
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_style'] = [];
			curr_saving['wc_settings_prdctfltr_range_filters']['pfr_grid'] = [];

			$('.pf_element.rng').each( function() {
				var curr_el = $(this);
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_title'][m] = curr_el.find('input[name^="pfr_title"]').val();
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][m] = curr_el.find('select[name^="pfr_taxonomy"] option:selected').val();
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_include'][m] = curr_el.find('select[name^="pfr_include"]').val();
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_order'][m] = curr_el.find('select[name^="pfr_order"]').val();
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_orderby'][m] = curr_el.find('select[name^="pfr_orderby"]').val();
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_style'][m] = curr_el.find('select[name^="pfr_style"] option:selected').val();
				curr_saving['wc_settings_prdctfltr_range_filters']['pfr_grid'][m] = ( curr_el.find('input[name^="pfr_grid"]:checked').length > 0 ? 'yes' : 'no' );
				m++;
			});

		}

		var curr_name = prompt('Enter template name to save it', $('select#prdctfltr_filter_presets option:selected:not([value="default"])').val() );

		if ( curr_name == '' || curr_saving == '' ) {
			alert(prdctfltr.localization.missing_settings);
			return false;
		}
		if ( curr_name === null ) {
			return false;
		}

		var curr_data = {
			action: 'prdctfltr_admin_save',
			curr_name: curr_name,
			curr_settings: JSON.stringify(curr_saving)
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				if ( $('select#prdctfltr_filter_presets option[value="'+curr_data.curr_name+'"]').length == 0) {
					$('select#prdctfltr_filter_presets').append('<option value="'+curr_data.curr_name+'">'+curr_data.curr_name+'</option>');
				}
				alert(prdctfltr.localization.saved);
			} else {
				alert(prdctfltr.localization.ajax_error);
			}
		});
		
		return false;

	});


	$(document).on('click', '#prdctfltr_load', function() {

		if ( confirm(prdctfltr.localization.load) === false ) {
			return false;
		};

		var curr_data = {
			action: 'prdctfltr_admin_load',
			curr_name: $('select#prdctfltr_filter_presets option:selected').val()
		};

		if ( curr_data.curr_name == '' || curr_data.curr_name == 'default' ) {
			alert(prdctfltr.localization.not_selected);
			return false;
		}

		var curr_form = $('.wcpf_mode_presets').closest('form');

		$('.prdctfltr_customizer').empty();

		curr_form.find('p span[class^=wcpfs_]').each( function() {
			var curr_el = $(this).parent();
			var curr_label = $(this).attr('class');

			curr_el.prev().removeClass(curr_label+'_init').removeAttr('style');
			curr_el.removeClass(curr_label+'_init').removeAttr('style');
			curr_el.next().removeClass(curr_label+'_init').removeAttr('style');
			curr_el.next().find('input, select, textarea').prop('disabled', false);
		});

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				var curr_preset = $.parseJSON(response);

				var inputs = $('table input[name^=wc_settings_prdctfltr], table select[name^=wc_settings_prdctfltr], table textarea[name^=wc_settings_prdctfltr]'), tmp;

				var count = inputs.length;

				$.each(inputs, function(i, obj) {
					var tag = ( $(obj).prop('tagName') == 'INPUT' ? $(obj).attr('type') : $(obj).prop('tagName').toLowerCase() );
					if ( tag == 'select' && $(obj).prop('multiple') == true ) {
						tag = 'multiselect';
					}

					var curr_val = curr_preset[$(obj).attr('name').replace('[]', '')];

					console.log( curr_val + ' > ' + $(obj).attr('name') + ' >TAG> ' + tag);
					if ( curr_val === undefined || curr_val === null ) {
						if ( tag == 'select' && $(obj).prop('multiple') == false ) {
							$(obj).find('option:first-child').attr('selected', 'selected').trigger('change');
						}
					}
					else {

						switch (tag) {
							case 'text':
								$(obj).attr('value', curr_val).trigger('change');
							break;
							case 'number':
								$(obj).attr('value', curr_val).trigger('change');
							break;
							case 'textarea':
								$(obj).html(curr_val);
							break;
							case 'radio':
								$(obj).attr('value', curr_val).trigger('change');
							break;
							case 'checkbox':
							console.log(curr_val);
								if (curr_val == 'yes') {
									$(obj).attr('checked', 'checked').attr('value', curr_val).trigger('change');
								}
								else {
									$(obj).removeAttr('checked').attr('value', curr_val).trigger('change');
								}
							break;
							case 'select':
								$(obj).val(curr_val).trigger('change');
							break;
							case 'multiselect':
								if ( curr_val !== null ) {
									$(obj).val(curr_val).trigger('change');
								}
							break;
						}

					}

					if ( !--count ) {

						if ( curr_preset['wc_settings_prdctfltr_active_filters'] !== undefined ) {
							var curr_el = $('.prdctfltr_customizer');
							var curr_flds = $('.prdctfltr_customizer_fields');

							curr_flds.find('a.prdctfltr_c_add_filter:not(.pf_advanced)').removeClass('pf_active');
							curr_flds.find('a.prdctfltr_c_add_filter:not(.pf_advanced) i').removeAttr('class').addClass('prdctfltr-eye-disabled');

							$('#wc_settings_prdctfltr_active_filters').empty();

							var curr=0,zurr=0;

							var curr_count = curr_preset['wc_settings_prdctfltr_active_filters'].length;

							$.each(curr_preset['wc_settings_prdctfltr_active_filters'], function(index, pf_filter) {

								$('#wc_settings_prdctfltr_active_filters').append('<option value="'+pf_filter+'" selected="selected">'+pf_filter+'</option>');

								if ( pf_filter == 'advanced' ) {

									var curr_data = {
										action: 'prdctfltr_c_fields',
										pfa_title: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_title'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_title'][curr] : '',
										pfa_taxonomy: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_taxonomy'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_taxonomy'][curr] : '',
										pfa_include: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_include'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_include'][curr] : [],
										pfa_order: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_order'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_order'][curr] : '',
										pfa_orderby: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_orderby'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_orderby'][curr] : 'DESC',
										pfa_multiselect: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_multiselect'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_multiselect'][curr] : 'no',
										pfa_relation: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_relation'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_relation'][curr] : 'and',
										pfa_adoptive: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'][curr] : 'no',
										pfa_none: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_none'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_none'][curr] : 'no',
										pfa_limit: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_limit'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_limit'][curr] : '',
										pfa_hierarchy: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy'][curr] : 'no',
										pfa_hierarchy_mode: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy_mode'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy_mode'][curr] : 'no',
										pfa_mode: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_mode'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_mode'][curr] : 'showall',
										pfa_style: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_style'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_style'][curr] : 'pf_attr_text',
										pfa_term_customization: curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'] !== undefined ? curr_preset['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'][curr] : '',
										pf_id: curr
									};

									var addIcons = '';
									if ( curr_data.pfa_term_customization !== '' ) {
										addIcons = '<a href="#" class="prdctfltr_set_terms pf_active"><i class="prdctfltr-style"></i></a><a href="#" class="prdctfltr_remove_terms">X</a>';
									}
									else {
										addIcons = '<a href="#" class="prdctfltr_set_terms pf_active"><i class="prdctfltr-style"></i></a>';
									}

									curr_el.append('<div class="pf_element adv" data-filter="'+pf_filter+'" data-id="'+curr+'"><span>'+prdctfltr.localization.adv_filter+'</span><a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a><a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a><a href="#" class="prdctfltr_c_toggle"><i class="prdctfltr-down"></i></a>'+addIcons+'<div class="pf_options_holder"></div></div>');

									$.post(prdctfltr.ajax, curr_data, function(response) {
										if (response) {
											response = response.split('%SPLIT%');
											curr_el.find('.pf_element.adv[data-id="'+response[0]+'"]').find('.pf_options_holder').append(response[1]);
										} else {
											alert(prdctfltr.localization.ajax_error);
										}
									});
									curr++;

								}
								else if ( pf_filter == 'range' ) {

									var curr_data = {
										action: 'prdctfltr_r_fields',
										pfr_title: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_title'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_title'][zurr] : '',
										pfr_taxonomy: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][zurr] : '',
										pfr_include: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_include'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_include'][zurr] : [],
										pfr_order: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_order'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_order'][zurr] : '',
										pfr_orderby: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_orderby'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_orderby'][zurr] : 'DESC',
										pfr_style: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_style'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_style'][zurr] : 'flat',
										pfr_grid: curr_preset['wc_settings_prdctfltr_range_filters']['pfr_grid'] !== undefined ? curr_preset['wc_settings_prdctfltr_range_filters']['pfr_grid'][zurr] : 'no',
										pf_id: zurr
									};

									curr_el.append('<div class="pf_element rng" data-filter="'+pf_filter+'" data-id="'+zurr+'"><span>'+prdctfltr.localization.rng_filter+'</span><a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a><a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a><a href="#" class="prdctfltr_c_toggle"><i class="prdctfltr-down"></i></a><div class="pf_options_holder"></div></div>');

									$.post(prdctfltr.ajax, curr_data, function(response) {
										if (response) {
											response = response.split('%SPLIT%');
											curr_el.find('.pf_element.rng[data-id="'+response[0]+'"]').find('.pf_options_holder').append(response[1]);
										} else {
											alert(prdctfltr.localization.ajax_error);
										}
									});
									zurr++;
								}
								else {

									curr_flds.find('a.prdctfltr_c_add_filter[data-filter="'+pf_filter+'"]').addClass('pf_active');
									curr_flds.find('a.prdctfltr_c_add_filter[data-filter="'+pf_filter+'"] i').removeAttr('class').addClass('prdctfltr-eye');

									curr_el.append('<div class="pf_element" data-filter="'+pf_filter+'"><span>'+curr_flds.find('a.prdctfltr_c_add_filter[data-filter="'+pf_filter+'"] span').text()+'</span><a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a><a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a><a href="#" class="prdctfltr_c_toggle"><i class="prdctfltr-down"></i></a><div class="pf_options_holder"></div></div>');

									var curr_set = curr_form.find('p span[class=wcpfs_'+pf_filter+']').parent();

									$('.prdctfltr_customizer div[data-filter="'+pf_filter+'"] .pf_options_holder').append(curr_set.prev().clone().wrap('<div>').parent().html());
									curr_set.prev().addClass('wcpfs_'+pf_filter+'_init').hide();

									$('.prdctfltr_customizer div[data-filter="'+pf_filter+'"] .pf_options_holder').append(curr_set.clone().wrap('<div>').parent().html());
									curr_set.addClass('wcpfs_'+pf_filter+'_init').hide();

									$('.prdctfltr_customizer div[data-filter="'+pf_filter+'"] .pf_options_holder').append(curr_set.next().clone().wrap('<div>').parent().html());
									curr_set.next().addClass('wcpfs_'+pf_filter+'_init').hide();
									curr_set.next().find('select, input, textarea').prop('disabled', true);

								}

								if ( !--curr_count ) {
									$('.prdctfltr_customizer_fields .prdctfltr_c_add_filter:not(.pf_active)').each( function() {
										var curr_filter = $(this).attr('data-filter');
										var curr_set = curr_form.find('p span[class=wcpfs_'+curr_filter+']').parent();

										curr_set.prev().addClass('wcpfs_'+curr_filter+'_init').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
										curr_set.addClass('wcpfs_'+curr_filter+'_init').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
										curr_set.next().addClass('wcpfs_'+curr_filter+'_init').css({'visibility':'hidden','position':'absolute','width':'0','height':'0','left':'-10000px','top':'-10000px'});
										curr_set.next().find('select, input, textarea').prop('disabled', false);

									});
								}

							});

						}

						pf_make_customizations('compact');
						alert(prdctfltr.localization.loaded);
					}
				});

			} else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});
	$(document).on('click', '#prdctfltr_delete', function() {

		if ( confirm(prdctfltr.localization.delete) === false ) {
			return false;
		};

		var curr_data = {
			action: 'prdctfltr_admin_delete',
			curr_name: $('select#prdctfltr_filter_presets option:selected').val()
		};

		if ( curr_data.curr_name == '' ) {
			alert(prdctfltr.localization.not_selected);
			return false;
		}

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				$('select#prdctfltr_filter_presets option[value="'+curr_data.curr_name+'"]').remove();
				alert(prdctfltr.localization.deleted);
			} else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});
	
	
	$('#wc_settings_prdctfltr_selected, #wc_settings_prdctfltr_attributes').closest('tr').hide();



	$(document).on('click', '.prdctfltr_c_add.pf_range', function() {

		var curr_el = $(this).parent().next();
		var curr = curr_el.find('.pf_element.rng').length;

		var curr_data = {
			action: 'prdctfltr_r_fields',
			pf_id: curr
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				response = response.split('%SPLIT%');

				var adv_ui = '<div class="pf_element rng" data-filter="range" data-id="'+response[0]+'"><span>'+prdctfltr.localization.rng_filter+'</span><a href="#" class="prdctfltr_c_delete"><i class="prdctfltr-delete"></i></a><a href="#" class="prdctfltr_c_move"><i class="prdctfltr-move"></i></a><a href="#" class="prdctfltr_c_toggle"><i class="prdctfltr-down"></i></a><div class="pf_options_holder">'+response[1]+'</div></div>';
				$('#wc_settings_prdctfltr_active_filters').append('<option value="range" selected="selected">'+prdctfltr.localization.rng_filter+'</option>');

				var curr_append = curr_el.append(adv_ui);

			} else { 
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});

	$(document).on('change', '.prdctfltr_rng_select', function() {

		var curr_el = $(this).closest('tr').next();
		var curr = curr_el.closest('.rng').attr('data-id');
		var curr_selected = $(this).find('option:selected').attr('value');

		var curr_data = {
			action: 'prdctfltr_r_terms',
			taxonomy: curr_selected
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {
				response = response.replace(/\%%/g, curr);
				curr_el.find('select').replaceWith(response);
				if ( curr_selected == 'price' ) {
					curr_el.next().find('select').prop( 'disabled', true );
					curr_el.next().next().find('select').prop( 'disabled', true );
				}
				else {
					curr_el.next().find('select').prop( 'disabled', false );
					curr_el.next().next().find('select').prop( 'disabled', false );
				}
			} else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

	});

	if ( $('#prdctfltr_save_default').length > 0 ) {
		$('input[name="save"]').before( '<a href="#" id="prdctfltr_save_bottom" class="button-primary">' + $('#prdctfltr_save').text() + '</a>' );
		$('input[name="save"]').val( $('#prdctfltr_save_default').text() );

		$('#prdctfltr_save_bottom').on( 'click', function() {
			$('#prdctfltr_save').trigger('click');
			return false;
		});
	}

	$(document).on('click', '.prdctfltr_or_remove_selected', function() {
		var curr = $(this).closest('p');

		curr.find('input.pf_override_checkbox:checked').each( function() {
			$(this).parent().find('.prdctfltr_or_remove').click();
		});

		return false;
	});

	$(document).on('click', '.prdctfltr_or_remove_all', function() {
		var curr = $(this).closest('p');

		curr.find('.prdctfltr_or_remove').click();

		return false;
	});

	$(document).on('click', '#prdctfltr_save_default', function() {
		var curr = $(this).closest('form');

		curr.submit();

		return false;
	});

	$(document).on('click', '#prdctfltr_reset_default', function() {
		window.location.href = window.location.href;

		return false;
	});

	$(document).on('click', '.prdctfltr_c_toggle', function() {
		var curr_holder = $(this).closest('.pf_element').find('.pf_options_holder');
		var curr_icon = $(this).find('i');

		if ( curr_icon.hasClass('prdctfltr-down') ) {
			curr_icon.removeClass('prdctfltr-down').addClass('prdctfltr-up');
			curr_holder.css({'max-height':'2500px','opacity':'1'});
		}
		else {
			curr_icon.removeClass('prdctfltr-up').addClass('prdctfltr-down');
			curr_holder.css({'max-height':'0','opacity':'0'});
		}

		return false;
	});

	function pf_make_customizations(mode) {
		$('.prdctfltr_customizer .pf_element').each(function() {

			var type = $(this).attr('data-filter');

			if ( mode == 'compact' && type == 'advanced' ) {
				return true;
			}

			if ( type == 'range' ) {
				return true;
			}

			var holder = $(this).find('.pf_options_holder');
			var addClass = '';

			if ( type == 'price' || type == 'per_page' ) {

				var hasFilters = $(this).find('input.pf_filter_customization').val();

				if ( typeof hasFilters != 'undefined' && hasFilters !== '' ) {
					var customizeFilters = $('<a href="#" class="prdctfltr_remove_filters">'+'X'+'</a>');
					addClass = ' pf_active';
				}

				var filters = $('<a href="#" class="prdctfltr_set_filters'+addClass+'"><i class="prdctfltr-terms"></i></a>');
				holder.before(filters);
				if ( typeof customizeFilters != 'undefined' && customize !== '' ) {
					holder.before(customizeFilters);
				}

			}

			var hasCusomization = $(this).find('.pf_term_customization').val();
			if ( typeof hasCusomization != 'undefined' && hasCusomization !== '' ) {
				var customize = $('<a href="#" class="prdctfltr_remove_terms">'+'X'+'</a>');
				addClass = ' pf_active';
			}

			var insert = $('<a href="#" class="prdctfltr_set_terms'+addClass+'"><i class="prdctfltr-style"></i></a>');
			holder.before(insert);
			if ( typeof customize != 'undefined' && customize !== '' ) {
				holder.before(customize);
			}

		});
	}

	$(document).ready(function() {
		pf_make_customizations('full');
	});

	$(document).on('click', 'a.prdctfltr_set_terms', function() {

		var curr = $(this).parent();
		var filter = curr.attr('data-filter');
		var key = curr.find('.pf_term_customization').val();
		var addKey = curr.find('.pf_filter_customization').val();
		var advanced = 0;

		if ( filter == 'advanced' ) {
			advanced = curr.find('.prdctfltr_adv_select').val();
		}

		var data = {
			action: 'prdctfltr_set_terms',
			filter: filter,
			advanced: advanced,
			key: key
		};

		if ( typeof addKey != 'undefined' && addKey !== '' ) {
			data.addkey = addKey;
		}

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				$('body').append($(response));
				curr.addClass('prdctfltr_editing');

				var quickview = $('.prdctfltr_quickview_terms');

				_trigger_color_picker(quickview);

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});

	$(document).on('click', '.prdctfltr_quickview_close', function() {
		$(this).parent().remove();
		$('.prdctfltr_editing').removeClass('prdctfltr_editing');
		return false;
	});

	$(document).on('change', '.prdctfltr_set_terms_attr_select', function() {

		var curr = $(this).closest('.prdctfltr_quickview_terms');
		var key = curr.attr('data-key');
		var addKey = curr.attr('data-addkey');

		var data = {
			action: 'prdctfltr_set_terms_new_style',
			filter: curr.find('.prdctfltr_set_terms').attr('data-taxonomy'),
			key: key,
			style: $('.prdctfltr_set_terms_attr_select[name="style"]').val()
		};

		if ( $('.prdctfltr_set_terms_attr_select[name="lang"]').length > 0 ) {
			data.lang = $('.prdctfltr_set_terms_attr_select[name="lang"]').val();
		}

		if ( typeof addKey != 'undefined' && addKey !== '' ) {
			data.addkey = addKey;
		}

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				var quickview = $('.prdctfltr_quickview_terms');

				quickview.find('.prdctfltr_quickview_terms_manager').html($(response));

				_trigger_color_picker(quickview);

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

	});

	$(document).on('click', '.prdctfltr_set_terms_save', function() {

		var settings = {};
		var curr = $(this).closest('.prdctfltr_quickview_terms');

		var inputs = $('.prdctfltr_quickview_terms_manager input, .prdctfltr_quickview_terms_manager textarea, .prdctfltr_quickview_terms_manager select, .prdctfltr_quickview_terms_settings select');
		$.each(inputs, function(i, obj) {
			var tag = ( $(obj).prop('tagName') == 'INPUT' ? $(obj).attr('type') : $(obj).prop('tagName').toLowerCase() );

			var optSet = getVals($(obj), tag);
			if ( optSet !== '' ) {
				settings[$(obj).attr('name')] = optSet;
			}

		});

		var key = curr.attr('data-key');

		var data = {
			action: 'prdctfltr_set_terms_save_style',
			key: key,
			settings: settings
		};

		if ( $('.prdctfltr_set_terms_attr_select[name="lang"]').length > 0 ) {
			data.lang = $('.prdctfltr_set_terms_attr_select[name="lang"]').val();
		}

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				var customize = $('<a href="#" class="prdctfltr_remove_terms">'+'X'+'</a>');

				$('.prdctfltr_editing input.pf_term_customization').val(key);
				if ( $('.prdctfltr_editing .prdctfltr_remove_terms').length == 0 ) {
					$('.prdctfltr_editing .prdctfltr_set_terms').after(customize);
				}
				//$('.prdctfltr_quickview_close').trigger('click');
				alert(prdctfltr.localization.customization_save);
			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});

	$(document).on( 'click', '.prdctfltr_st_upload_media', function () {

		var frame;
		var el = $(this);
		var curr = el.parent().prev();

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: el.data('choose'),
			button: {
				text: el.data('update'),
				close: false
			}
		});

		frame.on( 'select', function() {

			var attachment = frame.state().get('selection').first();
			frame.close();
			curr.find('input').val( attachment.attributes.url );

		});

		frame.open();

		return false;
	});

	$(document).on( 'click', '.prdctfltr_remove_terms', function () {

		if ( confirm(prdctfltr.localization.remove) === false ) {
			return false;
		};

		var el = $(this);
		var curr = el.parent();
		var settings = curr.find('.pf_term_customization').val();

		curr.find('.pf_term_customization').val('');
		el.remove();

		if ( confirm(prdctfltr.localization.remove_key) === false ) {
			return false;
		};

		var curr_data = {
			action: 'prdctfltr_set_terms_remove_style',
			settings: settings
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {

				alert(prdctfltr.localization.customization_removed);

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});

	$(document).on('click', '.prdctfltr_set_filters', function() {

		var curr = $(this).parent();
		var filter = curr.attr('data-filter');
		var key = curr.find('input.pf_filter_customization').val();

		var data = {
			action: 'prdctfltr_set_filters',
			filter: filter,
			key: key
		};

		if ( $('.prdctfltr_set_terms_attr_select[name="lang"]').length > 0 ) {
			data.lang = $('.prdctfltr_set_terms_attr_select[name="lang"]').val();
		}

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				$('body').append($(response));
				curr.addClass('prdctfltr_editing');

				$('.prdctfltr_quickview_filters_manager').sortable({
					cursor:'move'
				});

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});


	$(document).on('click', '.prdctfltr_c_move', function() {
		return false;
	});

	$(document).on('click', '.prdctfltr_filter_remove', function() {
		$(this).parent().remove();
		return false;
	});

	$(document).on('click', '.prdctfltr_set_filters_add', function() {

		var curr = $(this).parent();
		var filter = curr.find('.prdctfltr_set_filters_type').attr('data-filter');

		var data = {
			action: 'prdctfltr_set_filters_add',
			filter: filter
		};

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				curr.next().append($(response));

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});

	$(document).on('click', '.prdctfltr_set_filters_save', function() {

		var settings = {};
		var curr = $(this).closest('.prdctfltr_quickview_terms');
		var filter = curr.find('.prdctfltr_set_filters_type').attr('data-filter');

		var i = 0;
		$('.prdctfltr_quickview_filters_manager .prdctfltr_quickview_filter').each(function() {
			settings[i] = {};

			if ( $(this).find('.pf_min').length > 0 ) {
				settings[i]['min'] = $(this).find('input[name="pf_min"]').val();
				settings[i]['max'] = $(this).find('input[name="pf_max"]').val();
				settings[i]['text'] = $(this).find('textarea[name="pf_text"]').val();
			}
			else {
				settings[i]['value'] = $(this).find('input[name="pf_value"]').val();
				settings[i]['text'] = $(this).find('textarea[name="pf_text"]').val();
			}

			i++;

		});

		var key = curr.attr('data-key');

		var data = {
			action: 'prdctfltr_set_filters_save_style',
			key: key,
			filter: filter,
			settings: settings
		};

		if ( $('.prdctfltr_set_filters_attr_select[name="lang"]').length > 0 ) {
			data.lang = $('.prdctfltr_set_filters_attr_select[name="lang"]').val();
		}

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				$('.prdctfltr_editing input.pf_filter_customization').val(key);

				var customize = $('<a href="#" class="prdctfltr_remove_filters">'+'X'+'</a>');
				if ( $('.prdctfltr_editing .prdctfltr_remove_filters').length == 0 ) {
					$('.prdctfltr_editing .prdctfltr_set_filters').after(customize);
				}

				alert(prdctfltr.localization.customization_save);

				$('.prdctfltr_quickview_close').trigger('click');

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;

	});

	$(document).on('change', '.prdctfltr_set_filters_attr_select', function() {

		var curr = $(this).closest('.prdctfltr_quickview_terms');
		var filter = curr.find('.prdctfltr_set_filters_type').attr('data-filter');

		var key = curr.attr('data-key');

		var data = {
			action: 'prdctfltr_set_filters_new_style',
			filter: filter,
			key: key
		};

		if ( $('.prdctfltr_set_filters_attr_select[name="lang"]').length > 0 ) {
			data.lang = $('.prdctfltr_set_filters_attr_select[name="lang"]').val();
		}

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				var quickview = $('.prdctfltr_quickview_terms');

				quickview.find('.prdctfltr_quickview_filters_manager').html($(response));

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

	});

	$(document).on( 'click', '.prdctfltr_remove_filters', function () {

		if ( confirm(prdctfltr.localization.remove) === false ) {
			return false;
		};

		var el = $(this);
		var curr = el.parent();
		var settings = curr.find('.pf_filter_customization').val();

		curr.find('.pf_filter_customization').val('');
		el.remove();

		if ( confirm(prdctfltr.localization.remove_key) === false ) {
			return false;
		};

		var curr_data = {
			action: 'prdctfltr_set_filters_remove_style',
			settings: settings
		};

		$.post(prdctfltr.ajax, curr_data, function(response) {
			if (response) {

				alert(prdctfltr.localization.customization_remove);

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});

	$(document).on('click', '.prdctfltr_filtering_analytics_reset', function() {

		var data = {
			action: 'prdctfltr_analytics_reset'
		};

		$.post(prdctfltr.ajax, data, function(response) {
			if (response) {

				alert(prdctfltr.localization.delete_analytics);
				window.location.href = window.location.href;

			}
			else {
				alert(prdctfltr.localization.ajax_error);
			}
		});

		return false;
	});

	$('body').on('keyup change', 'input[name="wc_settings_prdctfltr_price_range"], input[name="wc_settings_prdctfltr_price_range_add"], input[name="pf_min"], input[name="pf_max"]', function(){
		var value = $(this).val();
		var regex = new RegExp( "[^\-0-9\%.\\" + prdctfltr.decimal_separator + "]+", "gi" );
		var newvalue = value.replace( regex, '' );

		if ( value !== newvalue ) {
			$(this).val( newvalue );
			alert(prdctfltr.localization.decimal_error);
		}
		return this;
	});


})(jQuery);