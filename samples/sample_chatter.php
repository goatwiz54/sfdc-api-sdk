<?php

date_default_timezone_set("Asia/Tokyo");

require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = new \Dotenv\Dotenv(__DIR__ . "/sfdc-api-sdk/");
$dotenv->load();

try {
    $baseUrl = getenv("SFDC_API_SDK_BASE_URL");
    $version = "46.0";
    $httpClient = new \GuzzleHttp\Client();
    $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClient, $baseUrl);
    $authProvider->authorization([
        "grant_type"    => "password",
        "username"      => getenv("SFDC_API_SDK_USERNAME"),
        "password"      => getenv("SFDC_API_SDK_PASSWORD"),
        "client_id"     => getenv("SFDC_API_SDK_CLIENT_ID"),
        "client_secret" => getenv("SFDC_API_SDK_CLIENT_SECRET"),
    ]);

    $client = new \SfdcApiSdk\Chatter\SfdcChatterApiClient($authProvider, $version);

    $feedElementId = $client->feed("0F9p000000019hhCAA", "フィード投稿");
    $client->comment($feedElementId, "コメント投稿");
} catch (\SfdcApiSdk\Exception\SfdcRestApiException $e) {
    echo((string)$e . PHP_EOL . $e->getPrevious()->getResponse(true)->getBody());
} catch (\SfdcApiSdk\Exception\AuthenticationException $e) {
    echo((string)$e . PHP_EOL . $e->getPrevious()->getResponse(true)->getBody());
}
