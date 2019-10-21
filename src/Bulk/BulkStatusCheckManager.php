<?php

namespace SfdcApiSdk\Bulk;

class BulkStatusCheckManager
{
    /**
     * @var float|int
     */
    private $timeoutSec;
    /**
     * @var int
     */
    private $sleepSec;
    /**
     * @var
     */
    private $bulkApiClient;
    /**
     * @var boolean
     */
    private $forceStopFlag;
    /**
     * @var array
     */
    private $checkerList;
    /**
     * @var BulkStatusListener
     */
    private $bulkStatusListener;

    /**
     * BulkStatusChecker constructor.
     * @param $timeoutSec
     */
    public function __construct($bulkApiClient, $checkerList, $bulkStatusListener)
    {
        $this->timeoutSec = 60 * 6;
        $this->sleepSec = 15;
        $this->bulkApiClient = $bulkApiClient;
        $this->checkerList = array_values($checkerList);

        if (isset($bulkStatusListener)) {
            $this->bulkStatusListener = $bulkStatusListener;
        }
        else {
            $this->bulkStatusListener = null;
        }
    }

    /**
     * @return mixed
     */
    public function getForceStopFlag()
    {
        return $this->forceStopFlag;
    }

    /**
     * @param mixed $forceStopFlag
     */
    public function setForceStopFlag($forceStopFlag)
    {
        $this->forceStopFlag = $forceStopFlag;
    }

    /**
     * @return mixed
     */
    public function getTimeoutSec()
    {
        return $this->timeoutSec;
    }

    /**
     * @param mixed $timeoutSec
     */
    public function setTimeoutSec($timeoutSec)
    {
        $this->timeoutSec = $timeoutSec;
    }

    /**
     * @return int
     */
    public function getSleepSec()
    {
        return $this->sleepSec;
    }

    /**
     * @param int $sleepSec
     */
    public function setSleepSec($sleepSec)
    {
        $this->sleepSec = $sleepSec;
    }

    /**
     * @return bool
     */
    public function checkBatchStatus($peek, $firstWait)
    {
        $startTime = strtotime("now");

        if ($firstWait) {
            sleep($this->getSleepSec());
        }

        $listIndexes = array_keys($this->checkerList);
        $listIndexes[/*Sentinel*/] = false;
        $progress = 1;

        while (($listId = current($listIndexes)) !== false) {
            //強制停止
            if ($this->getForceStopFlag()) {
                break;
            }

            //タイムリミット
            $endTime = strtotime("now");
            $diffTime = $endTime - $startTime;
            if ($diffTime >= $this->getTimeoutSec()) {
                throw new \SfdcApiSdk\Exception\SfdcTimeoutException("timeout {$diffTime} sec.");
            }

            $checker = $this->checkerList[$listId];
            $jobId = $checker->getBatchJobInfo()->getJobId();
            $batchId = $checker->getBatchJobInfo()->getBatchId();

            $json = $this->bulkApiClient->checkBatchStatus($jobId, $batchId);

            if (!isset($json["state"]) || strlen($json["state"]) <= 0) {
                throw new \SfdcApiSdk\Exception\SfdcBulkApiException("state is not found.");
            }
            $state = $json["state"];

            if (isset($peek)) {
                $peek($jobId, $batchId, $state, $progress, count($this->checkerList));
            }

            if (isset($this->bulkStatusListener)) {
                if ($this->bulkStatusListener->exists($state)) {
                    $this->bulkStatusListener->call($json);
                }
            }

            if ($state === "Queued" ||
                $state === "InProgress") {
                //do nothing
            } else if (
                $state === "Completed" ||
                $state === "Failed" ||
                $state === "Not Processed") {
                next($listIndexes);
                $progress++;
                continue;
            }
            else {
                throw new \SfdcApiSdk\Exception\SfdcBulkApiException("unknown state[$state}.");
            }

            sleep($this->getSleepSec());
        }
    }

    /**
     * 処理結果を取得する
     * @param int $errorsLimit 最大エラー情報の格納数
     * @return array エラー情報
     */
    public function retrieveBatchResults($errorsLimit = 10)
    {
        $keys = array_keys($this->checkerList);
        $errors = [];
        $stop = false;
        for ($index = 0; $index < count($keys); $index++) {
            if ($this->getForceStopFlag()) {
                break;
            }

            $checker = $this->checkerList[$keys[$index]];
            $result = $this->bulkApiClient->retrieveBatchResults($checker->getBatchJobInfo()->getJobId(), $checker->getBatchJobInfo()->getBatchId());

            foreach ($result as &$item) {
                if ($this->getForceStopFlag()) {
                    break;
                }
                if (isset($item["errors"])) {
                    $errors[] = $item["errors"];
                    if (count($errors) >= $errorsLimit) {
                        $stop = true;
                        break;
                    }
                }
            }
            if ($stop) {
                break;
            }
        }
        return $errors;
    }
}
