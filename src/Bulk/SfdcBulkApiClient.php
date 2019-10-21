<?php

namespace SfdcApiSdk\Bulk;

use GuzzleHttp\Stream\Stream;

class SfdcBulkApiClient
{
    private $client;
    private $authProvider;
    private $version;

    function __construct($authProvider, $version)
    {
        $this->authProvider = $authProvider;
        $this->version = $version;
    }

    public function buildHeader($request, $sessionId)
    {
        $request->setHeader('X-SFDC-Session', $sessionId);
        $request->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $request->setHeader('PrettyPrint', '1');
    }

    public function createJob(CreateJobRequest $jobRequest)
    {
        $url = "{$this->getBulkAPiBasePath()}";

        $request = $this->authProvider->getHttpClient()->createRequest('POST', $url);
        $this->buildHeader($request, $this->authProvider->getAccessToken());

        $postData = [
            "object"      => $jobRequest->getObject(),
            "contentType" => $jobRequest->getContentType(),
            "operation"   => $jobRequest->getOperation(),
        ];

        if (strlen($jobRequest->getExternalFieldName()) >= 1) {
            //外部キーの指定
            $postData["externalIdFieldName"] = $jobRequest->getExternalFieldName();
        }

        $postData = json_encode($postData);
        $request->setBody(Stream::factory($postData));

        $respons = $this->authProvider->getHttpClient()->send($request);

        $status = $respons->getStatusCode();

        $jobInfo = $respons->json();

        return $jobInfo;
    }

    public function closeJob($jobId)
    {
        $url = "{$this->getBulkAPiBasePath()}/{$jobId}";

        $request = $this->authProvider->getHttpClient()->createRequest('POST', $url);
        $this->buildHeader($request, $this->authProvider->getAccessToken());

        $postData = [
            "state" => "Closed"
        ];
        $postData = json_encode($postData);
        $request->setBody(Stream::factory($postData));

        $res = $this->authProvider->getHttpClient()->send($request);
        $json = $res->json();

        return $json;
    }


    public function bulkUpsert($object, $pluralData)
    {
        try {
            $jobRequest = new CreateJobRequest();
            $jobRequest->setObject($object);
            $jobRequest->setExternalFieldName("Id");
            $jobRequest->setContentType("JSON");
            $jobRequest->setOperation("upsert");

            $jobInfo = $this->createJob($jobRequest);
            $jobId = $jobInfo["id"];

            $batchInfo = $this->addBatchJob($jobId, $pluralData);
            $batchId = $batchInfo["id"];

            $info = $this->closeJob($jobId);

            $info = new \SfdcApiSdk\Bulk\BatchJobInfo;
            $info->setJobId($jobId);
            $info->setBatchId($batchId);

            return $info;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \SfdcApiSdk\Exception\SfdcBulkApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function checkBatchStatus($jobId, $batchId)
    {
        $url = "{$this->getBulkAPiBasePath()}/{$jobId}/batch/{$batchId}";

        $request = $this->authProvider->getHttpClient()->createRequest('PUT', $url);
        $this->buildHeader($request, $this->authProvider->getAccessToken());

        $res = $this->authProvider->getHttpClient()->send($request);
        $json = $res->json();

        return $json;
    }

    public function retrieveBatchResults($jobId, $batchId)
    {
        $url = "{$this->getBulkAPiBasePath()}/{$jobId}/batch/{$batchId}/result";

        $request = $this->authProvider->getHttpClient()->createRequest('PUT', $url);
        $this->buildHeader($request, $this->authProvider->getAccessToken());

        $res = $this->authProvider->getHttpClient()->send($request);
        $json = $res->json();

        return $json;
    }

    public function addBatchJob($jobId, $pluralityData)
    {
        $url = "{$this->getBulkAPiBasePath()}/{$jobId}/batch";

        $request = $this->authProvider->getHttpClient()->createRequest('POST', $url);
        $this->buildHeader($request, $this->authProvider->getAccessToken());

        $postData = json_encode($pluralityData);
        $request->setBody(Stream::factory($postData));

        $res = $this->authProvider->getHttpClient()->send($request);
        $json = $res->json();

        return $json;
    }

    public function getBulkAPiBasePath()
    {
        return "{$this->authProvider->getInstanceUrl()}/services/async/{$this->version}/job";
    }
}
