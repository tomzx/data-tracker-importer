<?php

require_once __DIR__ . '/shared.php';

$config = require_once __DIR__ . '/jawbone-config.php';

function jawboneGet($url)
{
	global $config;
	$headers = $config['headers'] + [
		'x-nudge-request-id' => time() . sprintf('%04d', rand(0, 9999)),
		'User-Agent'         => 'NudgeOpenAndroid/4.9.4 Dalvik/2.1.0 (Linux; U; Android 6.0; Nexus 5 Build/MRA58N)',
		'Accept-Encoding'    => 'gzip',
		'accept-language'    => 'en',
		'Client-Timezone'    => 'America/Toronto',
	];

	$url = 'https://api-android.jawbone.com/nudge/api/v.1.55/users/@me/' . $url;
	return getJson($url, $headers);
}

$key = $config['keys']['heart-pulse'];

// Ask data-tracker what is the last data point we've saved
$lastHeartRateDatapoint = getDataTracker('logs/' . $key . '?order=desc&limit=1&keyed=1');

$start = $lastHeartRateDatapoint ? $lastHeartRateDatapoint[0][0] : time() - 7 * 60 * 60 * 24;
$end = time();
$data = jawboneGet('heartrates?start_time='.$start.'&end_time='.$end.'&limit=31');
//$data = json_decode(file_get_contents('jawbone-data.json'), true);
$heartRateStats = [];
foreach ($data['data']['items'] as $dayStat) {
	foreach ($dayStat['bg_move_day_hr_ticks'] as $stat) {
		if ($stat['time'] <= $start) {
			continue;
		}

		$heartRateStats[$stat['time']] = [
			'_timestamp' => $stat['time'],
			$key         => $stat['hr'],
		];
	}

	foreach ($dayStat['bg_sleep_day_hr_ticks'] as $stat) {
		if ($stat['time'] <= $start) {
			continue;
		}

		$heartRateStats[$stat['time']] = [
			'_timestamp' => $stat['time'],
			$key         => $stat['hr'],
		];
	}
}
ksort($heartRateStats);

// Insert datapoints in the dataTracker
$out = postDataTracker('logs/bulk', array_values($heartRateStats));
var_dump($out);

// TODO: Add steps <tom@tomrochette.com>
// TODO: Add sleep <tom@tomrochette.com>
