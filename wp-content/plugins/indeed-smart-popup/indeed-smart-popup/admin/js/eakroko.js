// FLAT Theme v2.0
(function( $ ){
	$.fn.retina = function(retina_part) {
		// Set default retina file part to '-2x'
		// Eg. some_image.jpg will become some_image-2x.jpg
		var settings = {'retina_part': '-2x'};
		if(retina_part) jQuery.extend(settings, { 'retina_part': retina_part });
		if(window.devicePixelRatio >= 2) {
			this.each(function(index, element) {
				if(!jQuery(element).attr('src')) return;

				var checkForRetina = new RegExp("(.+)("+settings['retina_part']+"\\.\\w{3,4})");
				if(checkForRetina.test(jQuery(element).attr('src'))) return;

				var new_image_src = jQuery(element).attr('src').replace(/(.+)(\.\w{3,4})$/, "$1"+ settings['retina_part'] +"$2");
				$.ajax({url: new_image_src, type: "HEAD", success: function() {
					jQuery(element).attr('src', new_image_src);
				}});
			});
		}
		return this;
	}
})( jQuery );
function icheck(){
	if(jQuery(".icheck-me").length > 0){
		jQuery(".icheck-me").each(function(){
			var $el = jQuery(this);
			var skin = ($el.attr('data-skin') !== undefined) ? "_"+$el.attr('data-skin') : "",
			color = ($el.attr('data-color') !== undefined) ? "-"+$el.attr('data-color') : "";

			var opt = {
				checkboxClass: 'icheckbox' + skin + color,
				radioClass: 'iradio' + skin + color,
				increaseArea: "10%"
			}

			$el.iCheck(opt);
		});
	}
}
jQuery(document).ready(function() {
	var mobile = false,
	tooltipOnlyForDesktop = true,
	notifyActivatedSelector = 'button-active';

	if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
		mobile = true;
	}

	icheck();

	if(jQuery(".complexify-me").length > 0){
		jQuery(".complexify-me").complexify(function(valid, complexity){
			if(complexity < 40){
				jQuery(this).parent().find(".progress .bar").removeClass("bar-green").addClass("bar-red");
			} else {
				jQuery(this).parent().find(".progress .bar").addClass("bar-green").removeClass("bar-red");
			}

			jQuery(this).parent().find(".progress .bar").width(Math.floor(complexity)+"%").html(Math.floor(complexity)+"%");
		});
	}

	// Round charts (easypie)
	if(jQuery(".chart").length > 0)
	{
		jQuery(".chart").each(function(){
			var color = "#881302",
			$el = jQuery(this);
			var trackColor = $el.attr("data-trackcolor");
			if($el.attr('data-color'))
			{
				color = $el.attr('data-color');
			}
			else
			{
				if(parseInt($el.attr("data-percent")) <= 25)
				{
					color = "#046114";
				}
				else if(parseInt($el.attr("data-percent")) > 25 && parseInt($el.attr("data-percent")) < 75)
				{
					color = "#dfc864";
				}
			}
			$el.easyPieChart({
				animate: 1000,
				barColor: color,
				lineWidth: 5,
				size: 80,
				lineCap: 'square',
				trackColor: trackColor
			});
		});
	}

	// Calendar (fullcalendar)
	if(jQuery('.calendar').length > 0)
	{
		jQuery('.calendar').fullCalendar({
			header: {
				left: '',
				center: 'prev,title,next',
				right: 'month,agendaWeek,agendaDay,today'
			},
			buttonText:{
				today:'Today'
			},
			editable: true
		});
		jQuery(".fc-button-effect").remove();
		jQuery(".fc-button-next .fc-button-content").html("<i class='icon-chevron-right'></i>");
		jQuery(".fc-button-prev .fc-button-content").html("<i class='icon-chevron-left'></i>");
		jQuery(".fc-button-today").addClass('fc-corner-right');
		jQuery(".fc-button-prev").addClass('fc-corner-left');
	}

	// Tooltips (only for desktop) (bootstrap tooltips)
	if(tooltipOnlyForDesktop)
	{
		if(!mobile)
		{
			jQuery('[rel=tooltip]').tooltip();
		}
	}
	

	// Notifications
	jQuery(".notify").click(function(){
		var $el = jQuery(this);
		var title = $el.attr('data-notify-title'),
		message = $el.attr('data-notify-message'),
		time = $el.attr('data-notify-time'),
		sticky = $el.attr('data-notify-sticky'),
		overlay = $el.attr('data-notify-overlay');

		$.gritter.add({
			title: 	(typeof title !== 'undefined') ? title : 'Message - Head',
			text: 	(typeof message !== 'undefined') ? message : 'Body',
			image: 	(typeof image !== 'undefined') ? image : null,
			sticky: (typeof sticky !== 'undefined') ? sticky : false,
			time: 	(typeof time !== 'undefined') ? time : 3000
		});
	});

	// masked input
	if(jQuery('.mask_date').length > 0){
		jQuery(".mask_date").mask("9999/99/99");	
	}
	if(jQuery('.mask_phone').length > 0){
		jQuery(".mask_phone").mask("(999) 999-9999");
	}
	if(jQuery('.mask_serialNumber').length > 0){
		jQuery(".mask_serialNumber").mask("9999-9999-99");	
	}
	if(jQuery('.mask_productNumber').length > 0){
		jQuery(".mask_productNumber").mask("aaa-9999-a");	
	}
	// tag-input
	if(jQuery(".tagsinput").length > 0){
		jQuery('.tagsinput').each(function(e){
			jQuery(this).tagsInput({width:'auto', height:'auto'});
		});
	}

	// datepicker
	if(jQuery('.datepick').length > 0){
		jQuery('.datepick').datepicker();
	}

	// daterangepicker
	if(jQuery('.daterangepick').length > 0){
		jQuery('.daterangepick').daterangepicker();
	}

	// timepicker
	if(jQuery('.timepick').length > 0){
		jQuery('.timepick').timepicker({
			defaultTime: 'current',
			minuteStep: 1,
			disableFocus: true,
			template: 'dropdown'
		});
	}
	// colorpicker
	if(jQuery('.colorpick').length > 0){
		jQuery('.colorpick').colorpicker();	
	}
	// uniform
	if(jQuery('.uniform-me').length > 0){
		jQuery('.uniform-me').uniform({
			radioClass : 'uni-radio',
			buttonClass : 'uni-button'
		});
	}
	// Chosen (chosen)
	if(jQuery('.chosen-select').length > 0)
	{
		jQuery('.chosen-select').each(function(){
			var $el = jQuery(this);
			var search = ($el.attr("data-nosearch") === "true") ? true : false,
			opt = {};
			if(search) opt.disable_search_threshold = 9999999;
			$el.chosen(opt);
		});
	}

	if(jQuery(".select2-me").length > 0){
		jQuery(".select2-me").select2();
	}

	// multi-select
	if(jQuery('.multiselect').length > 0)
	{
		jQuery(".multiselect").each(function(){
			var $el = jQuery(this);
			var selectableHeader = $el.attr('data-selectableheader'),
			selectionHeader  = $el.attr('data-selectionheader');
			if(selectableHeader != undefined)
			{
				selectableHeader = "<div class='multi-custom-header'>"+selectableHeader+"</div>";
			}
			if(selectionHeader != undefined)
			{
				selectionHeader = "<div class='multi-custom-header'>"+selectionHeader+"</div>";	
			}
			$el.multiSelect({
				selectionHeader : selectionHeader,
				selectableHeader : selectableHeader
			});
		});
	}

	// spinner
	if(jQuery('.spinner').length > 0){
		jQuery('.spinner').spinner();
	}

	// dynatree
	if(jQuery(".filetree").length > 0){
		jQuery(".filetree").each(function(){
			var $el = jQuery(this),
			opt = {};
			opt.debugLevel = 0;
			if($el.hasClass("filetree-callbacks")){
				opt.onActivate = function(node){
					jQuery(".activeFolder").text(node.data.title);
					jQuery(".additionalInformation").html("<ul style='margin-bottom:0;'><li>Key: "+node.data.key+"</li><li>is folder: "+node.data.isFolder+"</li></ul>");
				};
			}
			if($el.hasClass("filetree-checkboxes")){
				opt.checkbox = true;

				opt.onSelect = function(select, node){
					var selNodes = node.tree.getSelectedNodes();
					var selKeys = $.map(selNodes, function(node){
						return "[" + node.data.key + "]: '" + node.data.title + "'";
					});
					jQuery(".checkboxSelect").text(selKeys.join(", "));
				};
			}

			$el.dynatree(opt);
		});
	}

	if(jQuery(".colorbox-image").length > 0){
		jQuery(".colorbox-image").colorbox({
			maxWidth: "90%",
			maxHeight: "90%",
			rel: jQuery(this).attr("rel")
		});
	}

	// PlUpload
	if(jQuery('.plupload').length > 0){
		jQuery(".plupload").each(function(){
			var $el = jQuery(this);
			$el.pluploadQueue({
				runtimes : 'html5,gears,flash,silverlight,browserplus',
				url : 'js/plupload/upload.php',
				max_file_size : '10mb',
				chunk_size : '1mb',
				unique_names : true,
				resize : {width : 320, height : 240, quality : 90},
				filters : [
				{title : "Image files", extensions : "jpg,gif,png"},
				{title : "Zip files", extensions : "zip"}
				],
				flash_swf_url : 'js/plupload/plupload.flash.swf',
				silverlight_xap_url : 'js/plupload/plupload.silverlight.xap'
			});
			jQuery(".plupload_header").remove();
			var upload = $el.pluploadQueue();
			if($el.hasClass("pl-sidebar")){
				jQuery(".plupload_filelist_header,.plupload_progress_bar,.plupload_start").remove();
				jQuery(".plupload_droptext").html("<span>Drop files to upload</span>");
				jQuery(".plupload_progress").remove();
				jQuery(".plupload_add").text("Or click here...");
				upload.bind('FilesAdded', function(up, files) {
					setTimeout(function () { 
						up.start(); 
					}, 500);
				});
				upload.bind("QueueChanged", function(up){
					jQuery(".plupload_droptext").html("<span>Drop files to upload</span>");
				});
				upload.bind("StateChanged", function(up){
					jQuery(".plupload_upload_status").remove();
					jQuery(".plupload_buttons").show();
				});
			} else {
				jQuery(".plupload_progress_container").addClass("progress").addClass('progress-striped');
				jQuery(".plupload_progress_bar").addClass("bar");
				jQuery(".plupload_button").each(function(){
					if(jQuery(this).hasClass("plupload_add")){
						jQuery(this).attr("class", 'btn pl_add btn-primary').html("<i class='icon-plus-sign'></i> "+jQuery(this).html());
					} else {
						jQuery(this).attr("class", 'btn pl_start btn-success').html("<i class='icon-cloud-upload'></i> "+jQuery(this).html());
					}
				});
			}
		});
}

	// Wizard
	if(jQuery(".form-wizard").length > 0){
		jQuery(".form-wizard").formwizard({ 
			formPluginEnabled: true,
			validationEnabled: true,
			focusFirstInput : false,
			disableUIStyles:true,
			validationOptions: {
				errorElement:'span',
				errorClass: 'help-block error',
				errorPlacement:function(error, element){
					element.parents('.controls').append(error);
				},
				highlight: function(label) {
					jQuery(label).closest('.control-group').removeClass('error success').addClass('error');
				},
				success: function(label) {
					label.addClass('valid').closest('.control-group').removeClass('error success').addClass('success');
				}
			},
			formOptions :{
				success: function(data){
					alert("Response: \n\n"+data.say);
				},
				dataType: 'json',
				resetForm: true
			}	
		});
	}

	// Validation
	if(jQuery('.form-validate').length > 0)
	{
		jQuery('.form-validate').each(function(){
			var id = jQuery(this).attr('id');
			jQuery("#"+id).validate({
				errorElement:'span',
				errorClass: 'help-block error',
				errorPlacement:function(error, element){
					element.parents('.controls').append(error);
				},
				highlight: function(label) {
					jQuery(label).closest('.control-group').removeClass('error success').addClass('error');
				},
				success: function(label) {
					label.addClass('valid').closest('.control-group').removeClass('error success').addClass('success');
				}
			});
		});
	}

	// dataTables
	if(jQuery('.dataTable').length > 0){
		jQuery('.dataTable').each(function(){
			if(!jQuery(this).hasClass("dataTable-custom")) {
				var opt = {
					"sPaginationType": "full_numbers",
					"oLanguage":{
						"sSearch": "<span>Search:</span> ",
						"sInfo": "Showing <span>_START_</span> to <span>_END_</span> of <span>_TOTAL_</span> entries",
						"sLengthMenu": "_MENU_ <span>entries per page</span>"
					},
					'sDom': "lfrtip"
				};
				if(jQuery(this).hasClass("dataTable-noheader")){
					opt.bFilter = false;
					opt.bLengthChange = false;
				}
				if(jQuery(this).hasClass("dataTable-nofooter")){
					opt.bInfo = false;
					opt.bPaginate = false;
				}
				if(jQuery(this).hasClass("dataTable-nosort")){
					var column = jQuery(this).attr('data-nosort');
					column = column.split(',');
					for (var i = 0; i < column.length; i++) {
						column[i] = parseInt(column[i]);
					};
					opt.aoColumnDefs =  [{ 
						'bSortable': false, 
						'aTargets': column 
					}];
				}
				if(jQuery(this).hasClass("dataTable-scroll-x")){
					opt.sScrollX = "100%";
					opt.bScrollCollapse = true;
					jQuery(window).resize(function(){
						oTable.fnAdjustColumnSizing();
					});
				}
				if(jQuery(this).hasClass("dataTable-scroll-y")){
					opt.sScrollY = "300px";
					opt.bPaginate = false;
					opt.bScrollCollapse = true;
					jQuery(window).resize(function(){
						oTable.fnAdjustColumnSizing();
					});
				}
				if(jQuery(this).hasClass("dataTable-reorder")){
					opt.sDom = "R"+opt.sDom;
				}
				if(jQuery(this).hasClass("dataTable-colvis")){
					opt.sDom = "C"+opt.sDom;
					opt.oColVis = {
						"buttonText": "Change columns <i class='icon-angle-down'></i>"
					};
				}
				if(jQuery(this).hasClass('dataTable-tools')){
					opt.sDom= "T"+opt.sDom;
					opt.oTableTools = {
						"sSwfPath": "js/plugins/datatable/swf/copy_csv_xls_pdf.swf"
					};
				}
				if(jQuery(this).hasClass("dataTable-scroller")){
					opt.sScrollY = "300px";
					opt.bDeferRender = true;
					if(jQuery(this).hasClass("dataTable-tools")){
						opt.sDom = 'TfrtiS';
					} else {
						opt.sDom = 'frtiS';
					}
					opt.sAjaxSource = "js/plugins/datatable/demo.txt";
				}
				if(jQuery(this).hasClass("dataTable-grouping") && jQuery(this).attr("data-grouping") == "expandable"){
					opt.bLengthChange = false;
					opt.bPaginate = false;
				}

				var oTable = jQuery(this).dataTable(opt);
				jQuery(this).css("width", '100%');
				jQuery('.dataTables_filter input').attr("placeholder", "Search here...");
				jQuery(".dataTables_length select").wrap("<div class='input-mini'></div>").chosen({
					disable_search_threshold: 9999999
				});
				jQuery("#check_all").click(function(e){
					jQuery('input', oTable.fnGetNodes()).prop('checked',this.checked);
				});
				if(jQuery(this).hasClass("dataTable-fixedcolumn")){
					new FixedColumns( oTable );
				}
				if(jQuery(this).hasClass("dataTable-columnfilter")){
					oTable.columnFilter({
						"sPlaceHolder" : "head:after"
					});
				}
				if(jQuery(this).hasClass("dataTable-grouping")){
					var rowOpt = {};

					if(jQuery(this).attr("data-grouping") == 'expandable'){
						rowOpt.bExpandableGrouping = true;
					}
					oTable.rowGrouping(rowOpt);
				}

				oTable.fnDraw();
				oTable.fnAdjustColumnSizing();
			}
		});
}

	// force correct width for chosen
	resize_chosen();

	// file_management
	if(jQuery('.file-manager').length > 0)
	{
		jQuery('.file-manager').elfinder({
			url:'js/plugins/elfinder/php/connector.php'
		});
	}

	// slider
	if(jQuery('.slider').length > 0)
	{
		jQuery(".slider").each(function(){
			var $el = jQuery(this);
			var min = parseInt($el.attr('data-min')),
			max = parseInt($el.attr('data-max')),
			step = parseInt($el.attr('data-step')),
			range = $el.attr('data-range'),
			rangestart = parseInt($el.attr('data-rangestart')),
			rangestop = parseInt($el.attr('data-rangestop'));

			var opt = {
				min: min,
				max: max,
				step: step,
				slide: function( event, ui ) {
					$el.find('.amount').html( ui.value );
				}
			};

			if(range !== undefined)
			{
				opt.range = true;
				opt.values = [rangestart, rangestop];
				opt.slide = function( event, ui ) {
					$el.find('.amount').html( ui.values[0]+" - "+ui.values[1] );
					$el.find(".amount_min").html(ui.values[0]+"$");
					$el.find(".amount_max").html(ui.values[1]+"$");
				};
			}

			$el.slider(opt);
			if(range !== undefined){
				var val = $el.slider('values');
				$el.find('.amount').html(val[0] + ' - ' + val[1]);
				$el.find(".amount_min").html(val[0]+"$");
				$el.find(".amount_max").html(val[1]+"$");
			} else {
				$el.find('.amount').html($el.slider('value'));
			}
		});
}

if(jQuery(".ckeditor").length > 0){
	CKEDITOR.replace("ck");
}

jQuery(".retina-ready").retina("@2x");

});

jQuery(window).resize(function() {
	// chosen resize bug
	resize_chosen();
});

function resize_chosen(){
	jQuery('.chzn-container').each(function() {
		var $el = jQuery(this);
		$el.css('width', $el.parent().width()+'px');
		$el.find(".chzn-drop").css('width', ($el.parent().width()-2)+'px');
		$el.find(".chzn-search input").css('width', ($el.parent().width()-37)+'px');
	});
}


