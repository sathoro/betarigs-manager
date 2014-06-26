<?php

class Betarigs {

	protected static $root = "https://www.betarigs.com";
	
	protected static function request($uri, $filters = array(), $include_key = false, $type = "GET", $body = "") {
		$url = self::$root . $uri . (empty($filters) ? "" : "?" . http_build_query($filters));

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if ($type != "GET") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-Api-Key: ' . $_ENV['BETARIGS_API_KEY']
			));
		}
		elseif ($include_key) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-Api-Key: ' . $_ENV['BETARIGS_API_KEY']
			));
		}

		$data = curl_exec($ch);

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
					default:
						$error = "Unknown error occured with the Betarigs API, status code {$resp['status_code']}";
						break;
				}

				return array('success' => false, 'error' => $error);
			}
		}
		else {
			return array('success' => false, 'error' => "CURL error: {$resp['curl_error']}");
		}

		curl_close($ch);

		$decoded = json_decode($data, true);

		return is_array($decoded) ? array('success' => true, 'json' => $decoded) : array('success' => true, 'json' => array());
	}

	public static function get_rentals() {
		return self::request("/api/v1/rentals.json", array(), true);
	}

	public static function update_pool($rental_id, $rig_id, $pool_details) {
		$body = json_encode(array('rig' => array('id' => $rig_id), 'pool' => $pool_details));
		return self::request("/api/v1/rental/$rental_id.json", array(), true, "PUT", $body);
	}

	public static function rent($rig_id, $duration) {
		$body = '{"rig":{"id":'.$rig_id.'},"duration":{"value":'.$duration.', "unit":"hour"}}';
		dd($body);
		return self::request("/api/v1/rental.json", array(), true, "POST", $body);
	}

	public static function rigs($page = 1, $algorithm = 1) {
		return self::request("/api/v1/rigs.json", compact('page', 'algorithm'))['json'];
	}

	public static function algorithms() {
		return self::request("/api/v1/algorithms.json")['json'];
	}
}