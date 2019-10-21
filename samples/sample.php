<?php

use SfdcApiSdk\Auth\OAuthProvider;
use SfdcApiSdk\Rest\SfdcRestApiClient;

date_default_timezone_set("Asia/Tokyo");

require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = new \Dotenv\Dotenv(__DIR__ . "/sfdc-api-sdk/");
$dotenv->load();

try {
    $client = new \GuzzleHttp\Client();

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

    $client = new SfdcApiSdk\Rest\SfdcRestApiClient($authProvider, $version);
    $time = date("YmdHis");
    $account = [
        "LastName"    => "test",
        "FirstName"   => "new",
        "PersonEmail" => "test@xxxxxxx.com",
    ];

    //新規作成
    $id = $client->create("Account", $account);
    var_dump($id);

    //作成したAccountを確認
    $result = $client->query(urlencode("select Id, PersonEmail, LastName, FirstName from Account where Id = '{$id}'"));
    var_dump("created:" . var_export($result, true));

    //作成したAccountを確認
    $result = $client->update("Account", $id, ["FirstName" => "update"]);

    //更新したAccountを確認
    $result = $client->query(urlencode("select Id, PersonEmail, LastName, FirstName from Account where Id = '{$id}'"));
    var_dump("updated:" . var_export($result, true));

    //削除
    $result = $client->delete("Account", $id);

    //削除したAccountを確認
    $result = $client->query(urlencode("select Id, PersonEmail, LastName, FirstName from Account where Id = '{$id}'"));
    var_dump("deleted:" . var_export($result, true));
} catch (\SfdcApiSdk\Exception\AuthenticationException $e) {
    $responseBody = $e->getResponse()->getBody(true);
    $json = json_decode(((string)$responseBody));
    var_dump("error=" . $e->getMessage());
}
