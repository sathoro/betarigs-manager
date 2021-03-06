<?php

class Betarigs {

	protected static $root = "https://www.betarigs.com";
	
	protected static function request($uri, $filters = array(), $include_key = false, $type = "GET", $body = "") {
		$url = self::$root . $uri . (empty($filters) ? "" : "?" . http_build_query($filters));

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$headers = array();

		if ($type != "GET") {
			$headers[] = 'X-Api-Key: ' . $_ENV['BETARIGS_API_KEY'];
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
			if ($type == "PUT")
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
			else {
				$headers[] = 'Content-Type: application/json';
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		elseif ($include_key) {
			$headers[] = 'X-Api-Key: ' . $_ENV['BETARIGS_API_KEY'];
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$data = curl_exec($ch);
		$decoded = json_decode($data, true);

		if (empty(curl_error($ch))) {
			$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($status_code !== 200) {
				switch ($status_code) {
					case 403:
						$error = "Error: Your Betarigs API key is incorrect!";
						break;
					case 404:
						$error = "Error: That rig doesn't exist! (this can also occur if the minimum price is not met)";
						break;
					case 400:
						$error = "Error: {$decoded['error']['message']}";
						break;
					default:
						$error = "Unknown error occured with the Betarigs API, status code $status_code";
						break;
				}

				curl_close($ch);
				return array('success' => false, 'error' => $error);
			}
		}
		else {
			curl_close($ch);
			return array('success' => false, 'error' => "CURL error: {$resp['curl_error']}");
		}

		curl_close($ch);

		return is_array($decoded) ? array('success' => true, 'json' => $decoded) : array('success' => true, 'json' => array());
	}

	public static function get_rentals() {
		return self::request("/api/v1/rentals.json", array(), true);
	}

	public static function update_pool($rental_id, $rig_id, $pool_details) {
		$body = array('rig' => array('id' => $rig_id), 'pool' => $pool_details);
		return self::request("/api/v1/rental/$rental_id.json", array(), true, "PUT", $body);
	}

	public static function rent($rig_id, $duration) {
		$body = json_encode(array(
			'rig' => array('id' => $rig_id), 
			'duration' => array('value' => $duration, 'unit' => 'hour'),
			'pool' => array('url' => Cache::get('pool_url'), 'worker_name' => Cache::get('pool_username'), 'worker_password' => Cache::get('pool_password'))
		));

		return self::request("/api/v1/rental.json", array(), true, "POST", $body);
	}

	public static function rigs($page = 1, $algorithm = 1) {
		return self::request("/api/v1/rigs.json", compact('page', 'algorithm'))['json'];
	}

	public static function algorithms() {
		return self::request("/api/v1/algorithms.json")['json'];
	}
}
