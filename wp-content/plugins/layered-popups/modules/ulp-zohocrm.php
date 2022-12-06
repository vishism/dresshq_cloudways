<?php
/* Zoho CRM integration for Layered Popups */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class ulp_zohocrm_class {
	var $default_popup_options = array(
		"zohocrm_enable" => "off",
		"zohocrm_api_key" => "",
		"zohocrm_domain" => "zoho.eu",
		"zohocrm_fields" => array('{subscription-email}', 'My Company', '{subscription-name}'),
		"zohocrm_fieldnames" => array('Email', 'Company', 'Last Name')
	);
	function __construct() {
		if (is_admin()) {
			add_action('ulp_popup_options_integration_show', array(&$this, 'popup_options_show'));
			add_filter('ulp_popup_options_check', array(&$this, 'popup_options_check'), 10, 1);
			add_filter('ulp_popup_options_populate', array(&$this, 'popup_options_populate'), 10, 1);
			add_action('wp_ajax_ulp-zohocrm-fields', array(&$this, "show_fields"));
			add_filter('ulp_popup_options_tabs', array(&$this, 'popup_options_tabs'), 10, 1);
		}
		add_action('ulp_subscribe', array(&$this, 'subscribe'), 10, 2);
	}
	function popup_options_tabs($_tabs) {
		if (!array_key_exists("integration", $_tabs)) $_tabs["integration"] = __('Integration', 'ulp');
		return $_tabs;
	}
	function popup_options_show($_popup_options) {
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		echo '
				<h3>'.__('Zoho CRM Parameters', 'ulp').'</h3>
				<table class="ulp_useroptions">
					<tr>
						<th>'.__('Enable Zoho CRM', 'ulp').':</th>
						<td>
							<input type="checkbox" id="ulp_zohocrm_enable" name="ulp_zohocrm_enable" '.($popup_options['zohocrm_enable'] == "on" ? 'checked="checked"' : '').'"> '.__('Submit contact details to Zoho CRM (as Lead)', 'ulp').'
							<br /><em>'.__('Please tick checkbox if you want to submit contact details to Zoho CRM.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Zoho Domain', 'ulp').':</th>
						<td>
							<select id="ulp_zohocrm_domain" name="ulp_zohocrm_domain">
								<option value="zoho.com"'.($popup_options['zohocrm_domain'] == 'zoho.com' ? ' selected="selected"' : '').'>zoho.com</option>
								<option value="zoho.eu"'.($popup_options['zohocrm_domain'] == 'zoho.eu' ? ' selected="selected"' : '').'>zoho.eu</option>
							</select>
							<br /><em>'.__('Select your Zoho Domain.', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('API Key', 'ulp').':</th>
						<td>
							<input type="text" id="ulp_zohocrm_api_key" name="ulp_zohocrm_api_key" value="'.esc_html($popup_options['zohocrm_api_key']).'" class="widefat">
							<br /><em>'.__('Enter your Zoho Authentication Token. Please read how to <a href="https://www.zoho.eu/crm/help/api/using-authentication-token.html" target="_blank">generate Authentication Token</a> (use Browser Mode).', 'ulp').'</em>
						</td>
					</tr>
					<tr>
						<th>'.__('Fields', 'ulp').':</th>
						<td style="vertical-align: middle;">
							<div class="ulp-zohocrm-fields-html">';
		if (!empty($popup_options['zohocrm_api_key'])) {
			$fields = $this->get_fields_html($popup_options['zohocrm_api_key'], $popup_options['zohocrm_domain'], $popup_options['zohocrm_fields'], $popup_options['zohocrm_fieldnames']);
			echo $fields;
		}
		echo '
							</div>
							<a id="ulp_zohocrm_fields_button" class="ulp_button button-secondary" onclick="return ulp_zohocrm_loadfields();">'.__('Load Fields', 'ulp').'</a>
							<img class="ulp-loading" id="ulp-zohocrm-fields-loading" src="'.plugins_url('/images/loading.gif', dirname(__FILE__)).'">
							<br /><em>'.__('Click the button to (re)load fields list. Ignore if you do not need specify fields values.', 'ulp').'</em>
							<script>
								function ulp_zohocrm_loadfields() {
									jQuery("#ulp-zohocrm-fields-loading").fadeIn(350);
									jQuery(".ulp-zohocrm-fields-html").slideUp(350);
									var data = {action: "ulp-zohocrm-fields", ulp_key: jQuery("#ulp_zohocrm_api_key").val(), ulp_domain: jQuery("#ulp_zohocrm_domain").val()};
									jQuery.post("'.admin_url('admin-ajax.php').'", data, function(return_data) {
										jQuery("#ulp-zohocrm-fields-loading").fadeOut(350);
										try {
											var data = jQuery.parseJSON(return_data);
											var status = data.status;
											if (status == "OK") {
												jQuery(".ulp-zohocrm-fields-html").html(data.html);
												jQuery(".ulp-zohocrm-fields-html").slideDown(350);
											} else {
												jQuery(".ulp-zohocrm-fields-html").html("<div class=\'ulp-zohocrm-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Zoho CRM server.', 'ulp').'</strong></div>");
												jQuery(".ulp-zohocrm-fields-html").slideDown(350);
											}
										} catch(error) {
											jQuery(".ulp-zohocrm-fields-html").html("<div class=\'ulp-zohocrm-grouping\' style=\'margin-bottom: 10px;\'><strong>'.__('Internal error! Can not connect to Zoho CRM server.', 'ulp').'</strong></div>");
											jQuery(".ulp-zohocrm-fields-html").slideDown(350);
										}
									});
									return false;
								}
							</script>
						</td>
					</tr>
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
		if (isset($ulp->postdata["ulp_zohocrm_enable"])) $popup_options['zohocrm_enable'] = "on";
		else $popup_options['zohocrm_enable'] = "off";
		if ($popup_options['zohocrm_enable'] == 'on') {
			if (empty($popup_options['zohocrm_api_key'])) $errors[] = __('Invalid Zoho CRM API Key.', 'ulp');
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
		if (isset($ulp->postdata["ulp_zohocrm_enable"])) $popup_options['zohocrm_enable'] = "on";
		else $popup_options['zohocrm_enable'] = "off";
		
		$fields = array();
		$fieldnames = array();
		foreach($ulp->postdata as $key => $value) {
			if (substr($key, 0, strlen('ulp_zohocrm_field_')) == 'ulp_zohocrm_field_') {
				$field = substr($key, strlen('ulp_zohocrm_field_'));
				$fields[$field] = stripslashes(trim($value));
				$fieldnames[$field] = stripslashes(trim($ulp->postdata['ulp_zohocrm_fieldname_'.$field]));
			}
		}
		$popup_options['zohocrm_fields'] = $fields;
		$popup_options['zohocrm_fieldnames'] = $fieldnames;
		
		return array_merge($_popup_options, $popup_options);
	}
	function subscribe($_popup_options, $_subscriber) {
		if (empty($_subscriber['{subscription-email}'])) return;
		$popup_options = array_merge($this->default_popup_options, $_popup_options);
		if ($popup_options['zohocrm_enable'] == 'on') {
			$data = array('xmlData' => '', 'duplicateCheck' => 2);
			$data['xmlData'] = '<Leads><row no="1"><FL val="Email">'.esc_html($_subscriber['{subscription-email}']).'</FL>';
			if (!empty($popup_options['zohocrm_fields']) && is_array($popup_options['zohocrm_fields'])) {
				foreach ($popup_options['zohocrm_fields'] as $key => $value) {
					if (!empty($value)) {
						if ($popup_options['zohocrm_fieldnames'][$key] != 'Email') {
							$data['xmlData'] .= '<FL val="'.esc_html($popup_options['zohocrm_fieldnames'][$key]).'">'.esc_html(strtr($value, $_subscriber)).'</FL>';
						}
					}
				}
			}
			$data['xmlData'] .= '</row></Leads>';
			$result = $this->connect($popup_options['zohocrm_api_key'], $popup_options['zohocrm_domain'], 'json/Leads/insertRecords', $data);
		}
	}
	function show_fields() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (!isset($_POST['ulp_key']) || empty($_POST['ulp_key'])) {
				$return_object = array();
				$return_object['status'] = 'OK';
				$return_object['html'] = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.__('Invalid API Key.', 'ulp').'</strong></div>';
				echo json_encode($return_object);
				exit;
			}
			$key = trim(stripslashes($_POST['ulp_key']));
			$domain = trim(stripslashes($_POST['ulp_domain']));
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $this->get_fields_html($key, $domain, $this->default_popup_options['zohocrm_fields'], $this->default_popup_options['zohocrm_fieldnames']);
			echo json_encode($return_object);
		}
		exit;
	}
	function get_fields_html($_key, $_domain, $_fields, $_fieldnames) {
		function get_field_html($_idx, $_field, $_fields, $_fieldnames) {
			$html = '';
			if (!in_array($_field['type'], array('Lookup', 'OwnerLookup', 'Boolean'))) {
				$html .= '
				<tr>
					<td style="width: 100px;'.($_field['req'] == 'true' ? ' color: red;' : '').'"><strong>'.esc_html($_field['label']).':</strong></td>
					<td>';
				$selected_idx = array_search($_field['dv'], $_fieldnames);
				if (array_key_exists('val', $_field) && $_field['type'] == 'Pick List') {
					$html .= '
									<select id="ulp_zohocrm_field_'.$_idx.'" name="ulp_zohocrm_field_'.$_idx.'" class="widefat">';
					foreach ($_field['val'] as $val) {
						$html .= '
										<option value="'.esc_html($val).'"'.($selected_idx !== false && array_key_exists($selected_idx, $_fields) && $_fields[$selected_idx] == $val ? ' selected="selected"' : '').'>'.esc_html($val).'</option>';
					}
					$html .= '
									</select>';
				} else {
					$html .= '
						<input type="text" id="ulp_zohocrm_field_'.$_idx.'" name="ulp_zohocrm_field_'.$_idx.'" value="'.esc_html($selected_idx !== false && array_key_exists($selected_idx, $_fields) ? $_fields[$selected_idx] : '').'" class="widefat"'.($_field['dv'] == 'Email' ? ' readonly="readonly"' : '').' />';
				}
				$html .= '
						<input type="hidden" id="ulp_zohocrm_fieldname_'.$_idx.'" name="ulp_zohocrm_fieldname_'.$_idx.'" value="'.esc_html($_field['dv']).'" />
						<br /><em>'.esc_html($_field['label'].' ('.$_field['dv'].($_field['req'] == 'true' ? ', mandatory' : '').')').'</em>
					</td>
				</tr>';
			}
			return $html;
		}
		$result = $this->connect($_key, $_domain, 'json/Leads/getFields');
		$fields = '';
		$idx = 0;
		if (is_array($result) && array_key_exists('Leads', $result) && array_key_exists('section', $result['Leads'])) {
			if (sizeof($result['Leads']['section']) > 0) {
				$fields = '
			'.__('Please adjust the fields below. You can use the same shortcodes (<code>{subscription-email}</code>, <code>{subscription-name}</code>, etc.) to associate Zoho CRM fields with the popup fields.', 'ulp').'
			<table style="min-width: 280px; width: 50%;">';
				foreach ($result['Leads']['section'] as $section) {
					if (is_array($section)) {
						$fields .= '
				<tr><td colspan="2"><strong>'.esc_html($section['dv']).':</strong></td></tr>';
						if (array_key_exists('dv', $section['FL'])) {
							$fields .= get_field_html($idx, $section['FL'], $_fields, $_fieldnames);
							$idx++;
						} else {
							foreach ($section['FL'] as $field) {
								$fields .= get_field_html($idx, $field, $_fields, $_fieldnames);
								$idx++;
							}
						}
					}
				}
				$fields .= '
			</table>';
			} else {
				$fields = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.__('No fields found.', 'ulp').'</strong></div>';
			}
		} else if (is_array($result) && array_key_exists('response', $result) && array_key_exists('error', $result['response']) && array_key_exists('message', $result['response']['error'])) {
			$fields = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.$result['response']['error']['message'].'</strong></div>';
		} else {
			$fields = '<div class="ulp-zohocrm-grouping" style="margin-bottom: 10px;"><strong>'.__('Inavlid server response.', 'ulp').'</strong></div>';
		}
		return $fields;
	}
	function connect($_api_key, $_domain, $_path, $_data = array(), $_method = '') {
		try {
			if (!in_array($_domain, array('zoho.eu', 'zoho.com'))) $_domain = 'zoho.com';
			$url = 'https://crm.'.$_domain.'/crm/private/'.ltrim($_path, '/');
			if (!empty($_data)) $_data = array_merge($_data, array('authtoken' => $_api_key, 'scope' => 'crmapi'));
			else $url .= (strpos($url, '?') === false ? '?' : '&').'scope=crmapi&authtoken='.$_api_key;
			$curl = curl_init($url);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$ulp_zohocrm = new ulp_zohocrm_class();
?>