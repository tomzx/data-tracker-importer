<?php

use GuzzleHttp\Exception\ClientException;

require_once __DIR__ . '/shared.php';

$config = require_once __DIR__ . '/fitbit-config.php';
$token = json_decode(file_get_contents(__DIR__ . '/fitbit.json'), true);

function fitbitGet($url)
{
	global $token;

	$headers = [
		'User-Agent'      => 'Dalvik/2.1.0 (Linux; U; Android 6.0; Nexus 5 Build/MRA58N)',
		'Accept-Encoding' => 'gzip',
		'Accept-Locale'   => 'en_US',
		'Authorization'   => 'Bearer '.$token['access_token'],
	];

//	$url = 'https://android-api.fitbit.com/1/user/-/' . $url;
	$url = 'https://api.fitbit.com/1/user/-/' . $url;

	try {
		$result = getJson($url, $headers);
	} catch (ClientException $exception) {
		$body = $exception->getResponse()->getBody();
		$body = json_decode($body, true);
		handleError($body);
	}


	if (handleError($result)) {
		$result = getJson($url, $headers);
	}

	return $result;
}

function handleError($result)
{
	global $token;

	if ($result === null || (isset($result['errors'][0]['errorType']) && $result['errors'][0]['errorType'] === 'invalid_token')) {
		$data = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $token['refresh_token'],
		];

		$headers = [
			'Authorization' => fitbitBasicAuthorization(),
		];

		$url = 'https://api.fitbit.com/oauth2/token';

		try {
			$response = post($url, $data, $headers);

			file_put_contents(__DIR__ . '/fitbit.json', $response->getBody());

			return true;
		} catch (ClientException $exception) {
			$body = $exception->getResponse()->getBody();
			$body = json_decode($body, true);
			var_dump($body);
			throw new RuntimeException('Could not refresh token.');
		}
	}
}

$now = new DateTime();

$key = $config['keys']['heart-pulse'];

// Ask data-tracker what is the last data point we've saved
$lastHeartRateDatapoint = getDataTracker('logs/' . $key . '?order=desc&limit=1&keyed=1');

$lastHeartRateDatapoint = $lastHeartRateDatapoint ? $lastHeartRateDatapoint[0][0] : null;
$startTime = clone $now;
$startTime = $lastHeartRateDatapoint ? $startTime->setTimestamp($lastHeartRateDatapoint) : $startTime->modify('-7 days');

// TODO: If the last point is 23:55, we will still get the whole day, which could be avoided <tom@tomrochette.com>
while ($now->getTimestamp() >= $startTime->getTimestamp()) {
	//	$startTime += 24*60*60;
//	$start = date('Y-m-d', $startTime);
	$start = $startTime->format('Y-m-d');
	$data = fitbitGet('activities/heart/date/' . $start . '/1d/5min.json');

	if (empty($data['activities-heart-intraday']['dataset'])) {
//		$startTime += 24 * 60 * 60;
		$startTime->modify('+1 day');
		continue;
	}

	$heartRateStats = [];
	$date = $data['activities-heart'][0]['dateTime'];
	foreach ($data['activities-heart-intraday']['dataset'] as $dayStat) {
		$timestamp = strtotime($date . ' ' . $dayStat['time']); // TODO: Consider timezone <tom@tomrochette.com>

		// Do not record data points we already know about
		if ($timestamp <= $lastHeartRateDatapoint) {
			continue;
		}

		$heartRateStats[$timestamp] = [
			'_timestamp' => $timestamp,
			$key         => $dayStat['value'],
		];
	}
	ksort($heartRateStats);

	// Insert datapoints in the dataTracker
	$out = postDataTracker('logs/bulk', array_values($heartRateStats));
	var_dump($out);
//	$startTime += 24 * 60 * 60;
	$startTime->modify('+1 day');
}

// TODO: Add steps <tom@tomrochette.com>
// TODO: Add sleep <tom@tomrochette.com>
