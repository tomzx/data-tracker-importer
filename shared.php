<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

function getDataTrackerUrl()
{
	global $config;
	return $config['data-tracker-url'];
}

function getDataTracker($url)
{
	$url = getDataTrackerUrl() . $url;
	$client = new Client();
	$response = $client->request('GET', $url);
	$response = $response->getBody();
	return json_decode($response, true);
}

function postDataTracker($url, array $data)
{
	$content = json_encode($data);
	$options = [
		'headers' => [
			'Content-Type' => 'application/json;charset=UTF-8',
		],
		'body' => $content,
	];

	$url = getDataTrackerUrl() . $url;
	$client = new Client();
	$response = $client->request('POST', $url, $options);
	$response = $response->getBody();
	return json_decode($response, true);
}

function getJson($url, array $headers = [])
{
	$response = get($url, $headers);
	return json_decode($response, true);
}

function get($url, $headers = [])
{
	$options = [
		'headers' => $headers,
	];

	$client = new Client();
	$response = $client->request('GET', $url, $options);
	$content = $response->getBody()->getContents();
	return $content;
}

function post($url, array $data = [], array $headers)
{
	$options = [
		'headers' => $headers,
		'form_params' => $data,
	];

	$client = new Client();
	$response = $client->request('POST', $url, $options);
	$response = $response->getBody()->getContents();
	return json_decode($response, true);
}

function fitbitBasicAuthorization()
{
	global $config;
	return 'Basic '.base64_encode($config['oauth']['client_id'].':'.$config['oauth']['client_secret']);
}
