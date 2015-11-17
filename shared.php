<?php

function buildHeader(array $items)
{
	$items = array_map(function ($a, $b) {
		return $a . ': ' . $b;
	}, array_keys($items), $items);
	return implode("\r\n", $items);
}

function getDataTrackerUrl()
{
	global $config;
	return $config['data-tracker-url'];
}

function getDataTracker($url)
{
	$url = getDataTrackerUrl() . $url;
	return json_decode(file_get_contents($url), true);
}

function postDataTracker($url, array $data)
{
	$url = getDataTrackerUrl() . $url;
	$content = json_encode($data);

	$context = stream_context_create([
		'http' => [
			'method'  => 'POST',
			'header'  => buildHeader([
				'Content-Type' => 'application/json;charset=UTF-8',
			]),
			'content' => $content,
		],
	]);

	return json_decode(file_get_contents($url, null, $context), true);
}

function getJson($url, array $headers = [])
{
	$contextData = [
		'http' => [
			'header' => buildHeader($headers),
		],
	];

	$context = stream_context_create($contextData);

	$result = file_get_contents($url, null, $context);
	$result = gzdecode($result);
	return json_decode($result, true);
}

function post($url, array $data = [], array $headers)
{
	$contextData = [
		'http' => [
			'method'  => 'POST',
			'header'  => buildHeader($headers + [
					'Content-Type' => 'application/x-www-form-urlencoded',
				]),
			'content' => http_build_query($data),
		],
	];

	$context = stream_context_create($contextData);

	return json_decode(file_get_contents($url, null, $context), true);
}
