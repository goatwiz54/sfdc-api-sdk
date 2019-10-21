<?php

namespace SfdcApiSdk\Chatter;

use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Exception\ClientException;

/**
 * Class SfdcChatterApiClient
 * @package SfdcApiSdk\Chatter
 */
class SfdcChatterApiClient
{
    /**
     * @var
     */
    private $authProvider;

    /**
     * @var
     */
    private $version;

    /**
     * SfdcChatterApiClient constructor.
     * @param $authProvider
     * @param $version
     */
    function __construct($authProvider, $version)
    {
        $this->authProvider = $authProvider;
        $this->version = $version;
    }

    /**
     * @param $subjectId 投稿先のidを指定する
     * @param $message 投稿する内容
     * @param null $mention メンション先のidを指定する　@(ユーザ名)形式の投稿
     * @return string フィードのid
     * @throws \SfdcApiSdk\Exception\AuthenticationException
     */
    public function feed($subjectId, $message, $mention = null)
    {
        $postData = [
            "body"            => [
                "messageSegments" => [
                    [
                        "type" => "text",
                        "text" => "{$message}",
                    ],
                ]
            ],
            "feedElementType" => "FeedItem",
            "subjectId"       => "{$subjectId}"
        ];

        if (isset($mention)) {
            $postData["body"]["messageSegments"][] = [
                "type" => "mention",
                "text" => "{$mention}",
            ];
        }

        $url = "{$this->getRestBasePath()}/chatter/feed-elements";

        try {
            $response = $this->invoke($url, "POST", $postData);
            return (string)$response["id"];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \SfdcApiSdk\Exception\AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * フィードへのコメントを投稿する
     * @param $feedElementId コメントするフィードid
     * @param $message 投稿するコメント
     * @return string コメントのid
     * @throws \SfdcApiSdk\Exception\AuthenticationException
     */
    public function comment($feedElementId, $message)
    {
        $postData = [
            "body" => [
                "messageSegments" => [
                    [
                        "type" => "text",
                        "text" => "{$message}",
                    ],
                ]
            ],
        ];

        $url = "{$this->getRestBasePath()}/chatter/feed-elements/{$feedElementId}/capabilities/comments/items";

        try {
            $response = $this->invoke($url, "POST", $postData);
            return (string)$response["id"];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \SfdcApiSdk\Exception\AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $url APIパス
     * @param $method HTTPメソッド
     * @param $payload 送信するデータ
     * @return mixed 送信結果
     */
    public function invoke($url, $method, $payload)
    {
        $request = $this->authProvider->getHttpClient()->createRequest($method, $url);
        $this->buildHeader($request);
        if (isset($payload)) {
            $postData = json_encode($payload);
            $request->setBody(Stream::factory($postData));
        }

        $res = $this->authProvider->getHttpClient()->send($request);
        $json = $res->json();

        return $json;

    }

    /**
     * @param $request GuzzleHttp\Message\Request
     */
    public function buildHeader($request)
    {
        $request->setHeader('Authorization', 'OAuth ' . $this->authProvider->getAccessToken());
        $request->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $request->setHeader('Accept', 'application/json');
    }

    /**
     * @return string ChatterAPI用のパス
     */
    public function getRestBasePath()
    {
        return "{$this->authProvider->getInstanceUrl()}/services/data/v{$this->version}";
    }
}

