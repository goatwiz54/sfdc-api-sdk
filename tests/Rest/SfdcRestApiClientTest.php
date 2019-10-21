<?php

namespace SfdcApiSdk\Test\Rest;

date_default_timezone_set("Asia/Tokyo");

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit_Framework_TestCase;

class SfdcRestApiClientTest extends PHPUnit_Framework_TestCase
{
    private static $authProvider;
    private static $client;
    private static $email;

    public static function setUpBeforeClass()
    {
        $dotenv = new \Dotenv\Dotenv(__DIR__ . "/../../");
        $dotenv->load();

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

        self::$authProvider = $authProvider;
        self::$client = new \SfdcApiSdk\Rest\SfdcRestApiClient($authProvider, $version);

        self::$email = date('YmdHis') . "@xxxxxxxxxxxx.com";
    }

    /**
     * @test
     */
    public function create()
    {
        $account = [
            "LastName"    => "テストsfdc-ap-sdk",
            "FirstName"   => "create",
            "PersonEmail" => self::$email,
        ];

        $id = self::$client->create("Account", $account);

        $this->assertNotEmpty($id);
    }

    /**
     * @test
     */
    public function query()
    {
        $soql = urlencode("select PersonEmail, LastName, FirstName from Account where PersonEmail = '" . self::$email . "'");
        $actual = self::$client->query($soql);
        $actual = $actual["records"][0];
        $expect = [
            "PersonEmail" => self::$email,
            "LastName"    => "テストsfdc-ap-sdk",
            "FirstName"   => "create",
        ];
        $this->assertArraySubset($expect, $actual);
    }

    /**
     * @test
     */
    public function update()
    {
        $soql = urlencode("select Id from Account where PersonEmail = '" . self::$email . "'");
        $actual = self::$client->query($soql);
        $actual = $actual["records"][0];

        $account = [
            "FirstName" => "update",
        ];

        self::$client->update("Account", $actual["Id"], $account);

        $soql = urlencode("select PersonEmail, LastName, FirstName from Account where PersonEmail = '" . self::$email . "'");
        $actual = self::$client->query($soql);
        $actual = $actual["records"][0];

        $expect = [
            "PersonEmail" => self::$email,
            "LastName"    => "テストsfdc-ap-sdk",
            "FirstName"   => "update",
        ];
        $this->assertArraySubset($expect, $actual);
    }

    /**
     * @test
     */
    public function delete()
    {
        $soql = urlencode("select Id from Account where PersonEmail = '" . self::$email . "'");
        $actual = self::$client->query($soql);
        $actual = $actual["records"][0];

        self::$client->delete("Account", $actual["Id"]);

        $soql = urlencode("select PersonEmail, LastName, FirstName from Account where PersonEmail = '" . self::$email . "'");
        $actual = self::$client->query($soql);

        $expect = [
            "totalSize" => 0,
            "done"      => true,
            "records"   => [],
        ];
        $this->assertArraySubset($expect, $actual);
    }
}


