<?php

if (!Cache::has('installed')) file_get_contents(url('/install'));

Route::get('/', function() {
	if (empty($_ENV['BETARIGS_API_KEY']) ||
		empty($_ENV['COINBASE_API_KEY']) ||
		empty($_ENV['COINBASE_API_SECRET'])) {
		return View::make('error');
	}

	$rentals = Betarigs::get_rentals();
	if (!$rentals['success']) {
		return Redirect::to('error')->with('message', $rentals['error']);
	}

	return View::make('home', array(
		'algorithms' => Cache::get('algorithms'),
		'rentals' => $rentals['json']
	));
});

Route::get('error', function() {
	return View::make('error', array('message' => Session::get('message')));
});

Route::post('submit-rentals', function() {
	$rigs_to_rent = array();
	$hashing_power_needed = floatval(Input::get('hashing_power')) * 1000; // in Kh
	$page = 1;
	$next_page = true;
	while (true) {
		$rigs = Betarigs::rigs($page, intval(Input::get('algorithm')))['items'];
		if (empty($rigs)) break;

		foreach($rigs as $rig) {
			if ($rig['declared_speed']['unit'] == 'Kh/s' &&
				$rig['price']['per_speed_unit']['unit'] == 'BTC/Mh/day' && 
				floatval($rig['price']['per_speed_unit']['value']) <= floatval(Input::get('max_price')) &&
				($hashing_power_needed - intval($rig['declared_speed']['value'])) >= 0 &&
				array_reduce($rig['rental_durations'], function($carry, $item) {
					return $carry || ($item['value'] == Input::get('rental_duration') && $item['unit'] == 'hour');
				}, false))
			{
				$rigs_to_rent[] = array(
					'id' => $rig['id'], 
					'name' => $rig['name'],
					'desc' => $rig['description'],
					'speed_mhs' => intval($rig['declared_speed']['value']) / 1000,
					'price_mhd' => floatval($rig['price']['per_speed_unit']['value']),
					'price_day' => floatval($rig['price']['total']['value'])
				);

				$hashing_power_needed -= intval($rig['declared_speed']['value']);
				if ($hashing_power_needed <= 400) {
					$next_page = false;
					break;
				}
			}
		}
		if (!$next_page) break;
		else ++$page;
	}
	
	return json_encode($rigs_to_rent);
});

Route::post('do-rent', function() {
	$rigs = array_map('intval', explode(',', Input::get('rigs')));
	$hrs = intval(Input::get('hrs'));
	$errors = array();
	$coinbase = Coinbase::withApiKey($_ENV['COINBASE_API_KEY'], $_ENV['COINBASE_API_SECRET']);

	foreach($rigs as $rig) {
		$resp = Betarigs::rent($rig, $hrs);
		if (!$resp['success']) $errors[] = $resp['error'];
		else {
			$resp = $resp['json'];
			if ($resp['payment']['bitcoin']['price']['unit'] != 'BTC') {
				$errors[] = "Payment unit not in Bitcoin";
			}
			else {
				try {
					$response = $coinbase->sendMoney($resp['payment']['bitcoin']['payment_address'], $resp['payment']['bitcoin']['price']['value'], "Betarigs", ".0002");
					if (!$response->success) {
						$errors[] = "Coinbase payment not successful.";
					}
				}
				catch (Exception $e) {
					$errors[] = "Coinbase Error: " . $e->getMessage();
				}
			}
		}
	}

	return array('errors' => $errors);
});

Route::post('pool-settings', function() {
	Cache::forever('pool_url', Input::get('pool_url'));
	Cache::forever('pool_username', Input::get('pool_username'));
	Cache::forever('pool_password', Input::get('pool_password'));

	foreach(Betarigs::get_rentals()['json'] as $rental) {
		if (!in_array($rental['status'], array('executing', 'waiting_payment', 'waiting_payment_confirmation'))) {
			continue;
		}

		Betarigs::update_pool($rental['id'], $rental['rig']['id'], array(
			'url' => Cache::get('pool_url'), 'worker_name' => Cache::get('pool_username'), 'worker_password' => Cache::get('pool_password')
		));
	}

	return "";
});

Route::get('install', function() {
	Cache::forget('algorithms');

	Cache::rememberForever('algorithms', function() {
	    return Betarigs::algorithms();
	});

	Cache::forever('installed', '1');
});