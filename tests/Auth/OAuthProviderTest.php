<?php


namespace SfdcApiSdk\Test\Auth;

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Message\Request;
use PHPUnit_Framework_TestCase;

class OAuthProviderTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $dotenv = new \Dotenv\Dotenv(__DIR__ . "/../../");
        $dotenv->load();
    }

    /**
     * @test
     */
    public function authorization_oauthSuccess()
    {
        $baseUrl = getenv("SFDC_API_SDK_BASE_URL");
        $httpClient = new \GuzzleHttp\Client();
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClient, $baseUrl);

        $authorized = $authProvider->authorization([
            "grant_type"    => "password",
            "username"      => getenv("SFDC_API_SDK_USERNAME"),
            "password"      => getenv("SFDC_API_SDK_PASSWORD"),
            "client_id"     => getenv("SFDC_API_SDK_CLIENT_ID"),
            "client_secret" => getenv("SFDC_API_SDK_CLIENT_SECRET"),
        ]);

        $this->assertEquals(true, $authorized);
        $this->assertStringStartsWith("https://", $authProvider->getInstanceUrl());
    }

    /**
     * @test
     * @expectedException \SfdcApiSdk\Exception\AuthenticationException
     */
    public function authorization_oauthFailed_throwAuthenticationException()
    {
        $httpClientMock = $this->createMock(\GuzzleHttp\Client::class);
        $httpClientMock->method('post')->will($this->throwException(new \GuzzleHttp\Exception\ClientException(__METHOD__, new Request(null, null), null, null)));

        $baseUrl = "https://login.salesforce.com";
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClientMock, $baseUrl);

        //throw
        $authProvider->authorization([]);
    }

    /**
     * @test
     * @expectedException \SfdcApiSdk\Exception\AuthenticationException
     * @expectedExceptionMessage authorize faild. 'access_token' not found.
     */
    public function authorization_oauthFailed_accessTokenUndefined_throwAuthenticationException()
    {
        $responseMock = $this->getMockBuilder('Response')->setMethods(array("json"))->getMock();
        //access_token is undefined
        $responseMock->method("json")->willReturn(array());

        $httpClientMock = $this->createMock(\GuzzleHttp\Client::class);
        $httpClientMock->method('post')->willReturn($responseMock);

        $baseUrl = "https://login.salesforce.com";
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClientMock, $baseUrl);

        //throw
        $authProvider->authorization([]);
    }

    /**
     * @test
     * @expectedException \SfdcApiSdk\Exception\AuthenticationException
     * @expectedExceptionMessage authorize faild. 'access_token' not found.
     */
    public function authorization_oauthFailed_accessTokenEmpty_throwAuthenticationException()
    {
        $responseMock = $this->getMockBuilder('Response')->setMethods(array("json"))->getMock();
        //access_token is empty
        $responseMock->method("json")->willReturn(array("access_token" => ""));

        $httpClientMock = $this->createMock(\GuzzleHttp\Client::class);
        $httpClientMock->method('post')->willReturn($responseMock);

        $baseUrl = "https://login.salesforce.com";
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClientMock, $baseUrl);

        //throw
        $authProvider->authorization([]);
    }

    /**
     * @test
     * @expectedException \SfdcApiSdk\Exception\AuthenticationException
     * @expectedExceptionMessage authorize faild. 'instance_url' not found.
     */
    public function authorization_oauthFailed_instanceUrlUndefined_throwAuthenticationException()
    {
        $responseMock = $this->getMockBuilder('Response')->setMethods(array("json"))->getMock();
        //instance_url is undefined
        $responseMock->method("json")->willReturn(array("access_token" => "test-token"));

        $httpClientMock = $this->createMock(\GuzzleHttp\Client::class);
        $httpClientMock->method('post')->willReturn($responseMock);

        $baseUrl = "https://login.salesforce.com";
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClientMock, $baseUrl);

        //throw
        $authProvider->authorization([]);
    }


    /**
     * @test
     * @expectedException \SfdcApiSdk\Exception\AuthenticationException
     * @expectedExceptionMessage authorize faild. 'instance_url' not found.
     */
    public function authorization_oauthFailed_instanceUrlEmpty_throwAuthenticationException()
    {
        $responseMock = $this->getMockBuilder('Response')->setMethods(array("json"))->getMock();
        //instance_url is empty
        $responseMock->method("json")->willReturn(array("access_token" => "test-token", "instance_url" => ""));

        $httpClientMock = $this->createMock(\GuzzleHttp\Client::class);
        $httpClientMock->method('post')->willReturn($responseMock);

        $baseUrl = "https://login.salesforce.com";
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClientMock, $baseUrl);

        //throw
        $authProvider->authorization([]);
    }

    /**
     * @test
     */
    public function authorized_validMemberVariable()
    {
        define('ACCESS_TOKEN', 'test-access-token');
        define('INSTANCE_URL', 'test-instance_url');

        $responseMock = $this->getMockBuilder('Response')->setMethods(array("json"))->getMock();
        //instance_url is empty
        $responseMock->method("json")->willReturn(array("access_token" => ACCESS_TOKEN, "instance_url" => INSTANCE_URL));

        $httpClientMock = $this->createMock(\GuzzleHttp\Client::class);
        $httpClientMock->method('post')->willReturn($responseMock);

        $baseUrl = "https://login.salesforce.com";
        $authProvider = new \SfdcApiSdk\Auth\OAuthProvider($httpClientMock, $baseUrl);

        $authProvider->authorization([]);

        $this->assertEquals(ACCESS_TOKEN, $authProvider->getAccessToken());
        $this->assertEquals(INSTANCE_URL, $authProvider->getInstanceUrl());
    }
}


