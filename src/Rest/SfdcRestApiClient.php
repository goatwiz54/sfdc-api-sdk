<?php

namespace SfdcApiSdk\Rest;

use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Exception\ClientException;

class SfdcRestApiClient
{
    private $authProvider;
    private $version;

    function __construct($authProvider, $version)
    {
        $this->authProvider = $authProvider;
        $this->version = $version;
    }

    /**
     * @param $soql urlencode()した結果を指定する。
     * @return mixed 配列を返却する。
     */
    public function query($soql)
    {
        $url = "{$this->getRestBasePath()}/query?q={$soql}";
        return $this->invoke($url, "GET", null);
    }

    /**
     * $objectで指定したオブジェクトに新規にレコードを作成する。
     * @param $object 登録先のオブジェクト名。
     * @param $payload 登録するデータ（連想配列）を指定する。
     * @return mixed 配列を返却する。
     */
    public function create($object, array $payload)
    {
        $url = "{$this->getRestBasePath()}/sobjects/{$object}";

        try {
            $response = $this->invoke($url, "POST", $payload);
            return $response["id"];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $responseBody = $e->getResponse()->getBody(true);
            throw new \SfdcApiSdk\Exception\SfdcRestApiException((string)$responseBody);
        }
    }

    /**
     * $objectで指定したオブジェクトのIDを指定してレコードを更新する。
     * @param $object 登録先のオブジェクト名。
     * @param $id Salesefore idを指定する。
     * @param $payload 登録するデータ（連想配列）を指定する。
     * @return mixed
     */
    public function update($object, $id, array $payload)
    {
        $url = "{$this->getRestBasePath()}/sobjects/{$object}/$id";

        return $this->invoke($url, "PATCH", $payload);
    }

    /**
     * @param $object オブジェクト名
     * @param $id レコードのID
     * @return mixed 送信結果
     */
    public function delete($object, $id)
    {
        $url = "{$this->getRestBasePath()}/sobjects/{$object}/{$id}";

        return $this->invoke($url, "DELETE", null);
    }

    /**
     * 「メモ&添付」にファイルを添付する
     * @param $id Salseforce idを指定
     * @param $name ファイル名
     * @param $payload base64エンコードしたデータ
     * @return string AttatchmentのSaleseforce id
     * @throws \SfdcApiSdk\Exception\SfdcRestApiException
     */
    public function attachment($id, $name, $payload)
    {
        $url = "{$this->getRestBasePath()}/sobjects/Attachment";

        $postData = [
            "ParentId" => $id,
            "Name"     => $name,
            "Body"     => $payload,
        ];

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
        try {
            $request = $this->authProvider->getHttpClient()->createRequest($method, $url);
            $this->buildHeader($request);
            if (isset($payload)) {
                $postData = json_encode($payload);
                $request->setBody(Stream::factory($postData));
            }

            $res = $this->authProvider->getHttpClient()->send($request);
            $json = $res->json();

            return $json;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \SfdcApiSdk\Exception\SfdcRestApiException($e->getMessage(), $e->getCode(), $e);
        }
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
     * @return string RestAPI用のパス
     */
    public function getRestBasePath()
    {
        return "{$this->authProvider->getInstanceUrl()}/services/data/v{$this->version}";
    }
}

