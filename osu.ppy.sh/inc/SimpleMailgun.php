<?php
/**
 * SimpleMailgun - An API wrapper for Mailgun that is only able to send messages.
 *
 * Mailgun API library, not bloated version.
 * This library wants to do one thing: send messages.
 * Nothing else will be implemented.
 *
 * @author kwisk <kwisk@airmail.cc>
 *
 * @version 1.0
 */
class SimpleMailgun {
	/**
	 * Array containing the configuration data for the mailgun api
	 * Only "domain" and "key" are required. Respectively mailgun domain and
	 * the secret API key for that domain.
	 *
	 * @var string
	 */
	public $config = ['domain' => '', 'key' => ''];

	/**
	 * __construct - Method constructor for SimpleMailgun.
	 *
	 * @param array $config Configuration array, documented in SimpleMailgun::$config
	 */
	public function __construct($config = null) {
		if (is_array($config)) {
			$this->config = $config;
		}
	}

	/**
	 * Send - Send a message through Mailgun.
	 *
	 * @param string $from    The sender.
	 * @param string $to      The email to which the email should be delivered to.
	 * @param string $subject The email subject.
	 * @param string $content The email content.
	 */
	public function Send($from, $to, $subject, $content) {
		$arr = ['from' => $from, 'to' => $to, 'subject' => $subject, 'html' => $content];
		$data = $this->CurlRequest('/messages', $arr);
		if (!$data[0]) {
			if ($data[1] == 'invalid key') {
				die('Slap off the website owner telling him that the mailgun api key is invalid.');
			}
		}
	}

	/**
	 * CurlRequest - Make a POST request to the Mailgun API.
	 *
	 * @param string $ApiEndpoint The endpoint of the API to reach (eg. "/messages")
	 *
	 * @return array An array. [0] is whether the function failed or succeded. [1] is either the contents of the curl error, or the contents of the requested webpage.
	 */
	private function CurlRequest($ApiEndpoint, $PostFields) {
		$url = 'https://api.mailgun.net/v3/'.$this->config['domain'].$ApiEndpoint;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);
		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// HTTP basic authorization for the mailgun API.
		// See https://documentation.mailgun.com/api-intro.html#authentication
		curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$this->config['key']);
		// POST data
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $PostFields);
		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		// Download the given URL, and return output
		$output = curl_exec($ch);
		if (curl_errno($ch)) {
			return [false, curl_error($ch)];
		}
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 401) {
			return [false, 'invalid key'];
		}
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			return [false, 'else'];
		}
		// Close the cURL resource, and free system resources
		curl_close($ch);

		return [true, $output];
	}
}
