<?php

require_once __DIR__ . '/shared.php';

$config = require_once 'fitbit-config.php';

if (isset($_GET['code'])) {
	$code = $_GET['code'];

	$url = 'https://api.fitbit.com/oauth2/token?client_id=' . $config['oauth']['client_id'] . '&grant_type=authorization_code&redirect_uri=' . urlencode($config['oauth']['redirect_url']) . '&code=' . $code;

	$headers = [
		'Authorization' => fitbitBasicAuthorization(),
	];

	try {
		$result = post($url, [], $headers);
	} catch (Exception $e) {
		var_dump($e);
	}

	file_put_contents('fitbit.json', json_encode($result, JSON_PRETTY_PRINT));

	echo 'Token updated!';
	return;
}

$scopes = [
	'activity',
	'heartrate',
	'location',
	'nutrition',
	'profile',
	'settings',
	'sleep',
	'social',
	'weight',
];

$url = 'https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=' . $config['oauth']['client_id'] . '&redirect_uri=' . urlencode($config['oauth']['redirect_url']) . '&expires_in=2592000&scope=' . implode('%20', $scopes);

header('location: '.$url);
