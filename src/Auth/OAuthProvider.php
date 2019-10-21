<?php

namespace SfdcApiSdk\Auth;

use http\Exception\RuntimeException;

require_once __DIR__ . "/AuthProvider.php";

class OAuthProvider implements AuthProviderInterface
{
    private $instanceUrl;
    private $accessToken;
    private $baseUrl;
    private $httpClient;

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function __construct($httpClient, $baseUrl)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = ($baseUrl) ? $baseUrl : "https://login.salesforce.com";
    }

    public function authorization($params)
    {
        $url = "{$this->baseUrl}/services/oauth2/token";

        try {
            $respons = $this->httpClient->post(
                $url, [
                    "body" => $params,
                ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \SfdcApiSdk\Exception\AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }

        $json = $respons->json();

        if (!isset($json["access_token"]) || strlen($json["access_token"]) <= 0) {
            throw new \SfdcApiSdk\Exception\AuthenticationException("authorize faild. 'access_token' not found.");
        }
        if (!isset($json["instance_url"]) || strlen($json["instance_url"]) <= 0) {
            throw new \SfdcApiSdk\Exception\AuthenticationException("authorize faild. 'instance_url' not found.");
        }

        $this->accessToken = $json["access_token"];
        $this->instanceUrl = $json["instance_url"];

        return true;
    }

    public function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }
}

