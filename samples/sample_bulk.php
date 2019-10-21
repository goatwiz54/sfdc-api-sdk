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

    $client = new SfdcApiSdk\Bulk\SfdcBulkApiClient($authProvider, $version);
    $jobAll = [];

    $time = date("YmdHis");
    $accountList = [
        [
            "FirstName"   => "test-bulk-001-{$time}",
            "LastName"    => "1",
            "PersonEmail" => "sfdc-api-sdk.001.{$time}@xxxxxxxx.com",
        ],
        [
            "FirstName"   => "test-bulk-002-{$time}",
            "LastName"    => "2",
            "PersonEmail" => "sfdc-api-sdk.002.{$time}@xxxxxxxx.com",
        ],
    ];
    $batchJobInfo = $client->bulkUpsert("Account", $accountList);
    var_dump($batchJobInfo);
    $jobAll[] = new \SfdcApiSdk\Bulk\BulkStatusChecker($batchJobInfo);

    $accountList = [
        [
            "FirstName"   => "test-bulk-003-{$time}",
            "LastName"    => "3",
            "PersonEmail" => "sfdc-api-sdk.003.{$time}@xxxxxxxx.com",
        ],
        [
            "FirstName"   => "test-bulk-004-{$time}",
            "LastName"    => "4",
            "PersonEmail" => "sfdc-api-sdk.004.{$time}@xxxxxxxx.com",
        ],
    ];

    $batchJobInfo = $client->bulkUpsert("Account", $accountList);
    var_dump($batchJobInfo);
    $jobAll[] = new \SfdcApiSdk\Bulk\BulkStatusChecker($batchJobInfo);

    $manager = new \SfdcApiSdk\Bulk\BulkStatusCheckManager($client, $jobAll, null);
    $manager->setSleepSec(3);
    $manager->setTimeoutSec(60);
    $manager->checkBatchStatus(function ($jobId, $batchId, $state, $progress, $all) {
        var_dump(date("Y/m/d H:i:s") . ",[{$jobId}][{$batchId}}],status=" . $state . ", {$progress}/{$all}");
    }, false);
    $errors = $manager->retrieveBatchResults(1);

    $batchJobInfo = $client->bulkUpsert("Account", $accountList);
    var_dump($batchJobInfo);
    $jobAll[] = new \SfdcApiSdk\Bulk\BulkStatusChecker($batchJobInfo);

} catch (\SfdcApiSdk\Exception\AuthenticationException $e) {
    echo((string)$e . PHP_EOL . $e->getPrevious()->getResponse(true)->getBody());
} catch (\SfdcApiSdk\Exception\SfdcBulkApiException $e) {
    echo((string)$e . PHP_EOL . $e->getPrevious()->getResponse(true)->getBody());
} catch (\SfdcApiSdk\Exception\SfdcTimeoutException $e) {
    echo((string)$e);
}
