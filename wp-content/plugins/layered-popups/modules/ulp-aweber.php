<?php
/* AWeber integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
define('ULP_AWEBER_APPID', '0e193739');

class ulp_aweber_class {
	var $options = array(
		"aweber_consumer_key" => "",
		"aweber_consumer_secret" => "",
		"aweber_access_key" => "",
		"aweber_access_secret" => ""
	);
	var $default_popup_options = array(
		'aweber_enable' => "off",
		'aweber_listid' => "",
		'aweber_email' => '{subscription-email}',
		'aweber_name' => '{subscription-name}',
		'aweber_fields' => array(),
		'aweber_fieldnames' => array(),
		'aweber_tags' => '',
		'aweber_misc_notes' => '',
		'aweber_ad_tracking' => "layered-popups"
	);
	function __construct() {
		$this->get_options();
		if (is_admin()) {
			add_action('ulp_options_show', array(&$this, 'options_show'));
			add_action('wp_ajax_ulp_aweber_connect', array(&$this, "aweber_connect"));
			add_action('wp_ajax_ulp_aweber_disconnect', array(&$this, "aweber_disconnect"));
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
			add_action('wp_ajax_ulp-aweber-fields', array(&$this, "show_fields"));
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function get_options() {
		foreach ($this->options as $key => $value) {
			$this->options[$key] = get_option('ulp_'.$key, $this->options[$key]);
		}
	}
	function update_options() {
		if (current_user_can('manage_options')) {
			foreach ($this->options as $key => $value) {
				update_option('ulp_'.$key, $value);
			}
		}
	}
	function populate_options() {
		foreach ($this->options as $key => $value) {
			if (isset($_POST['ulp_'.$key])) {
				$this->options[$key] = trim(stripslashes($_POST['ulp_'.$key]));
			}
		}
	}
	function options_show() {
		echo '
			<h3>'.__('AWeber Connection', 'ulp').'</h3>';
		$account = null;
		if ($this->options['aweber_access_secret']) {
			if (!class_exists('AWeberAPI')) {
				require_once(dirname(dirname(__FILE__)).'/aweber_api/aweber_api.php');
			}
			try {
				$aweber = new AWeberAPI($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret']);
				$account = $aweber->getAccount($this->options['aweber_access_key'], $this->options['aweber_access_secret']);
			} catch (AWeberException $e) {
				$account = null;
			}
		}
		if (!$account) {
			echo '
			<div id="ulp-aweber-connection">
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Authorization code', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_oauth_id" value="" class="widefat" placeholder="AWeber authorization code">
							<br /><em>Get your authorization code <a target="_blank" href="https://auth.aweber.com/1.0/oauth/authorize_app/'.ULP_AWEBER_APPID.'">'.__('here', 'ulp').'</a></em>.
						</td>
					</tr>
					<tr>
						<th></th>
						<td style="vertical-align: middle;">
							<input type="button" class="ulp_button button-secondary" value="'.__('Make Connection', 'ulp').'" onclick="return ulp_aweber_connect();" >
							<img id="ulp-aweber-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
						</td>
					</tr>
				</table>
			</div>';
		} else {
			echo '
			<div id="ulp-aweber-connection">
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connected', 'ulp').':</th>
						<td>
							<input type="button" class="ulp_button button-secondary" value="'.__('Disconnect', 'ulp').'" onclick="return ulp_aweber_disconnect();" >
							<img id="ulp-aweber-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to disconnect.', 'ulp').'</em>
						</td>
					</tr>
				</table>
			</div>';
		}
		echo '
			<script>
				function ulp_aweber_connect() {
					jQuery("#ulp-aweber-loading").fadeIn(350);
					jQuery(".ulp-popup-form").find(".ulp-message").slideUp(350);
					var data = {action: "ulp_aweber_connect", ulp_aweber_oauth_id: jQuery("#ulp_aweber_oauth_id").val()};
					jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
						jQuery("#ulp-aweber-loading").fadeOut(350);
						try {
							//alert(return_data);
							var data = jQuery.parseJSON(return_data);
							var status = data.status;
							if (status == "OK") {
								jQuery("#ulp-aweber-connection").slideUp(350, function() {
									jQuery("#ulp-aweber-connection").html(data.html);
									jQuery("#ulp-aweber-connection").slideDown(350);
								});
							} else if (status == "ERROR") {
								jQuery(".ulp-popup-form").find(".ulp-message").html(data.message);
								jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
							} else {
								jQuery(".ulp-popup-form").find(".ulp-message").html("Service is not available.");
								jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
							}
						} catch(error) {
							jQuery(".ulp-popup-form").find(".ulp-message").html("Service is not available.");
							jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
						}
					});
					return false;
				}
				function ulp_aweber_disconnect() {
					jQuery("#ulp-aweber-loading").fadeIn(350);
					var data = {action: "ulp_aweber_disconnect"};
					jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
						jQuery("#ulp-aweber-loading").fadeOut(350);
						try {
							//alert(return_data);
							var data = jQuery.parseJSON(return_data);
							var status = data.status;
							if (status == "OK") {
								jQuery("#ulp-aweber-connection").slideUp(350, function() {
									jQuery("#ulp-aweber-connection").html(data.html);
									jQuery("#ulp-aweber-connection").slideDown(350);
								});
							} else if (status == "ERROR") {
								jQuery(".ulp-popup-form").find(".ulp-message").html(data.message);
								jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
							} else {
								jQuery(".ulp-popup-form").find(".ulp-message").html("Service is not available.");
								jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
							}
						} catch(error) {
							jQuery(".ulp-popup-form").find(".ulp-message").html("Service is not available.");
							jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
						}
					});
					return false;
				}
			</script>';
	}
	function aweber_connect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_aweber_oauth_id']) || empty($_POST['ulp_aweber_oauth_id'])) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = __('Authorization Code not found.', 'ulp');
				echo json_encode($return_object);
				exit;
			}
			$code = trim(stripslashes($_POST['ulp_aweber_oauth_id']));
			if (!class_exists('AWeberAPI')) {
				require_once(dirname(dirname(__FILE__)).'/aweber_api/aweber_api.php');
			}
			$account = null;
			try {
				list($consumer_key, $consumer_secret, $access_key, $access_secret) = AWeberAPI::getDataFromAweberID($code);
			} catch (AWeberAPIException $exc) {
				list($consumer_key, $consumer_secret, $access_key, $access_secret) = null;
			} catch (AWeberOAuthDataMissing $exc) {
				list($consumer_key, $consumer_secret, $access_key, $access_secret) = null;
			} catch (AWeberException $exc) {
				list($consumer_key, $consumer_secret, $access_key, $access_secret) = null;
			}
			if (!$access_secret) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = __('Invalid Authorization Code!', 'ulp');
				echo json_encode($return_object);
				exit;
			} else {
				try {
					$aweber = new AWeberAPI($consumer_key, $consumer_secret);
					$account = $aweber->getAccount($access_key, $access_secret);
				} catch (AWeberException $e) {
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = __('Can not access AWeber account!', 'ulp');
					echo json_encode($return_object);
					exit;
				}
			}
			update_option('ulp_aweber_consumer_key', $consumer_key);
			update_option('ulp_aweber_consumer_secret', $consumer_secret);
			update_option('ulp_aweber_access_key', $access_key);
			update_option('ulp_aweber_access_secret', $access_secret);
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = '
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Connected', 'ulp').':</th>
						<td>
							<input type="button" class="ulp_button button-secondary" value="'.__('Disconnect', 'ulp').'" onclick="return ulp_aweber_disconnect();" >
							<img id="ulp-aweber-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to disconnect.', 'ulp').'</em>
						</td>
					</tr>
				</table>';
			echo json_encode($return_object);
			exit;
		}
		exit;
	}
	function aweber_disconnect() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			update_option('ulp_aweber_consumer_key', "");
			update_option('ulp_aweber_consumer_secret', "");
			update_option('ulp_aweber_access_key', "");
			update_option('ulp_aweber_access_secret', "");
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = '
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Authorization code', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_oauth_id" value="" class="widefat" placeholder="AWeber authorization code">
							<br />Get your authorization code <a target="_blank" href="https://auth.aweber.com/1.0/oauth/authorize_app/'.ULP_AWEBER_APPID.'">'.__('here', 'ulp').'</a>.
						</td>
					</tr>
					<tr>
						<th></th>
						<td style="vertical-align: middle;">
							<input type="button" class="ulp_button button-secondary" value="'.__('Make Connection', 'ulp').'" onclick="return ulp_aweber_connect();" >
							<img id="ulp-aweber-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
						</td>
					</tr>
				</table>';
			echo json_encode($return_object);
			exit;
		}
		exit;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('AWeber Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">';
		$account = null;
		if ($this->options['aweber_access_secret']) {
			if (!class_exists('AWeberAPI')) {
				require_once(dirname(dirname(__FILE__)).'/aweber_api/aweber_api.php');
			}
			try {
				$aweber = new AWeberAPI($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret']);
				$account = $aweber->getAccount($this->options['aweber_access_key'], $this->options['aweber_access_secret']);
			} catch (AWeberException $e) {
				$account = null;
			}
		}
		if (!$account) {
			echo '
					<tr>
						<th>'.__('Enable AWeber', 'ulp').':</th>
						<td>'.__('Please connect your AWeber account on <a target="_blank" href="admin.php?page=ulp-settings">Settings</a> page.', 'ulp').'</td>
					</tr>';
		} else {
			$lists = $account->lists;
            if (empty($lists)) {
				echo '
					<tr>
						<th>'.__('Enable AWeber', 'ulp').':</th>
						<td>'.__('This AWeber account does not currently have any lists.', 'ulp').'</td>
					</tr>';
			} else {
				echo '
					<tr>
						<th>'.__('Enable AWeber', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_aweber_enable" name="ulp_aweber_enable" '.($popup_options['aweber_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to AWeber', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to AWeber.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('List ID', 'ulp').':</th>
						<td>
							<select id="ulp-aweber-listid" name="ulp_aweber_listid" class="ic_input_m">';
				foreach ($lists as $list) {
					echo '
								<option value="'.$list->id.'"'.($list->id == $popup_options['aweber_listid'] ? ' selected="selected"' : '').'>'.$list->name.'</option>';
				}
				echo '
							</select>
							<br /><em>'.__('Select your List ID.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate AWeber fields with the popup fields.', 'ulp').'
							<table style="min-width: 280px; width: 50%;">
								<tr>
									<td style="width: 100px;"><strong>'.__('Email', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_aweber_email" name="ulp_aweber_email" value="{subscription-email}" class="widefat" readonly="readonly" />
										<br /><em>'.__('Email address of the contact.', 'ulp').'</em>
									</td>
								</tr>
								<tr>
									<td><strong>'.__('Name', 'ulp').':</strong></td>
									<td>
										<input type="text" id="ulp_aweber_name" name="ulp_aweber_name" value="'.esc_html($popup_options['aweber_name']).'" class="widefat" />
										<br /><em>'.__('Name of the contact.', 'ulp').'</em>
									</td>
								</tr>
							</table>
							<div class="ulp-aweber-fields-html">';
		if (!empty($popup_options['aweber_listid'])) {
			$fields = $this->get_fields_html($popup_options['aweber_listid'], $popup_options['aweber_fields']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_aweber_fields_button" class="ulp_button button-secondary" onclick="return ulp_aweber_loadfields();">'.__('Load Custom Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-aweber-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify custom fields values.', 'ulp').'</em>
							<script>
								function ulp_aweber_loadfields() {
									jQuery("#ulp-aweber-fields-loading").fadeIn(350);
									jQuery(".ulp-aweber-fields-html").slideUp(350);
									var data = {action: "ulp-aweber-fields", ulp_list: jQuery("#ulp-aweber-listid").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-aweber-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-aweber-fields-html").html(data.html);
												jQuery(".ulp-aweber-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-aweber-fields-html").html("<div class=\'ulp-aweber-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to AWeber server.', 'ulp').'</strong></div>");
												jQuery(".ulp-aweber-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-aweber-fields-html").html("<div class=\'ulp-aweber-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to AWeber server.', 'ulp').'</strong></div>");
											jQuery(".ulp-aweber-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<th>'.__('Tags', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_tags" name="ulp_aweber_tags" value="'.esc_html($popup_options['aweber_tags']).'" class="widefat">
							<br /><em>'.__('Enter comma-separated list of tags applied to the contact.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Notes', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_misc_notes" name="ulp_aweber_misc_notes" value="'.esc_html($popup_options['aweber_misc_notes']).'" class="widefat">
							<br /><em>'.__('Enter notes applied to the contact (max 60 sybmols).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Ad Tracking', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_aweber_ad_tracking" name="ulp_aweber_ad_tracking" value="'.esc_html($popup_options['aweber_ad_tracking']).'" class="widefat">
							<br /><em>'.__('Enter your Ad Tracking info applied to the contact.', 'ulp').'</em>
						</td>
					</tr>';
			}
		}
		echo '
				</table>';
	}
	function popup_options_check($_errors) {
		global $ulp;
		$errors = array();
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_aweber_enable"])) $popup_options['aweber_enable'] = "on";
		else $popup_options['aweber_enable'] = "off";
		if ($popup_options['aweber_enable'] == 'on') {
			if (empty($popup_options['aweber_listid'])) $errors[] = __('Invalid AWeber List ID.', 'ulp');
		}
		return array_merge($_errors, $errors);
	}
	function popup_options_populate($_popup_options) {
		global $ulp;
		$popup_options = array();
		foreach ($this->default_popup_options as $key => $value) {
			if (isset($ulp->postdata['ulp_'.$key])) {
				$popup_options[$key] = stripslashes(trim($ulp->postdata['ulp_'.$key]));
			}
		}
		if (isset($ulp->postdata["ulp_aweber_enable"])) $popup_options['aweber_enable'] = "on";
		else $popup_options['aweber_enable'] = "off";
		
		$popup_options['aweber_fields'] = array();
		$popup_options['aweber_fieldnames'] = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_aweber_field_')) == 'ulp_aweber_field_') {
				$field = substr($key, strlen('ulp_aweber_field_'));
				$popup_options['aweber_fields'][$field] = stripslashes(trim($value));
				$popup_options['aweber_fieldnames'][$field] = stripslashes(trim($ulp->postdata['ulp_aweber_fieldname_'.$field]));
			}
		}
		$tags = explode(',', $popup_options['aweber_tags']);
		$ready_tags = array();
		foreach($tags as $tag) {
			$tag = trim($tag);
			if (strlen($tag) > 0) $ready_tags[] = $tag;
		}
		$popup_options['aweber_tags'] = implode(', ', $ready_tags);
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($this->options['aweber_access_secret']) {
			if ($popup_options['aweber_enable'] == 'on') {
				$account = null;
				if (!class_exists('AWeberAPI')) {
					require_once(dirname(dirname(__FILE__)).'/aweber_api/aweber_api.php');
				}
				try {
					$aweber = new AWeberAPI($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret']);
					$account = $aweber->getAccount($this->options['aweber_access_key'], $this->options['aweber_access_secret']);
					$subscribers = $account->loadFromUrl('/accounts/'.$account->id.'/lists/'.$popup_options['aweber_listid'].'/subscribers');
					$data = array(
						'email' => $_subscriber['{subscription-email}'],
						'ip_address' => $_SERVER['REMOTE_ADDR'],
						'name' => strtr($popup_options['aweber_name'], $_subscriber),
						'ad_tracking' => strtr($popup_options['aweber_ad_tracking'], $_subscriber),
						'last_followup_message_number_sent' => 0,
						'misc_notes' => strtr($popup_options['aweber_misc_notes'], $_subscriber)
					);
					if (!empty($popup_options['aweber_fields']) && is_array($popup_options['aweber_fields'])) {
						foreach ($popup_options['aweber_fields'] as $key => $value) {
							if (!empty($value)) {
								$data['custom_fields'][$popup_options['aweber_fieldnames'][$key]] = strtr($value, $_subscriber);
							}
						}
					}
					$tags = explode(',', $popup_options['aweber_tags']);
					foreach($tags as $tag) {
						$tag = trim($tag);
						if (strlen($tag) > 0) $data['tags'][] = $tag;
					}
					$subscribers->create($data);
				} catch (Exception $e) {
					$account = null;
				}
			}
		}
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_list']) || empty($_POST['ulp_list'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-aweber-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid List ID.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$list = trim(stripslashes($_POST['ulp_list']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($list, $this->default_popup_options['aweber_fields']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_list, $_fields) {
		$account = null;
		if ($this->options['aweber_access_secret']) {
			if (!class_exists('AWeberAPI')) {
				require_once(dirname(dirname(__FILE__)).'/aweber_api/aweber_api.php');
			}
			try {
				$aweber = new AWeberAPI($this->options['aweber_consumer_key'], $this->options['aweber_consumer_secret']);
				$account = $aweber->getAccount($this->options['aweber_access_key'], $this->options['aweber_access_secret']);
			} catch (AWeberException $e) {
				$account = null;
			}
		}
		if ($account) {
			$custom_fields = $account->loadFromUrl('/accounts/'.$account->id.'/lists/'.$_list.'/custom_fields');
			$fields = '';
			if (!empty($custom_fields) && is_object($custom_fields) && property_exists($custom_fields, 'data')) {
				if (sizeof($custom_fields->data['entries']) > 0) {
					$fields = '
				<table style="min-width: 280px; width: 50%;">';
					foreach ($custom_fields->data['entries'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('id', $field) && array_key_exists('name', $field)) {
								$fields .= '
					<tr>
						<td style="width: 100px;"><strong>'.esc_html($field['name']).':</strong></td>
						<td>
							<input type="text" id="ulp_aweber_field_'.esc_html($field['id']).'" name="ulp_aweber_field_'.esc_html($field['id']).'" value="'.esc_html(array_key_exists($field['id'], $_fields) ? $_fields[$field['id']] : '').'" class="widefat" />
							<input type="hidden" id="ulp_aweber_fieldname_'.esc_html($field['id']).'" name="ulp_aweber_fieldname_'.esc_html($field['id']).'" value="'.esc_html($field['name']).'" />
							<br /><em>'.esc_html($field['name']).'</em>
						</td>
					</tr>';
							}
						}
					}
					$fields .= '
				</table>';
				} else {
					$fields = '<div class="ulp-aweber-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
				}
			} else {
				$fields = '<div class="ulp-aweber-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
			}
		} else {
			$fields = '<div class="ulp-aweber-grouping" style="margin-bottom: 10px;"><strong>'.__('Please connect your AWeber account on <a target="_blank" href="admin.php?page=ulp-settings">Settings</a> page.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	
}
$ulp_aweber = new ulp_aweber_class();
?>