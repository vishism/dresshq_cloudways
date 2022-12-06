<?php
class AffinityEcommerceUtils {
	const SERVICE_MAX_TRIALS = 3;
	const SERVICE_SECONDS_BEFORE_TRYING_AGAIN = 5;
	const SERVICE_TIMEOUT = 600; //in seconds
	
	public static function getCallbackUrl() { 
		return admin_url('admin-ajax.php');
	}
	
	public static function getStoreUrl() { 
		return get_site_url();
	}
	
	public static function getAdminEmail() {
		return get_option('admin_email');
	}
	
	public static function isHttpsBeingUsed() {
		return is_ssl();
	}
	
	public static function redirectToAffinityAuthenticationPage() {
		$authUrl = admin_url('admin.php?page=ebay-sync-settings');
		wp_redirect($authUrl);
		exit();
	}
	
	public static function generateSecureRandomString() {
		if(function_exists('openssl_random_pseudo_bytes')) {
			return bin2hex(openssl_random_pseudo_bytes(16));
		}
		
		if (function_exists('random_bytes')) {
			$return = bin2hex(random_bytes(16));
		} else {
			set_time_limit(600);
			require_once(__DIR__ . "/../service/phpseclib/Crypt/Random.php");
			$return = bin2hex(crypt_random_string(16));
		}
		
		return $return;
	}
	
	public static function callMethodWithJsonContent($url, $arrParameters, $extraArgs = array()) {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		require_once(__DIR__ . "/../model/AffinityGlobalOptions.php");
		require_once(__DIR__ . "/../service/AffinityEnc.php");
		
		$method = $extraArgs['method'];
		
		if (empty($arrParameters)) {
			$arrParameters = array();
		}
		
		if (!class_exists('WP_Http')) {
			require_once(ABSPATH . WPINC. '/class-http.php');
		}
		
		$token = AffinityEnc::getToken();
		
		$defaultOpts = array(
			'method' => 'GET',
			'httpversion'  => "1.0",
			'headers'  => array(
				"Content-type" => "application/json",
				"Accept" => "application/json",
				"Authorization" => "Bearer " . $token,
				"X-EBAY-AFFINITY-PARTNER-ID" => AffinityGlobalOptions::getInstallationId(),
				"Expect" => ''
			),
			'timeout' => self::SERVICE_TIMEOUT,
			'sslverify' => false
//			'sslcertificates' => __DIR__ . "/ebay.crt" @Todo, make sure production services use ssl certificates
		);
		$options = array_merge($defaultOpts, $extraArgs);
		$finalUrl = $url;
		
		if ($method === "DELETE" && count($arrParameters) > 0) {
			$method = 'POST';
		}
		
		switch($method) {
			case "POST":
			case "PUT":
				unset($arrParameters['access_token']);
				$options['body'] = json_encode($arrParameters);
				break;
			default:
				$urlParameters = http_build_query($arrParameters);
				if(!empty($urlParameters)) {
					$finalUrl = $url . '?' . $urlParameters;
				}
		}
		
		$optionsWithoutHeaders = $options;
		unset($optionsWithoutHeaders['headers']);
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Sending HTTP Request", "URL: $finalUrl<br>Options: " . print_r($optionsWithoutHeaders, true));
		
		$requestCount = 1;
		$result = wp_remote_request($finalUrl, $options);
		
		while($requestCount < self::SERVICE_MAX_TRIALS && is_wp_error($result)) {
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "HTTP Call Failed - Trying again $requestCount", "No details");

			sleep(self::SERVICE_SECONDS_BEFORE_TRYING_AGAIN);
			$requestCount += 1;

			$result = wp_remote_request($finalUrl, $options);
		}
		
		if(is_wp_error($result)) {
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Connection to eBay Failed", "After trying to connect " . self::SERVICE_MAX_TRIALS . " times, we couldn't succeed and gave up. Please check your connection and WordPress site conectivity to Internet.");
			self::sendNotificationMail(self::getAdminEmail(), 'Connection to eBay Failed', "After trying to connect " . self::SERVICE_MAX_TRIALS . " times, we couldn't succeed and gave up. Please check your connection and WordPress site conectivity to Internet.");
			return false;
		}
		
		$return = array(
			'headers' => $result["headers"],
			'httpResponseCode' => $result["response"]["code"],
			'arrResult' => json_decode($result["body"], true)
		);
		
		if (is_array($return['headers'])) {
			$arr = $return['headers'];
		} else {
			$arr = $return['headers']->getAll();
		}
		
		if (!empty($arr['rlogid'])) {
			$return['rlogid'] = $arr['rlogid'];
		}
		unset($return['headers']);
		$returnWithoutHeaders = $return;
		unset($returnWithoutHeaders['headers']);
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Json Post Returning Result", "Return: " . print_r($returnWithoutHeaders, true));

		return $return;
	}
	
	public static function sendNotificationMail($to, $subject, $messageContent) {
		$messageWithStyledParagraphs = self::nl2p($messageContent, 'style="margin-top: 20px; margin-bottom: 20px; font-family: \'Helvetica Neue\', \'Open sans\', \'sans-serif\', \'Helvetica\'; color: #868686; font-size: 15px; line-height: 27px;"');
		
		$htmlTemplate = file_get_contents(__DIR__ . "/../includes/email-template.html");
		$htmlRenderedTemplate = str_replace("{{content}}", $messageWithStyledParagraphs, $htmlTemplate);
		
		wp_mail( $to, $subject, $htmlRenderedTemplate, array('Content-Type: text/html; charset=UTF-8') );
	}
	
	private static function nl2p($string, $paragraphExtraAttributes) {
		$return = '';

		foreach(explode("\n", $string) as $line) {
			if (trim($line)) {
				$return .= "<p $paragraphExtraAttributes>$line</p>";
			}
		}

		return $return;
	}
}
