<?php

use Dotenv\Dotenv;
use GuzzleHttp\Client;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$subdomain= $_ENV['SUBDOMAIN'];
$clientId =$_ENV['CLIENT_ID'];
$clientSecret = $_ENV['CLIENT_SECRET'];
$code = $_ENV['CODE'];
$redirectUri = $_ENV['REDIRECT_URI'];

function loadTokens(): ?array
{
	$path = __DIR__ . '/../tokens.json';
	if(!file_exists($path)) return null;
	$json = file_get_contents($path);
	return json_decode($json, true);
}

function saveTokens(array $tokens)
{
	$path = __DIR__ . '/../tokens.json';
	file_put_contents($path, json_encode($tokens, JSON_PRETTY_PRINT));
}

function getTokens(
	$subdomain, 
	$clientId, 
	$clientSecret, 
	$redirectUri, 
	$code
) 
{
	$client = new Client();
	$url = "https://$subdomain.amocrm.ru/oauth2/access_token";

	$response = $client->post($url, [
		'json' => [
			'client_id' => $clientId,
			'client_secret' => $clientSecret,
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirectUri
		]
	]);

	return json_decode($response->getBody(), true);
}

function refreshAccessToken(
	$subdomain, 
	$clientId, 
	$clientSecret, 
	$refreshToken
) 
{
	$client = new Client();
	$url = "https://$subdomain.amocrm.ru/oauth2/access_token";

	$response = $client->post($url, [
		'json' => [
			'client_id'     => $clientId,
			'client_secret' => $clientSecret,
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refreshToken,
		]
	]);

    return json_decode($response->getBody(), true);
}

function createContact($accessToken, $subdomain) 
{
	try {
		$client = new Client();
		$url = "https://$subdomain.amocrm.ru/api/v4/contacts";

		$data = [
			[
				"first_name" => "Петр",
				"last_name" => "Смирнов",
				"name" => "Владимир Смирнов",
				"custom_fields_values" => [
					[
						"field_code" => "PHONE",
						"values" => [
							["value" => "+79998887766"]
						],
					],
					[
						"field_code" => "EMAIL",
						"values" => [
							["value" => "example@gmail.com"]
						]
					]
				]
			]
		];

		$response = $client->post($url, [
			'headers' => [
				'Authorization' => "Bearer {$accessToken}",
				'Content-Type' => 'application/json'
			],
			'json' => $data
		]);

		return json_decode($response->getBody(), true);

	} catch (Exception $e) {
		print_r($e->getMessage());
	}
}

function createDeal($accessToken, $subdomain)
{
	try {
		$client = new Client();
		$url = "https://$subdomain.amocrm.ru/api/v4/leads"; 
		
		$data = [
			[
				"name" => "Сделка номер  №" . date("H:i:s"),
				"price" => 20000,
			]
		];
		
		$response = $client->post($url, [
			'headers' =>[ 
				'Authorization' => "Bearer {$accessToken}",
				'Content-Type' => 'application/json'
			],
			'json' => $data
		]);

		return json_decode($response->getBody(), true);

	} catch (Exception $e) {
		print_r($e);
	}
}

$tokens = loadTokens();
// var_dump($tokens);
if (!$tokens) {
	$tokens = getTokens($subdomain, $clientId, $clientSecret, $redirectUri, $code);
	saveTokens($tokens);
} else {
	$refreshToken = $tokens['refresh_token'];
	$tokens = refreshAccessToken($subdomain, $clientId, $clientSecret, $refreshToken);
	saveTokens($tokens);
}

if (isset($tokens['access_token'])) {
	echo "Access token получен\n";
	$accessToken = $tokens['access_token'];

	$contactResponse = createContact($accessToken, $subdomain);
	echo "Создан контакт\n";

	$dealResponse = createDeal($accessToken, $subdomain);
	echo "Создана сделка\n";
}

?>